<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use TallCms\Cms\Exceptions\ConfigurationException;
use TallCms\Cms\Exceptions\DownloadException;
use TallCms\Cms\Exceptions\ExtractionException;
use TallCms\Cms\Exceptions\IncompatiblePlatformException;
use TallCms\Cms\Exceptions\IncompatibleVersionException;
use TallCms\Cms\Exceptions\InsufficientDiskSpaceException;
use TallCms\Cms\Exceptions\IntegrityException;
use TallCms\Cms\Exceptions\InvalidReleaseException;
use TallCms\Cms\Exceptions\MissingDependencyException;
use TallCms\Cms\Exceptions\SecurityException;
use TallCms\Cms\Exceptions\SignatureException;
use TallCms\Cms\Exceptions\UpdateException;
use TallCms\Cms\Exceptions\UpdateInProgressException;
use ZipArchive;

class TallCmsUpdater
{
    private const LOCK_FILE = '.tallcms-update.lock';

    private const STATE_FILE = '.tallcms-update-state.json';

    private const MANIFEST_FILE = '.tallcms-manifest.json';

    private const LOCK_TIMEOUT_SECONDS = 1800; // 30 minutes

    private const MIN_DISK_SPACE_MB = 200;

    private ?string $targetVersion = null;

    private array $preservedPaths = [
        '.env',
        '.env.backup',
        'storage/',
        'themes/',
        'plugins/',
        'database/database.sqlite',
        'public/storage',
        'public/themes/',
    ];

