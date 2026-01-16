<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Exceptions\UpdateException;
use TallCms\Cms\Services\TallCmsUpdater;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TallCmsUpdate extends Command
{
    protected $signature = 'tallcms:update
                            {--target= : Specific version to update to (default: latest)}
                            {--force : Skip confirmation prompts}
                            {--skip-backup : Skip file and database backups}
                            {--skip-db-backup : Skip database backup only}
                            {--dry-run : Show what would happen without making changes}';

    protected $description = 'Update TallCMS to the latest version';

    private TallCmsUpdater $updater;

    private bool $dryRun = false;

    public function handle(TallCmsUpdater $updater): int
    {
        $this->updater = $updater;
        $this->dryRun = $this->option('dry-run');
        $dryRun = $this->dryRun;

        $this->newLine();
        $this->components->info($dryRun ? 'TallCMS Updater (DRY RUN)' : 'TallCMS Updater');
        if ($dryRun) {
            $this->components->warn('No changes will be made.');
        }
        $this->newLine();

        try {
            // Step 1: Preflight checks
            $this->updateStep('preflight', 'Running preflight checks...');

            $updater->verifySodiumAvailable();
            $updater->validateDiskSpace();
            $updater->validateNoLock();

            // Step 2: Check for updates
            $this->updateStep('checking', 'Checking for updates...');

            $currentVersion = config('tallcms.version');
            $targetVersion = $this->option('target');
            $release = null;

            if ($targetVersion) {
                // Fetch specific release
                $release = $updater->fetchRelease($targetVersion);
                if (! $release) {
                    $this->error("Could not fetch release {$targetVersion}.");

                    return 1;
                }
                $targetVersion = $release['version'] ?: ltrim($targetVersion, 'v');
                $this->components->twoColumnDetail('Target Version', $targetVersion);
            } else {
                // Fetch latest release
                $release = $updater->checkForUpdates();

                if (! $release) {
                    $this->error('Could not fetch latest release information.');

                    return 1;
                }

                $targetVersion = $release['version'];
                $this->components->twoColumnDetail('Current Version', $currentVersion);
                $this->components->twoColumnDetail('Latest Version', $targetVersion);

                if (version_compare($targetVersion, $currentVersion, '<=')) {
                    $this->components->info('You are already running the latest version.');

                    return 0;
                }
            }

            $updater->setTargetVersion($targetVersion);

            // Step 3: Confirm update
            if (! $this->option('force') && ! $this->option('no-interaction')) {
                if (! $this->confirm("Update from {$currentVersion} to {$targetVersion}?", true)) {
                    $this->info('Update cancelled.');

                    return 0;
                }
            }

            // Acquire lock (skip in dry-run)
            if (! $dryRun) {
                $updater->acquireLock();

                $updater->updateState([
                    'status' => 'in_progress',
                    'version' => $targetVersion,
                    'started_at' => now()->toIso8601String(),
                    'current_step' => 'downloading',
                    'steps' => [],
                ]);
            }

            // Step 4: Download release
            $this->updateStep('downloading', 'Downloading release...');

            $files = $updater->downloadRelease($release);

            // Find the downloaded files
            $zipFile = null;
            $checksumsFile = null;
            $signatureFile = null;

            foreach ($files as $name => $path) {
                if (str_ends_with($name, '.zip')) {
                    $zipFile = $path;
                } elseif ($name === 'checksums.json') {
                    $checksumsFile = $path;
                } elseif ($name === 'checksums.json.sig') {
                    $signatureFile = $path;
                }
            }

            if (! $zipFile || ! $checksumsFile || ! $signatureFile) {
                throw new UpdateException('Missing required release files');
            }

            // Step 5: Verify signature
            $this->updateStep('verifying', 'Verifying release signature...');

            $updater->verifyRelease($zipFile, $checksumsFile, $signatureFile);
            $this->components->info('Signature verified.');

            // Step 6: Backup files
            if (! $this->option('skip-backup') && ! $dryRun) {
                $this->updateStep('backup_files', 'Backing up files...');
                $backupPath = $updater->backupFiles();
                if ($backupPath) {
                    $this->components->info("Files backed up to: {$backupPath}");
                } else {
                    $this->warn('File backup skipped (no critical files to backup).');
                }
            } elseif ($dryRun) {
                $this->components->twoColumnDetail('File backup', 'Would create backup');
            }

            // Step 7: Backup database
            $requireDbBackup = config('tallcms.updates.require_db_backup', true);
            $skipDbBackup = $this->option('skip-backup') || $this->option('skip-db-backup');

            if (! $skipDbBackup && ! $dryRun) {
                $this->updateStep('backup_database', 'Backing up database...');

                $dbCapability = $updater->checkDatabaseBackupCapability();
                if ($dbCapability['capable']) {
                    $backupPath = $updater->backupDatabase();
                    if ($backupPath) {
                        $this->components->info("Database backed up to: {$backupPath}");
                    } elseif ($requireDbBackup) {
                        throw new UpdateException('Database backup failed and require_db_backup is enabled. Use --skip-db-backup to override.');
                    } else {
                        $this->warn('Database backup failed but continuing...');
                    }
                } elseif ($requireDbBackup) {
                    throw new UpdateException('Database backup not available: '.($dbCapability['warning'] ?? 'Unknown reason').'. Use --skip-db-backup to override.');
                } else {
                    $this->warn($dbCapability['warning'] ?? 'Database backup not available.');
                }
            } elseif ($dryRun) {
                $dbCapability = $updater->checkDatabaseBackupCapability();
                $this->components->twoColumnDetail('Database backup', $dbCapability['capable'] ? 'Would backup via '.$dbCapability['method'] : 'Not available');
            }

            // Step 8: Extract to temp directory
            $this->updateStep('extracting', 'Extracting release...');

            $extractDir = storage_path('app/tallcms-update-'.uniqid());
            mkdir($extractDir, 0755, true);

            $updater->extractSafely($zipFile, $extractDir);

            // Step 9: Validate release marker
            $this->updateStep('validating', 'Validating release...');

            $marker = $updater->validateReleaseMarker($extractDir);
            $warnings = $updater->validatePlatformCompatibility($marker);

            foreach ($warnings as $warning) {
                $this->warn($warning);
            }

            // Step 10: Detect changes
            $this->updateStep('analyzing', 'Analyzing changes...');

            $checksums = json_decode(file_get_contents($checksumsFile), true);
            $installed = $updater->getInstalledManifest() ?? ['files' => []];
            $changes = $updater->detectChanges($installed, $checksums);

            $this->components->twoColumnDetail('Files to add', (string) count($changes['added']));
            $this->components->twoColumnDetail('Files modified locally', (string) count($changes['modified']));
            $this->components->twoColumnDetail('Files to quarantine', (string) count($changes['quarantine']));
            $this->components->twoColumnDetail('Conflicts', (string) count($changes['conflicts']));

            // Step 11: Handle quarantine confirmation
            if (! empty($changes['quarantine']) || ! empty($changes['conflicts'])) {
                $this->newLine();
                $this->warn($dryRun ? 'Files that would be moved to quarantine:' : 'The following files will be moved to quarantine:');
                foreach (array_merge($changes['quarantine'], $changes['conflicts']) as $file) {
                    $this->line("  - {$file}");
                }

                if (! $dryRun) {
                    if (! $this->option('force') && ! $this->option('no-interaction')) {
                        if (! $this->confirm('Continue with update?', true)) {
                            $this->info('Update cancelled.');
                            $this->cleanup($extractDir);
                            $updater->clearLock();

                            return 0;
                        }
                    }

                    $updater->quarantineFiles(array_merge($changes['quarantine'], $changes['conflicts']));
                }
            }

            // Dry run ends here
            if ($dryRun) {
                $this->newLine();
                $this->components->info('Dry run complete. No changes were made.');
                $this->newLine();
                $this->components->twoColumnDetail('Would update to', $targetVersion);
                $this->components->twoColumnDetail('Files to add', (string) count($changes['added']));
                $this->components->twoColumnDetail('Files to update', (string) (count($checksums['files'] ?? []) - count($changes['added'])));
                $this->components->twoColumnDetail('Files to quarantine', (string) (count($changes['quarantine']) + count($changes['conflicts'])));
                $this->newLine();
                $this->info('Run without --dry-run to apply the update.');
                $this->cleanup($extractDir);

                return 0;
            }

            // Step 12: Apply update
            $this->updateStep('applying', 'Applying update...');

            // Copy files from extract directory to base path
            // Skip preserved paths
            $this->applyUpdate($extractDir, base_path());

            // Step 13: Run migrations
            $this->updateStep('migrating', 'Running migrations...');

            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());

            // Step 14: Clear caches
            $this->updateStep('clearing_cache', 'Clearing caches...');

            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // Step 15: Save manifest
            $updater->saveInstalledManifest($checksums);

            // Cleanup
            $this->cleanup($extractDir);
            $updater->clearLock();

            // Mark the final step as completed and update overall status
            $state = $updater->getUpdateState();
            $steps = $state['steps'] ?? [];
            foreach ($steps as &$s) {
                if (($s['status'] ?? '') === 'in_progress') {
                    $s['status'] = 'completed';
                    $s['completed_at'] = now()->toIso8601String();
                }
            }
            unset($s);

            $updater->updateState([
                'status' => 'completed',
                'completed_at' => now()->toIso8601String(),
                'steps' => $steps,
            ]);

            $this->newLine();
            $this->components->info("Successfully updated to TallCMS {$targetVersion}!");

            return 0;
        } catch (\Throwable $e) {
            Log::error('TallCmsUpdate: Update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('Update failed: '.$e->getMessage());

            if (! $this->dryRun) {
                // Mark the current step as failed and update overall status
                $state = $updater->getUpdateState();
                $steps = $state['steps'] ?? [];
                foreach ($steps as &$s) {
                    if (($s['status'] ?? '') === 'in_progress') {
                        $s['status'] = 'failed';
                        $s['failed_at'] = now()->toIso8601String();
                        $s['error'] = $e->getMessage();
                    }
                }
                unset($s);

                $updater->updateState([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'steps' => $steps,
                ]);

                $updater->clearLock();
            }

            return 1;
        }
    }

    /**
     * Update the current step in state and output.
     */
    private function updateStep(string $step, string $message): void
    {
        if (! $this->dryRun) {
            // Mark previous step as completed and set new step as in_progress
            $state = $this->updater->getUpdateState();
            $steps = $state['steps'] ?? [];

            // Mark any in_progress step as completed
            foreach ($steps as &$s) {
                if (($s['status'] ?? '') === 'in_progress') {
                    $s['status'] = 'completed';
                    $s['completed_at'] = now()->toIso8601String();
                }
            }
            unset($s);

            // Add new step as in_progress
            $steps[] = [
                'name' => $step,
                'status' => 'in_progress',
                'started_at' => now()->toIso8601String(),
            ];

            $this->updater->updateState([
                'current_step' => $step,
                'steps' => $steps,
            ]);
        }
        $this->components->task($message, fn () => true);
    }

    /**
     * Apply the update by copying files.
     */
    private function applyUpdate(string $sourceDir, string $targetDir): void
    {
        $preservedPaths = [
            '.env',
            '.env.backup',
            'storage/',
            'themes/',
            'plugins/',
            'database/database.sqlite',
            'public/storage',
            'public/themes/',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($sourceDir) + 1);

            // Check if this path should be preserved
            $shouldPreserve = false;
            foreach ($preservedPaths as $preserved) {
                if (str_starts_with($relativePath, $preserved)) {
                    $shouldPreserve = true;
                    break;
                }
            }

            if ($shouldPreserve) {
                continue;
            }

            $targetPath = $targetDir.'/'.$relativePath;

            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                // Create .bak for modified files
                if (file_exists($targetPath)) {
                    $oldHash = hash_file('sha256', $targetPath);
                    $newHash = hash_file('sha256', $item->getPathname());

                    if ($oldHash !== $newHash) {
                        copy($targetPath, $targetPath.'.bak');
                    }
                }

                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Clean up temporary files.
     */
    private function cleanup(string $extractDir): void
    {
        if (is_dir($extractDir)) {
            $this->deleteDirectory($extractDir);
        }
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