    /**
     * Check if sodium functions are available for signature verification.
     */
    public function verifySodiumAvailable(): void
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            throw new MissingDependencyException(
                'Signature verification unavailable. Please run: composer require paragonie/sodium_compat'
            );
        }

        // Quick self-test with known Ed25519 test vectors
        try {
            $testPubKey = sodium_hex2bin('d75a980182b10ab7d54bfed3c964073a0ee172f3daa62325af021a68f707511a');
            $testSig = sodium_hex2bin('e5564300c360ac729086e2cc806e828a84877f1eb8e5d974d873e065224901555fb8821590a33bacc61e39701cf9b46bd25bf5f0595bbe24655141438e7a100b');
            $testMsg = '';
            sodium_crypto_sign_verify_detached($testSig, $testMsg, $testPubKey);
        } catch (\Throwable $e) {
            throw new MissingDependencyException(
                'Sodium functions available but not working correctly: '.$e->getMessage()
            );
        }
    }

    /**
     * Verify release signature and integrity.
     */
    public function verifyRelease(string $zipPath, string $checksumsPath, string $signaturePath): void
    {
        $this->verifySodiumAvailable();

        $publicKeyHex = config('tallcms.updates.public_key');
        if (empty($publicKeyHex)) {
            throw new ConfigurationException('Update public key not configured. Set TALLCMS_UPDATE_PUBLIC_KEY in .env');
        }

        try {
            $publicKey = sodium_hex2bin($publicKeyHex);
        } catch (\Throwable $e) {
            throw new ConfigurationException('Invalid public key format: '.$e->getMessage());
        }

        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new ConfigurationException(
                'Invalid public key length. Expected '.SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES.' bytes, got '.strlen($publicKey)
            );
        }

        $checksums = file_get_contents($checksumsPath);
        if ($checksums === false) {
            throw new IntegrityException('Failed to read checksums file');
        }

        $signatureBase64 = file_get_contents($signaturePath);
        if ($signatureBase64 === false) {
            throw new SignatureException('Failed to read signature file');
        }

        $signature = base64_decode($signatureBase64, true);
        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new SignatureException('Invalid signature format');
        }

        // Verify Ed25519 signature of checksums.json
        if (! sodium_crypto_sign_verify_detached($signature, $checksums, $publicKey)) {
            throw new SignatureException('Invalid signature - release may be tampered');
        }

        // Verify ZIP hash matches signed checksums
        $checksumsData = json_decode($checksums, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IntegrityException('Invalid checksums JSON: '.json_last_error_msg());
        }

        $expectedHash = $checksumsData['zip_sha256'] ?? null;
        if (empty($expectedHash)) {
            throw new IntegrityException('ZIP hash not found in checksums');
        }

        $actualHash = hash_file('sha256', $zipPath);
        if (! hash_equals($expectedHash, $actualHash)) {
            throw new IntegrityException('ZIP checksum mismatch');
        }
    }

    /**
     * Get the current update state.
     */
    public function getUpdateState(): array
    {
        $stateFile = storage_path('app/'.self::STATE_FILE);

        if (! file_exists($stateFile)) {
            return ['status' => 'no_update'];
        }

        $content = file_get_contents($stateFile);
        $state = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Update state file corrupted', [
                'error' => json_last_error_msg(),
                'content_preview' => substr($content, 0, 200),
            ]);

            return [
                'status' => 'error',
                'error' => 'Update state file is corrupted. Check logs or clear lock to retry.',
            ];
        }

        return $state;
    }

    /**
     * Update the state file atomically.
     */
    public function updateState(array $data): void
    {
        $stateFile = storage_path('app/'.self::STATE_FILE);
        $tempFile = $stateFile.'.tmp.'.uniqid();

        $state = $this->getUpdateState();
        if ($state['status'] === 'error') {
            $state = [];
        }

        $state = array_merge($state, $data);
        $state['updated_at'] = now()->toIso8601String();

        $written = file_put_contents($tempFile, json_encode($state, JSON_PRETTY_PRINT));

        if ($written === false) {
            throw new UpdateException('Failed to write update state');
        }

        if (! rename($tempFile, $stateFile)) {
            @unlink($tempFile);
            throw new UpdateException('Failed to update state file');
        }
    }

    /**
     * Validate that no update is currently in progress.
     */
    public function validateNoLock(): void
    {
        $lockFile = storage_path('app/'.self::LOCK_FILE);

        if (! file_exists($lockFile)) {
            return;
        }

        $lock = json_decode(file_get_contents($lockFile), true);

        // Check if lock is stale (older than 30 minutes)
        $lockTime = strtotime($lock['started_at'] ?? '');
        if ($lockTime && (time() - $lockTime) > self::LOCK_TIMEOUT_SECONDS) {
            Log::warning('Clearing stale update lock', $lock);
            $this->clearLock();

            return;
        }

        // Check if PID is still running (Unix only, requires ext-posix)
        if (isset($lock['pid']) && PHP_OS_FAMILY !== 'Windows' && function_exists('posix_kill')) {
            if (! @posix_kill($lock['pid'], 0)) {
                Log::warning('Clearing orphaned update lock (process dead)', $lock);
                $this->clearLock();

                return;
            }
        }

        throw new UpdateInProgressException(
            'Another update is in progress. Started at: '.($lock['started_at'] ?? 'unknown')
        );
    }

    /**
     * Acquire the update lock.
     */
    public function acquireLock(): void
    {
        $lockFile = storage_path('app/'.self::LOCK_FILE);
        file_put_contents($lockFile, json_encode([
            'pid' => getmypid(),
            'started_at' => now()->toIso8601String(),
            'version' => $this->targetVersion,
        ]));
    }

    /**
     * Clear the update lock.
     */
    public function clearLock(): void
    {
        @unlink(storage_path('app/'.self::LOCK_FILE));
    }

    /**
     * Validate available disk space.
     */
    public function validateDiskSpace(): void
    {
        $freeSpace = disk_free_space(base_path());
        $requiredSpace = self::MIN_DISK_SPACE_MB * 1024 * 1024;

        if ($freeSpace < $requiredSpace) {
            throw new InsufficientDiskSpaceException(
                sprintf(
                    'Insufficient disk space. Required: %dMB, Available: %dMB',
                    self::MIN_DISK_SPACE_MB,
                    round($freeSpace / 1024 / 1024)
                )
            );
        }
    }

    /**
     * Validate platform compatibility (PHP version, extensions).
     *
     * @return array Warnings (non-blocking issues)
     */
    public function validatePlatformCompatibility(array $marker): array
    {
        $errors = [];
        $warnings = [];

        // Check PHP version (BLOCKING)
        $requiredPhp = $marker['min_php'] ?? '8.2';
        if (version_compare(PHP_VERSION, $requiredPhp, '<')) {
            $errors[] = "This release requires PHP {$requiredPhp}+. You have ".PHP_VERSION;
        }

        // Check required extensions (BLOCKING)
        $requiredExtensions = $marker['required_extensions'] ?? ['pdo', 'mbstring', 'openssl'];
        $missing = [];
        foreach ($requiredExtensions as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        if (! empty($missing)) {
            $errors[] = 'Missing required PHP extensions: '.implode(', ', $missing);
        }

        // Check platform match (WARNING ONLY - allow to proceed)
        $buildPlatform = $marker['build_platform'] ?? null;
        if ($buildPlatform && $buildPlatform !== PHP_OS_FAMILY) {
            $warnings[] = "Platform mismatch: Release built on {$buildPlatform}, you're running ".PHP_OS_FAMILY.'. '.
                'Some vendor packages may not work. If issues occur, run \'composer install\' after update.';
        }

        // Check PHP major version match (WARNING)
        $buildPhp = $marker['build_php'] ?? null;
        if ($buildPhp) {
            $buildMajor = (int) explode('.', $buildPhp)[0];
            $targetMajor = (int) explode('.', PHP_VERSION)[0];
            if ($buildMajor !== $targetMajor) {
                $warnings[] = "PHP version mismatch: Release built with PHP {$buildPhp}, you have ".PHP_VERSION.'. '.
                    'Consider running \'composer install\' after update.';
            }
        }

        if (! empty($errors)) {
            throw new IncompatiblePlatformException(implode("\n", $errors));
        }

        return $warnings;
    }

    /**
     * Validate the release marker file.
     */
    public function validateReleaseMarker(string $extractedDir): array
    {
        $markerPath = $extractedDir.'/.tallcms-release';

        if (! file_exists($markerPath)) {
            throw new InvalidReleaseException('Not a valid TallCMS release (missing marker)');
        }

        $marker = json_decode(file_get_contents($markerPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidReleaseException('Invalid release marker: '.json_last_error_msg());
        }

        if (($marker['product'] ?? '') !== 'tallcms') {
            throw new InvalidReleaseException('Not a TallCMS release');
        }

        // Version compatibility check
        $currentVersion = config('tallcms.version');
        $minRequired = $marker['min_tallcms'] ?? '0.0.0';

        if (version_compare($currentVersion, $minRequired, '<')) {
            throw new IncompatibleVersionException(
                "This update requires TallCMS {$minRequired}+. You have {$currentVersion}."
            );
        }

        return $marker;
    }

    /**
     * Check database backup capability.
     */
    public function checkDatabaseBackupCapability(): array
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $result = [
            'capable' => false,
            'method' => null,
            'warning' => null,
            'size_mb' => null,
            'driver' => $driver,
        ];

        $config = config("database.connections.{$connection}");

        switch ($driver) {
            case 'sqlite':
                $dbPath = $config['database'] ?? '';
                $size = file_exists($dbPath) ? filesize($dbPath) : 0;
                $result['size_mb'] = round($size / 1024 / 1024, 1);
                $result['capable'] = $size <= 500 * 1024 * 1024;
                $result['method'] = 'file_copy';
                if (! $result['capable']) {
                    $result['warning'] = "SQLite database is {$result['size_mb']}MB (limit: 500MB). Manual backup recommended.";
                }
                break;

            case 'mysql':
            case 'mariadb':
                $hasTool = $this->commandExists('mysqldump');
                $result['capable'] = $hasTool;
                $result['method'] = 'mysqldump';
                if (! $hasTool) {
                    $result['warning'] = 'mysqldump not available on this server. Database will not be backed up automatically.';
                } else {
                    try {
                        $sizeResult = DB::select('SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = ?', [$config['database'] ?? '']);
                        $size = $sizeResult[0]->size ?? 0;
                        $result['size_mb'] = round($size / 1024 / 1024, 1);
                        if ($size > config('tallcms.updates.db_backup_size_limit', 100 * 1024 * 1024)) {
                            $result['capable'] = false;
                            $result['warning'] = "Database is {$result['size_mb']}MB (limit: 100MB). Manual backup recommended.";
                        }
                    } catch (\Throwable $e) {
                        $result['warning'] = 'Could not determine database size: '.$e->getMessage();
                    }
                }
                break;

            case 'pgsql':
                $hasTool = $this->commandExists('pg_dump');
                $result['capable'] = $hasTool;
                $result['method'] = 'pg_dump';
                if (! $hasTool) {
                    $result['warning'] = 'pg_dump not available on this server. Database will not be backed up automatically.';
                }
                break;

            default:
                $result['warning'] = "Unsupported database driver: {$driver}. Database will not be backed up.";
        }

        return $result;
    }

    /**
     * Check if a command exists on the system.
     */
    private function commandExists(string $command): bool
    {
        $result = Process::run(PHP_OS_FAMILY === 'Windows' ? "where {$command}" : "which {$command}");

        return $result->successful();
    }

    /**
     * Backup critical files before update.
     */
    public function backupFiles(): ?string
    {
        $this->ensureBackupDirectory();

        $backupDir = storage_path('app/tallcms-backups/files/'.date('Y-m-d_His'));
        mkdir($backupDir, 0755, true);

        $filesToBackup = [
            '.env' => base_path('.env'),
            'composer.json' => base_path('composer.json'),
            'composer.lock' => base_path('composer.lock'),
        ];

        // Add config files
        $configDir = config_path();
        if (is_dir($configDir)) {
            foreach (glob($configDir.'/*.php') as $configFile) {
                $name = 'config/'.basename($configFile);
                $filesToBackup[$name] = $configFile;
            }
        }

        $backedUp = 0;
        foreach ($filesToBackup as $name => $sourcePath) {
            if (file_exists($sourcePath)) {
                $destPath = $backupDir.'/'.$name;
                $destDir = dirname($destPath);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                copy($sourcePath, $destPath);
                $backedUp++;
            }
        }

        if ($backedUp === 0) {
            rmdir($backupDir);

            return null;
        }

        Log::info('Files backed up', ['path' => $backupDir, 'count' => $backedUp]);

        // Enforce backup retention
        $this->pruneOldBackups('files');

        return $backupDir;
    }

    /**
     * Backup the database.
     */
    public function backupDatabase(): ?string
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $this->ensureBackupDirectory();

        $backupPath = match ($driver) {
            'sqlite' => $this->backupSqlite($connection),
            'mysql', 'mariadb' => $this->backupMysql($connection),
            'pgsql' => $this->backupPostgres($connection),
            default => null,
        };

        if ($backupPath) {
            // Enforce backup retention
            $this->pruneOldBackups('db');
        }

        return $backupPath;
    }

    private function backupSqlite(string $connection): ?string
    {
        $dbPath = config("database.connections.{$connection}.database");
        $size = filesize($dbPath);

        if ($size > 500 * 1024 * 1024) {
            Log::warning('Database too large for backup', ['size' => $size]);

            return null;
        }

        $backupPath = storage_path('app/tallcms-backups/db/db_'.date('Y-m-d_His').'.sqlite');
        copy($dbPath, $backupPath);

        return $backupPath;
    }

    private function backupMysql(string $connection): ?string
    {
        if (! $this->commandExists('mysqldump')) {
            Log::warning('mysqldump not available');

            return null;
        }

        $backupPath = storage_path('app/tallcms-backups/db/db_'.date('Y-m-d_His').'.sql');
        $config = config("database.connections.{$connection}");

        // SECURITY: Use MYSQL_PWD env var to avoid password in process list
        $process = Process::env([
            'MYSQL_PWD' => $config['password'] ?? '',
        ])->run(sprintf(
            'mysqldump -h%s -u%s %s > %s',
            escapeshellarg($config['host'] ?? 'localhost'),
            escapeshellarg($config['username'] ?? 'root'),
            escapeshellarg($config['database'] ?? ''),
            escapeshellarg($backupPath)
        ));

        return $process->successful() ? $backupPath : null;
    }

    private function backupPostgres(string $connection): ?string
    {
        if (! $this->commandExists('pg_dump')) {
            Log::warning('pg_dump not available');

            return null;
        }

        $backupPath = storage_path('app/tallcms-backups/db/db_'.date('Y-m-d_His').'.sql');
        $config = config("database.connections.{$connection}");

        // SECURITY: Use PGPASSWORD env var to avoid password in process list
        $process = Process::env([
            'PGPASSWORD' => $config['password'] ?? '',
        ])->run(sprintf(
            'pg_dump -h %s -U %s -d %s -f %s',
            escapeshellarg($config['host'] ?? 'localhost'),
            escapeshellarg($config['username'] ?? 'postgres'),
            escapeshellarg($config['database'] ?? ''),
            escapeshellarg($backupPath)
        ));

        return $process->successful() ? $backupPath : null;
    }

    /**
     * Ensure backup directories exist.
     */
    private function ensureBackupDirectory(): void
    {
        $dirs = [
            storage_path('app/tallcms-backups'),
            storage_path('app/tallcms-backups/db'),
            storage_path('app/tallcms-backups/files'),
            storage_path('app/tallcms-backups/quarantine'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Prune old backups to enforce retention limit.
     */
    private function pruneOldBackups(string $type): void
    {
        $retention = config('tallcms.updates.backup_retention', 3);
        $backupDir = storage_path("app/tallcms-backups/{$type}");

        if (! is_dir($backupDir)) {
            return;
        }

        // Get all backup entries sorted by modification time (newest first)
        $entries = [];
        foreach (scandir($backupDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $backupDir.'/'.$entry;
            $entries[$path] = filemtime($path);
        }

        // Sort by time descending (newest first)
        arsort($entries);

        // Remove entries beyond retention limit
        $count = 0;
        foreach (array_keys($entries) as $path) {
            $count++;
            if ($count > $retention) {
                if (is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    @unlink($path);
                }
                Log::info('Pruned old backup', ['path' => $path]);
            }
        }
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Detect file changes between installed manifest and release manifest.
     */
    public function detectChanges(array $oldManifest, array $newManifest): array
    {
        $changes = [
            'modified' => [],
            'added' => [],
            'quarantine' => [],
            'conflicts' => [],
        ];

        $oldFiles = $oldManifest['files'] ?? [];
        $newFiles = $newManifest['files'] ?? [];

        // Files in new version
        foreach ($newFiles as $path => $hash) {
            if (! isset($oldFiles[$path])) {
                $changes['added'][] = $path;
            } elseif ($this->isLocallyModified($path, $oldFiles[$path])) {
                $changes['modified'][] = $path;
            }
        }

        // Files removed in new version
        foreach ($oldFiles as $path => $hash) {
            if (! isset($newFiles[$path]) && ! $this->isPreserved($path)) {
                if ($this->isLocallyModified($path, $hash)) {
                    $changes['conflicts'][] = $path;
                } else {
                    $changes['quarantine'][] = $path;
                }
            }
        }

        return $changes;
    }

    /**
     * Check if a file has been locally modified.
     */
    private function isLocallyModified(string $path, string $expectedHash): bool
    {
        $fullPath = base_path($path);
        if (! file_exists($fullPath)) {
            return false;
        }

        // Handle hash format (sha256:abc123... or just abc123...)
        $expectedHash = str_replace('sha256:', '', $expectedHash);
        $actualHash = hash_file('sha256', $fullPath);

        return ! hash_equals($expectedHash, $actualHash);
    }

    /**
     * Check if a path is in the preserved list.
     */
    private function isPreserved(string $path): bool
    {
        foreach ($this->preservedPaths as $preserved) {
            if (str_starts_with($path, $preserved)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect potential references for files to be quarantined.
     */
    public function detectPotentialReferences(array $filesToRemove): array
    {
        $warnings = [];

        foreach ($filesToRemove as $path) {
            $refs = [];

            // Check if in autoload paths
            $autoloadPaths = ['app/', 'database/factories/', 'database/seeders/'];
            foreach ($autoloadPaths as $autoloadPath) {
                if (str_starts_with($path, $autoloadPath)) {
                    $refs[] = "autoload ({$autoloadPath})";
                    break;
                }
            }

            // Check if config file
            if (str_starts_with($path, 'config/') && str_ends_with($path, '.php')) {
                $refs[] = 'config()';
            }

            // Check if view file
            if (str_starts_with($path, 'resources/views/') && str_ends_with($path, '.blade.php')) {
                $refs[] = 'view()';
            }

            // Check if route file
            if (str_starts_with($path, 'routes/')) {
                $refs[] = 'route loading';
            }

            $warnings[$path] = $refs;
        }

        return $warnings;
    }

    /**
     * Check if quarantine confirmation is required.
     */
    public function requiresQuarantineConfirmation(array $changes): bool
    {
        return ! empty($changes['quarantine']) || ! empty($changes['conflicts']);
    }

    /**
     * Move files to quarantine directory.
     */
    public function quarantineFiles(array $files): void
    {
        $this->ensureBackupDirectory();
        $quarantineDir = storage_path('app/tallcms-backups/quarantine/'.date('Y-m-d_His'));

        foreach ($files as $path) {
            $source = base_path($path);
            $dest = $quarantineDir.'/'.$path;

            if (file_exists($source)) {
                $destDir = dirname($dest);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                rename($source, $dest);
                Log::info("Quarantined: {$path}");
            }
        }
    }

    /**
     * Safely extract a ZIP file with zip-slip protection.
     */
    public function extractSafely(string $zipPath, string $targetDir): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new ExtractionException('Failed to open ZIP file');
        }

        $targetDir = rtrim(realpath($targetDir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        // First pass: validate all paths
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $normalizedPath = $this->normalizePath($filename);

            if ($normalizedPath === null) {
                $zip->close();
                throw new SecurityException("Invalid path in ZIP: {$filename}");
            }

            $fullPath = $targetDir.$normalizedPath;
            if (! str_starts_with($fullPath, $targetDir)) {
                $zip->close();
                throw new SecurityException("Zip-slip attempt: {$filename}");
            }
        }

        // Safe to extract
        $zip->extractTo($targetDir);
        $zip->close();
    }

    /**
     * Normalize a path without relying on filesystem.
     */
    private function normalizePath(string $path): ?string
    {
        // Reject absolute paths
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)) {
            return null;
        }

        // Split and process components
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if (empty($parts)) {
                    return null; // Attempting to go above root
                }
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Check if exec functions are available.
     */
    public function isExecAvailable(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        $disabled = explode(',', ini_get('disable_functions'));

        return ! in_array('exec', array_map('trim', $disabled));
    }

    /**
     * Check if queue is available (not sync driver).
     */
    public function isQueueAvailable(): bool
    {
        return config('queue.default') !== 'sync';
    }

    /**
     * Check if we should check for updates based on interval.
     */
    public function shouldCheckForUpdates(): bool
    {
        $lastCheck = Cache::get('tallcms_last_update_check');
        $interval = config('tallcms.updates.check_interval', 86400);

        return $lastCheck === null || (now()->timestamp - $lastCheck) > $interval;
    }

    /**
     * Fetch the latest release from GitHub.
     */
    public function checkForUpdates(): ?array
    {
        if (! config('tallcms.updates.enabled', true)) {
            return null;
        }

        $release = Cache::remember('tallcms_latest_release', config('tallcms.updates.cache_ttl', 3600), function () {
            return $this->fetchLatestRelease();
        });

        Cache::put('tallcms_last_update_check', now()->timestamp);

        return $release;
    }

    /**
     * Fetch the latest release from GitHub API.
     */
    private function fetchLatestRelease(): ?array
    {
        $repo = config('tallcms.updates.github_repo', 'tallcms/tallcms');
        $token = config('tallcms.updates.github_token');

        $headers = ['Accept' => 'application/vnd.github.v3+json'];
        if ($token) {
            $headers['Authorization'] = "token {$token}";
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if (! $response->successful()) {
                Log::warning('Failed to fetch latest release', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $this->formatRelease($response->json());
        } catch (\Throwable $e) {
            Log::error('Error fetching latest release', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Fetch a specific release by version/tag.
     */
    public function fetchRelease(string $version): ?array
    {
        $repo = config('tallcms.updates.github_repo', 'tallcms/tallcms');
        $token = config('tallcms.updates.github_token');
        $tag = str_starts_with($version, 'v') ? $version : "v{$version}";

        $headers = ['Accept' => 'application/vnd.github.v3+json'];
        if ($token) {
            $headers['Authorization'] = "token {$token}";
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/tags/{$tag}");

            if (! $response->successful()) {
                Log::warning('Failed to fetch release by tag', [
                    'tag' => $tag,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $this->formatRelease($response->json());
        } catch (\Throwable $e) {
            Log::error('Error fetching release by tag', [
                'tag' => $tag,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Normalize release response data.
     */
    private function formatRelease(array $data): array
    {
        return [
            'version' => ltrim($data['tag_name'] ?? '', 'v'),
            'tag' => $data['tag_name'] ?? '',
            'name' => $data['name'] ?? '',
            'body' => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? null,
            'html_url' => $data['html_url'] ?? '',
            'assets' => collect($data['assets'] ?? [])->map(fn ($asset) => [
                'name' => $asset['name'],
                'url' => $asset['browser_download_url'],
                'size' => $asset['size'],
            ])->all(),
        ];
    }

    /**
     * Check if an update is available.
     */
    public function isUpdateAvailable(): bool
    {
        $latest = $this->checkForUpdates();
        if (! $latest) {
            return false;
        }

        $currentVersion = config('tallcms.version');

        return version_compare($latest['version'], $currentVersion, '>');
    }

    /**
     * Download release assets.
     */
    public function downloadRelease(array $release): array
    {
        $this->ensureBackupDirectory();
        $downloadDir = storage_path('app/tallcms-downloads');

        if (! is_dir($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }

        $files = [];
        $tag = $release['tag'];

        foreach ($release['assets'] as $asset) {
            $name = $asset['name'];

            // Only download the files we need
            if (! str_contains($name, $tag) && ! in_array($name, ['checksums.json', 'checksums.json.sig'])) {
                continue;
            }

            $path = "{$downloadDir}/{$name}";

            $response = Http::timeout(300)->withOptions([
                'sink' => $path,
            ])->get($asset['url']);

            if (! $response->successful()) {
                throw new DownloadException("Failed to download {$name}: ".$response->status());
            }

            $files[$name] = $path;
        }

        return $files;
    }

    /**
     * Save the installed manifest after successful update.
     */
    public function saveInstalledManifest(array $releaseManifest): void
    {
        $installedManifest = $releaseManifest;
        $installedManifest['installed_at'] = now()->toIso8601String();

        file_put_contents(
            storage_path('app/'.self::MANIFEST_FILE),
            json_encode($installedManifest, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Get the installed manifest.
     */
    public function getInstalledManifest(): ?array
    {
        $path = storage_path('app/'.self::MANIFEST_FILE);
        if (! file_exists($path)) {
            return null;
        }

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Set the target version for updates.
     */
    public function setTargetVersion(string $version): void
    {
        $this->targetVersion = $version;
    }

    /**
     * Get the target version.
     */
    public function getTargetVersion(): ?string
    {
        return $this->targetVersion;
    }

    /**
     * Clear the state file.
     */
    public function clearState(): void
    {
        @unlink(storage_path('app/'.self::STATE_FILE));
    }

    /**
     * Run preflight checks and return results.
     */
    public function runPreflightChecks(): array
    {
        $checks = [
            'sodium' => ['status' => 'pending', 'message' => ''],
            'public_key' => ['status' => 'pending', 'message' => ''],
            'disk_space' => ['status' => 'pending', 'message' => ''],
            'lock' => ['status' => 'pending', 'message' => ''],
            'exec' => ['status' => 'pending', 'message' => ''],
            'queue' => ['status' => 'pending', 'message' => ''],
        ];

        // Check sodium
        try {
            $this->verifySodiumAvailable();
            $checks['sodium'] = ['status' => 'pass', 'message' => 'Signature verification available'];
        } catch (MissingDependencyException $e) {
            $checks['sodium'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }

        // Check public key
        $publicKey = config('tallcms.updates.public_key');
        if (empty($publicKey)) {
            $checks['public_key'] = ['status' => 'fail', 'message' => 'Public key not configured'];
        } else {
            $checks['public_key'] = ['status' => 'pass', 'message' => 'Public key configured'];
        }

        // Check disk space
        try {
            $this->validateDiskSpace();
            $freeSpace = round(disk_free_space(base_path()) / 1024 / 1024);
            $checks['disk_space'] = ['status' => 'pass', 'message' => "{$freeSpace}MB available"];
        } catch (InsufficientDiskSpaceException $e) {
            $checks['disk_space'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }

        // Check lock
        try {
            $this->validateNoLock();
            $checks['lock'] = ['status' => 'pass', 'message' => 'No update in progress'];
        } catch (UpdateInProgressException $e) {
            $checks['lock'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }

        // Check exec
        if ($this->isExecAvailable()) {
            $checks['exec'] = ['status' => 'pass', 'message' => 'Background execution available'];
        } else {
            $checks['exec'] = ['status' => 'warn', 'message' => 'exec() disabled - will try queue or manual CLI'];
        }

        // Check queue
        if ($this->isQueueAvailable()) {
            $checks['queue'] = ['status' => 'pass', 'message' => 'Queue available ('.config('queue.default').')'];
        } else {
            $checks['queue'] = ['status' => 'warn', 'message' => 'Queue set to sync - background jobs not available'];
        }

        return $checks;
    }
}
