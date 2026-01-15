<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use TallCms\Cms\Events\PluginInstalled;
use TallCms\Cms\Events\PluginInstalling;
use TallCms\Cms\Events\PluginUninstalled;
use TallCms\Cms\Events\PluginUninstalling;
use TallCms\Cms\Models\Plugin;
use ZipArchive;

class PluginManager
{
    protected ?Collection $discoveredPlugins = null;

    protected array $bootedProviders = [];

    /**
     * Get the plugins directory path
     */
    public function getPluginsPath(): string
    {
        return config('tallcms.plugin_mode.plugins_path') ?? base_path('plugins');
    }

    /**
     * Get the path for a specific plugin
     */
    public function getPluginPath(string $vendor, string $slug): string
    {
        return $this->getPluginsPath()."/{$vendor}/{$slug}";
    }

    /**
     * Get all installed plugins
     */
    public function getInstalledPlugins(): Collection
    {
        if ($this->discoveredPlugins !== null) {
            return $this->discoveredPlugins;
        }

        if ($this->isCacheEnabled()) {
            $cached = Cache::get($this->getCacheKey());
            if ($cached !== null) {
                $this->discoveredPlugins = $this->pruneMissingPlugins($cached);

                return $this->discoveredPlugins;
            }
        }

        $this->discoveredPlugins = $this->discover();

        if ($this->isCacheEnabled()) {
            Cache::put(
                $this->getCacheKey(),
                $this->discoveredPlugins,
                config('plugin.cache_ttl', 3600)
            );
        }

        return $this->discoveredPlugins;
    }

    /**
     * Discover all plugins from the filesystem
     */
    public function discover(): Collection
    {
        $pluginsPath = $this->getPluginsPath();

        if (! File::exists($pluginsPath)) {
            return collect();
        }

        $plugins = collect();

        // Scan vendor directories
        foreach (File::directories($pluginsPath) as $vendorDir) {
            $vendor = basename($vendorDir);

            // Skip hidden directories
            if (str_starts_with($vendor, '.')) {
                continue;
            }

            // Scan plugin directories within each vendor
            foreach (File::directories($vendorDir) as $pluginDir) {
                $plugin = Plugin::fromDirectory($pluginDir);
                if ($plugin) {
                    $plugins->push($plugin);
                }
            }
        }

        return $plugins->sortBy(fn ($plugin) => $plugin->getFullSlug());
    }

    /**
     * Prune plugins that no longer exist on disk
     */
    protected function pruneMissingPlugins(Collection $plugins): Collection
    {
        return $plugins->filter(function ($plugin) {
            return File::exists($plugin->path) && File::exists($plugin->path.'/plugin.json');
        })->values();
    }

    /**
     * Find a plugin by vendor and slug
     */
    public function find(string $vendor, string $slug): ?Plugin
    {
        return $this->getInstalledPlugins()->first(function ($plugin) use ($vendor, $slug) {
            return $plugin->vendor === $vendor && $plugin->slug === $slug;
        });
    }

    /**
     * Find a plugin by full slug (vendor/slug)
     */
    public function findByFullSlug(string $fullSlug): ?Plugin
    {
        $parts = explode('/', $fullSlug, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return $this->find($parts[0], $parts[1]);
    }

    /**
     * Check if a plugin is installed
     */
    public function isInstalled(string $vendor, string $slug): bool
    {
        return $this->find($vendor, $slug) !== null;
    }

    /**
     * Refresh the plugin cache
     */
    public function refreshCache(): Collection
    {
        $this->clearCache();
        $this->discoveredPlugins = null;

        return $this->getInstalledPlugins();
    }

    /**
     * Clear the plugin cache
     */
    public function clearCache(): bool
    {
        $this->discoveredPlugins = null;

        return Cache::forget($this->getCacheKey());
    }

    /**
     * Check if caching is enabled
     */
    protected function isCacheEnabled(): bool
    {
        return config('plugin.cache_enabled', true);
    }

    /**
     * Get the cache key for discovered plugins
     */
    protected function getCacheKey(): string
    {
        return 'plugins.discovered';
    }

    /**
     * Ensure the plugins directory exists
     */
    public function ensurePluginsDirectoryExists(): void
    {
        $path = $this->getPluginsPath();

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Get plugins grouped by vendor
     */
    public function getPluginsGroupedByVendor(): Collection
    {
        return $this->getInstalledPlugins()->groupBy('vendor');
    }

    /**
     * Get plugins by tag
     */
    public function getPluginsByTag(string $tag): Collection
    {
        return $this->getInstalledPlugins()->filter(function ($plugin) use ($tag) {
            return in_array($tag, $plugin->tags);
        });
    }

    /**
     * Register PSR-4 autoloading for a plugin
     */
    public function registerAutoload(Plugin $plugin): void
    {
        if (empty($plugin->namespace)) {
            return;
        }

        $srcPath = $plugin->getSrcPath();

        if (! File::exists($srcPath)) {
            return;
        }

        $loader = require base_path('vendor/autoload.php');
        $loader->addPsr4($plugin->namespace.'\\', $srcPath);
    }

    /**
     * Blocked namespace prefixes that plugins cannot use
     * These are reserved for the application and core Laravel functionality
     */
    protected const BLOCKED_NAMESPACE_PREFIXES = [
        'App',
        'Database',
        'Tests',
        'Illuminate',
        'Laravel',
        'Filament',
        'Livewire',
        'Spatie',
    ];

    /**
     * Check if a plugin namespace is managed by Composer or conflicts with app namespaces
     * This prevents ZIP installs from colliding with Composer-installed packages or app classes
     */
    public function isComposerManaged(string $namespace): bool
    {
        $checkNamespace = $this->normalizeNamespace($namespace);

        // Check against blocked namespace prefixes (security: prevent class shadowing)
        // Case-insensitive comparison to catch App\, app\, APP\, etc.
        foreach (self::BLOCKED_NAMESPACE_PREFIXES as $blocked) {
            $blockedLower = strtolower($blocked);
            $checkLower = strtolower($checkNamespace);
            if ($checkLower === $blockedLower || str_starts_with($checkLower, $blockedLower.'\\')) {
                return true;
            }
        }

        // Check root composer.json autoload (app's own namespaces)
        if ($this->isInRootComposerAutoload($checkNamespace)) {
            return true;
        }

        // Check composer.lock for installed packages
        if ($this->isInComposerLock($checkNamespace)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a namespace for comparison
     * Removes leading backslash and trailing backslash
     */
    protected function normalizeNamespace(string $namespace): string
    {
        // Remove leading backslash
        $namespace = ltrim($namespace, '\\');
        // Remove trailing backslash
        $namespace = rtrim($namespace, '\\');

        return $namespace;
    }

    /**
     * Check if namespace conflicts with root composer.json autoload
     */
    protected function isInRootComposerAutoload(string $namespace): bool
    {
        $composerJson = base_path('composer.json');

        if (! File::exists($composerJson)) {
            return false;
        }

        try {
            $composerData = json_decode(File::get($composerJson), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Normalize input namespace for case-insensitive comparison
            $namespaceLower = strtolower($this->normalizeNamespace($namespace));

            // Check autoload and autoload-dev sections
            $autoloadSections = [
                $composerData['autoload'] ?? [],
                $composerData['autoload-dev'] ?? [],
            ];

            foreach ($autoloadSections as $autoload) {
                // Check PSR-4
                foreach ($autoload['psr-4'] ?? [] as $prefix => $path) {
                    $prefixLower = strtolower($this->normalizeNamespace($prefix));
                    if ($prefixLower === $namespaceLower || str_starts_with($namespaceLower, $prefixLower.'\\')) {
                        return true;
                    }
                }

                // Check PSR-0
                foreach ($autoload['psr-0'] ?? [] as $prefix => $path) {
                    $prefixLower = strtolower($this->normalizeNamespace($prefix));
                    if ($prefixLower === $namespaceLower || str_starts_with($namespaceLower, $prefixLower.'\\')) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('Failed to check composer.json for namespace collision', [
                'namespace' => $namespace,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if namespace conflicts with composer.lock packages
     */
    protected function isInComposerLock(string $namespace): bool
    {
        $composerLock = base_path('composer.lock');

        if (! File::exists($composerLock)) {
            return false;
        }

        try {
            $lockData = json_decode(File::get($composerLock), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Normalize input namespace for case-insensitive comparison
            $namespaceLower = strtolower($this->normalizeNamespace($namespace));

            // Check both packages and packages-dev
            $packages = array_merge(
                $lockData['packages'] ?? [],
                $lockData['packages-dev'] ?? []
            );

            foreach ($packages as $package) {
                $autoload = $package['autoload'] ?? [];

                // Check PSR-4 autoload entries
                foreach ($autoload['psr-4'] ?? [] as $prefix => $path) {
                    $prefixLower = strtolower($this->normalizeNamespace($prefix));
                    if ($prefixLower === $namespaceLower || str_starts_with($namespaceLower, $prefixLower.'\\')) {
                        return true;
                    }
                }

                // Check PSR-0 autoload entries
                foreach ($autoload['psr-0'] ?? [] as $prefix => $path) {
                    $prefixLower = strtolower($this->normalizeNamespace($prefix));
                    if ($prefixLower === $namespaceLower || str_starts_with($namespaceLower, $prefixLower.'\\')) {
                        return true;
                    }
                }
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('Failed to check composer.lock for namespace collision', [
                'namespace' => $namespace,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Boot a plugin's service provider
     */
    public function bootPlugin(Plugin $plugin): void
    {
        $fullSlug = $plugin->getFullSlug();

        // Don't boot twice
        if (isset($this->bootedProviders[$fullSlug])) {
            return;
        }

        if (empty($plugin->provider)) {
            return;
        }

        if (! class_exists($plugin->provider)) {
            Log::warning("Plugin provider class not found: {$plugin->provider}", [
                'plugin' => $fullSlug,
            ]);

            return;
        }

        try {
            $provider = app()->register($plugin->provider);
            $this->bootedProviders[$fullSlug] = $provider;
        } catch (\Throwable $e) {
            Log::error("Failed to boot plugin provider: {$plugin->provider}", [
                'plugin' => $fullSlug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all booted providers
     */
    public function getBootedProviders(): array
    {
        return $this->bootedProviders;
    }

    /**
     * Get Filament plugin instances from installed plugins
     */
    public function getFilamentPlugins(): array
    {
        $filamentPlugins = [];

        foreach ($this->getInstalledPlugins() as $plugin) {
            if (! $plugin->hasFilamentPlugin()) {
                continue;
            }

            if (! class_exists($plugin->filamentPlugin)) {
                Log::warning("Filament plugin class not found: {$plugin->filamentPlugin}", [
                    'plugin' => $plugin->getFullSlug(),
                ]);

                continue;
            }

            try {
                $filamentPlugins[] = app($plugin->filamentPlugin);
            } catch (\Throwable $e) {
                Log::error("Failed to instantiate Filament plugin: {$plugin->filamentPlugin}", [
                    'plugin' => $plugin->getFullSlug(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $filamentPlugins;
    }

    /**
     * Check if uploads are allowed
     */
    public function uploadsAllowed(): bool
    {
        return config('plugin.allow_uploads', true);
    }

    /**
     * Get maximum upload size in bytes
     */
    public function getMaxUploadSize(): int
    {
        return config('plugin.max_upload_size', 50 * 1024 * 1024);
    }

    /**
     * Install a plugin from a ZIP file
     */
    public function installFromZip(string $zipPath): PluginInstallResult
    {
        $validator = app(PluginValidator::class);

        // Validate the ZIP file
        $validationResult = $validator->validateZip($zipPath);

        if (! $validationResult->isValid) {
            return PluginInstallResult::failed($validationResult->errors);
        }

        $pluginData = $validationResult->pluginData;
        $vendor = $pluginData['vendor'];
        $slug = $pluginData['slug'];

        // Check if plugin already exists
        if ($this->isInstalled($vendor, $slug)) {
            return PluginInstallResult::failed(["Plugin {$vendor}/{$slug} is already installed. Use update to replace it."]);
        }

        // Check for Composer namespace collision
        $namespace = $pluginData['namespace'] ?? '';
        if ($namespace && $this->isComposerManaged($namespace)) {
            return PluginInstallResult::failed([
                "Cannot install plugin: namespace '{$namespace}' is already managed by Composer. ".
                'ZIP-based plugins cannot override Composer-installed packages.',
            ]);
        }

        // Extract to temp directory first
        $tempPath = storage_path("app/plugin-temp/{$vendor}-{$slug}-".uniqid());

        try {
            $this->extractZip($zipPath, $tempPath, $pluginData);

            // Validate extracted directory
            $dirValidation = $validator->validateDirectory($tempPath);
            if (! $dirValidation->isValid) {
                File::deleteDirectory($tempPath);

                return PluginInstallResult::failed($dirValidation->errors);
            }

            // Atomic move to final location
            $finalPath = $this->getPluginPath($vendor, $slug);

            // Ensure vendor directory exists
            File::ensureDirectoryExists(dirname($finalPath), 0755);

            // Move from temp to final location
            if (! File::moveDirectory($tempPath, $finalPath)) {
                // Cleanup temp directory if move failed
                if (File::exists($tempPath)) {
                    File::deleteDirectory($tempPath);
                }

                return PluginInstallResult::failed(['Failed to move plugin to final location. Check directory permissions.']);
            }

            // Load the plugin
            $plugin = Plugin::fromDirectory($finalPath);

            if (! $plugin) {
                File::deleteDirectory($finalPath);

                return PluginInstallResult::failed(['Failed to load plugin after extraction']);
            }

            // Fire installing event
            event(new PluginInstalling($plugin, 'upload'));

            // Register autoloader
            $this->registerAutoload($plugin);

            // Validate classes exist
            $classValidation = $validator->validateClassesExist($plugin);
            if (! $classValidation->isValid) {
                File::deleteDirectory($finalPath);

                return PluginInstallResult::failed($classValidation->errors);
            }

            // Validate provider doesn't contain Route:: calls (security check)
            $routeErrors = $validator->scanProviderForRoutes($plugin);
            if (! empty($routeErrors)) {
                File::deleteDirectory($finalPath);

                return PluginInstallResult::failed($routeErrors);
            }

            // Run migrations if auto_migrate is enabled
            $migrationsRan = [];
            if (config('plugin.auto_migrate', true)) {
                $migrator = app(PluginMigrator::class);
                $migrationResult = $migrator->migrate($plugin);

                if (! $migrationResult->success) {
                    // Rollback: delete plugin directory
                    File::deleteDirectory($finalPath);

                    return PluginInstallResult::failed(
                        array_merge(['Migration failed:'], $migrationResult->errors)
                    );
                }

                $migrationsRan = $migrationResult->migrations;
            }

            // Clear all caches
            $this->clearAllCaches();

            // Fire installed event
            event(new PluginInstalled($plugin, $migrationsRan, 'upload'));

            Log::info("Plugin installed: {$plugin->getFullSlug()}", [
                'version' => $plugin->version,
                'migrations' => $migrationsRan,
            ]);

            return PluginInstallResult::success(
                $plugin,
                $migrationsRan,
                $validationResult->warnings
            );

        } catch (\Throwable $e) {
            // Cleanup
            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }

            $finalPath = $this->getPluginPath($vendor, $slug);
            if (File::exists($finalPath)) {
                File::deleteDirectory($finalPath);
            }

            Log::error("Plugin installation failed: {$vendor}/{$slug}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PluginInstallResult::failed([$e->getMessage()]);
        }
    }

    /**
     * Uninstall a plugin
     */
    public function uninstall(string $vendor, string $slug): PluginInstallResult
    {
        $plugin = $this->find($vendor, $slug);

        if (! $plugin) {
            return PluginInstallResult::failed(["Plugin {$vendor}/{$slug} is not installed"]);
        }

        try {
            // Fire uninstalling event
            event(new PluginUninstalling($plugin));

            // Rollback migrations
            $migrationsRolledBack = [];
            $migrator = app(PluginMigrator::class);
            $rollbackResult = $migrator->rollback($plugin);

            if (! $rollbackResult->success) {
                Log::warning("Plugin migration rollback had errors: {$vendor}/{$slug}", [
                    'errors' => $rollbackResult->errors,
                ]);
            }

            $migrationsRolledBack = $rollbackResult->migrations;

            // Delete plugin directory
            File::deleteDirectory($plugin->path);

            // Clear all caches
            $this->clearAllCaches();

            // Fire uninstalled event
            event(new PluginUninstalled($plugin, $migrationsRolledBack));

            Log::info("Plugin uninstalled: {$plugin->getFullSlug()}", [
                'migrations_rolled_back' => $migrationsRolledBack,
            ]);

            return PluginInstallResult::success(
                $plugin,
                $migrationsRolledBack,
                [],
                'Plugin uninstalled successfully'
            );

        } catch (\Throwable $e) {
            Log::error("Plugin uninstallation failed: {$vendor}/{$slug}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PluginInstallResult::failed([$e->getMessage()]);
        }
    }

    /**
     * Update a plugin from a ZIP file (in-place update)
     */
    public function update(string $zipPath): PluginInstallResult
    {
        $validator = app(PluginValidator::class);

        // Validate the ZIP file
        $validationResult = $validator->validateZip($zipPath);

        if (! $validationResult->isValid) {
            return PluginInstallResult::failed($validationResult->errors);
        }

        $pluginData = $validationResult->pluginData;
        $vendor = $pluginData['vendor'];
        $slug = $pluginData['slug'];

        // Check if plugin exists
        $existingPlugin = $this->find($vendor, $slug);
        if (! $existingPlugin) {
            return PluginInstallResult::failed([
                "Plugin {$vendor}/{$slug} is not installed. Use install for new plugins.",
            ]);
        }

        // Check license for plugins that require it
        if ($existingPlugin->requiresLicense()) {
            $licenseService = app(PluginLicenseService::class);
            if (! $licenseService->isValid($existingPlugin->getLicenseSlug())) {
                $status = $licenseService->getStatus($existingPlugin->getLicenseSlug());
                $purchaseUrl = $status['purchase_url'] ?? null;

                $message = 'A valid license is required to update this plugin.';
                if ($status['status'] === 'expired') {
                    $message = 'Your license has expired. Please renew your license to download updates.';
                } elseif ($status['status'] === 'none' || ! $status['has_license']) {
                    $message = 'Please purchase and activate a license to download updates.';
                }

                if ($purchaseUrl) {
                    $message .= " Visit: {$purchaseUrl}";
                }

                return PluginInstallResult::failed([$message]);
            }
        }

        // Enforce version upgrade (block downgrades and same-version updates)
        $newVersion = $pluginData['version'] ?? '0.0.0';
        $currentVersion = $existingPlugin->version;

        if (version_compare($newVersion, $currentVersion, '<=')) {
            return PluginInstallResult::failed([
                "Cannot update to version {$newVersion}. Current version is {$currentVersion}. Only upgrades are allowed.",
            ]);
        }

        // Check for Composer namespace collision (in case existing plugin was file-based but new one conflicts)
        $namespace = $pluginData['namespace'] ?? '';
        if ($namespace && $this->isComposerManaged($namespace)) {
            return PluginInstallResult::failed([
                "Cannot update plugin: namespace '{$namespace}' is managed by Composer. ".
                'ZIP-based plugins cannot override Composer-installed packages.',
            ]);
        }

        // Create backup
        $backupPath = $this->createBackup($existingPlugin);

        // Extract to temp directory
        $tempPath = storage_path("app/plugin-temp/{$vendor}-{$slug}-".uniqid());

        try {
            $this->extractZip($zipPath, $tempPath, $pluginData);

            // Validate extracted directory
            $dirValidation = $validator->validateDirectory($tempPath);
            if (! $dirValidation->isValid) {
                File::deleteDirectory($tempPath);
                $this->deleteBackup($backupPath);

                return PluginInstallResult::failed($dirValidation->errors);
            }

            // Delete existing plugin directory
            $finalPath = $existingPlugin->path;
            File::deleteDirectory($finalPath);

            // Move new version to final location
            if (! File::moveDirectory($tempPath, $finalPath)) {
                // Restore from backup since the move failed
                if (File::exists($tempPath)) {
                    File::deleteDirectory($tempPath);
                }
                $this->restoreFromBackup($backupPath, $finalPath);
                $this->deleteBackup($backupPath);

                return PluginInstallResult::failed(['Failed to move updated plugin to final location. Original plugin restored from backup.']);
            }

            // Load the updated plugin
            $plugin = Plugin::fromDirectory($finalPath);

            if (! $plugin) {
                // Restore from backup
                $this->restoreFromBackup($backupPath, $finalPath);
                $this->deleteBackup($backupPath);

                return PluginInstallResult::failed(['Failed to load plugin after update']);
            }

            // Register autoloader with new files
            $this->registerAutoload($plugin);

            // Validate classes exist
            $classValidation = $validator->validateClassesExist($plugin);
            if (! $classValidation->isValid) {
                // Restore from backup
                File::deleteDirectory($finalPath);
                $this->restoreFromBackup($backupPath, $finalPath);
                $this->deleteBackup($backupPath);

                return PluginInstallResult::failed($classValidation->errors);
            }

            // Validate provider doesn't contain Route:: calls (security check)
            $routeErrors = $validator->scanProviderForRoutes($plugin);
            if (! empty($routeErrors)) {
                // Restore from backup
                File::deleteDirectory($finalPath);
                $this->restoreFromBackup($backupPath, $finalPath);
                $this->deleteBackup($backupPath);

                return PluginInstallResult::failed($routeErrors);
            }

            // Run any new migrations
            $migrationsRan = [];
            if (config('plugin.auto_migrate', true)) {
                $migrator = app(PluginMigrator::class);
                $migrationResult = $migrator->migrate($plugin);

                if (! $migrationResult->success) {
                    // Restore from backup
                    File::deleteDirectory($finalPath);
                    $this->restoreFromBackup($backupPath, $finalPath);
                    $this->deleteBackup($backupPath);

                    return PluginInstallResult::failed(
                        array_merge(['Migration failed during update:'], $migrationResult->errors)
                    );
                }

                $migrationsRan = $migrationResult->migrations;
            }

            // Clear all caches
            $this->clearAllCaches();

            // Keep backup for rollback (with timestamp)
            $this->archiveBackup($backupPath, $vendor, $slug, $existingPlugin->version);

            Log::info("Plugin updated: {$plugin->getFullSlug()}", [
                'old_version' => $existingPlugin->version,
                'new_version' => $plugin->version,
                'migrations' => $migrationsRan,
            ]);

            return PluginInstallResult::success(
                $plugin,
                $migrationsRan,
                $validationResult->warnings,
                "Updated from {$existingPlugin->version} to {$plugin->version}"
            );

        } catch (\Throwable $e) {
            // Cleanup temp directory
            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }

            // Try to restore from backup if plugin directory was deleted
            $finalPath = $this->getPluginPath($vendor, $slug);
            if (! File::exists($finalPath) && File::exists($backupPath)) {
                $this->restoreFromBackup($backupPath, $finalPath);
            }

            $this->deleteBackup($backupPath);

            Log::error("Plugin update failed: {$vendor}/{$slug}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PluginInstallResult::failed([$e->getMessage()]);
        }
    }

    /**
     * Create a backup of a plugin
     */
    protected function createBackup(Plugin $plugin): string
    {
        $backupPath = storage_path("app/plugin-temp/backup-{$plugin->vendor}-{$plugin->slug}-".uniqid());

        File::copyDirectory($plugin->path, $backupPath);

        return $backupPath;
    }

    /**
     * Restore a plugin from backup
     *
     * @throws \RuntimeException If restore fails
     */
    protected function restoreFromBackup(string $backupPath, string $targetPath): void
    {
        if (File::exists($targetPath)) {
            File::deleteDirectory($targetPath);
        }

        File::ensureDirectoryExists(dirname($targetPath), 0755);

        if (! File::moveDirectory($backupPath, $targetPath)) {
            throw new \RuntimeException("Failed to restore plugin from backup. Backup remains at: {$backupPath}");
        }
    }

    /**
     * Delete a backup directory
     */
    protected function deleteBackup(string $backupPath): void
    {
        if (File::exists($backupPath)) {
            File::deleteDirectory($backupPath);
        }
    }

    /**
     * Archive a backup for potential rollback
     */
    protected function archiveBackup(string $backupPath, string $vendor, string $slug, string $version): void
    {
        $archivePath = $this->getBackupPath($vendor, $slug);
        $versionPath = "{$archivePath}/{$version}_".date('Y-m-d_His');

        File::ensureDirectoryExists($archivePath, 0755);

        if (File::exists($backupPath)) {
            if (! File::moveDirectory($backupPath, $versionPath)) {
                Log::warning("Failed to archive backup for {$vendor}/{$slug}", [
                    'backup_path' => $backupPath,
                    'version' => $version,
                ]);
                // Clean up the backup since we couldn't archive it
                File::deleteDirectory($backupPath);

                return;
            }
        }

        // Clean old backups (keep last 3)
        $this->cleanOldBackups($archivePath, 3);
    }

    /**
     * Clean old backups, keeping the most recent ones
     */
    protected function cleanOldBackups(string $archivePath, int $keep = 3): void
    {
        if (! File::exists($archivePath)) {
            return;
        }

        $backups = collect(File::directories($archivePath))
            ->sortByDesc(fn ($path) => File::lastModified($path))
            ->values();

        foreach ($backups->skip($keep) as $oldBackup) {
            File::deleteDirectory($oldBackup);
        }
    }

    /**
     * Rollback a plugin to a previous version
     */
    public function rollback(string $vendor, string $slug, ?string $version = null): PluginInstallResult
    {
        $plugin = $this->find($vendor, $slug);

        if (! $plugin) {
            return PluginInstallResult::failed(["Plugin {$vendor}/{$slug} is not installed"]);
        }

        $archivePath = $this->getBackupPath($vendor, $slug);

        if (! File::exists($archivePath)) {
            return PluginInstallResult::failed(['No backups available for rollback']);
        }

        $backups = collect(File::directories($archivePath))
            ->sortByDesc(fn ($path) => File::lastModified($path))
            ->values();

        if ($backups->isEmpty()) {
            return PluginInstallResult::failed(['No backups available for rollback']);
        }

        // Find the backup to restore
        $backupToRestore = null;

        if ($version) {
            // Find specific version
            $backupToRestore = $backups->first(fn ($path) => str_starts_with(basename($path), "{$version}_"));

            if (! $backupToRestore) {
                return PluginInstallResult::failed(["Backup for version {$version} not found"]);
            }
        } else {
            // Use most recent backup
            $backupToRestore = $backups->first();
        }

        try {
            $currentVersion = $plugin->version;

            // Create backup of current version first
            $currentBackup = $this->createBackup($plugin);

            // Delete current plugin
            File::deleteDirectory($plugin->path);

            // Restore from backup
            File::copyDirectory($backupToRestore, $plugin->path);

            // Reload plugin
            $restoredPlugin = Plugin::fromDirectory($plugin->path);

            if (! $restoredPlugin) {
                // Restore current version
                $this->restoreFromBackup($currentBackup, $plugin->path);

                return PluginInstallResult::failed(['Failed to load plugin after rollback']);
            }

            // Register autoloader
            $this->registerAutoload($restoredPlugin);

            // Note: We don't run migration rollback automatically
            // The user should handle any data migration manually

            // Clear caches
            $this->clearAllCaches();

            // Archive the current version backup
            $this->archiveBackup($currentBackup, $vendor, $slug, $currentVersion);

            Log::info("Plugin rolled back: {$restoredPlugin->getFullSlug()}", [
                'from_version' => $currentVersion,
                'to_version' => $restoredPlugin->version,
            ]);

            return PluginInstallResult::success(
                $restoredPlugin,
                [],
                [],
                "Rolled back from {$currentVersion} to {$restoredPlugin->version}"
            );

        } catch (\Throwable $e) {
            Log::error("Plugin rollback failed: {$vendor}/{$slug}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PluginInstallResult::failed([$e->getMessage()]);
        }
    }

    /**
     * Get available backup versions for a plugin
     */
    public function getAvailableBackups(string $vendor, string $slug): array
    {
        $archivePath = $this->getBackupPath($vendor, $slug);

        if (! File::exists($archivePath)) {
            return [];
        }

        return collect(File::directories($archivePath))
            ->map(function ($path) {
                $name = basename($path);
                $parts = explode('_', $name, 2);

                return [
                    'version' => $parts[0] ?? 'unknown',
                    'date' => isset($parts[1]) ? str_replace('_', ' ', $parts[1]) : 'unknown',
                    'path' => $path,
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    /**
     * Extract a ZIP file to a directory
     */
    protected function extractZip(string $zipPath, string $targetPath, array $pluginData): void
    {
        $zip = new ZipArchive;
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \RuntimeException("Failed to open ZIP file: error code {$result}");
        }

        try {
            // Create temp extraction directory
            File::ensureDirectoryExists($targetPath, 0755);

            // Determine if ZIP has a wrapper directory
            $hasWrapper = $this->zipHasWrapperDirectory($zip);

            if ($hasWrapper) {
                // Extract to temp, then move contents up
                $extractTemp = "{$targetPath}_extract";
                $zip->extractTo($extractTemp);
                $zip->close();

                // Find the wrapper directory
                $dirs = File::directories($extractTemp);
                if (count($dirs) === 1) {
                    // Move contents from wrapper to target
                    if (! File::moveDirectory($dirs[0], $targetPath)) {
                        File::deleteDirectory($extractTemp);

                        throw new \RuntimeException('Failed to move extracted plugin contents from wrapper directory');
                    }
                    File::deleteDirectory($extractTemp);
                } else {
                    // Multiple directories, just move everything
                    foreach (File::directories($extractTemp) as $dir) {
                        if (! File::moveDirectory($dir, "{$targetPath}/".basename($dir))) {
                            File::deleteDirectory($extractTemp);

                            throw new \RuntimeException('Failed to move extracted directory: '.basename($dir));
                        }
                    }
                    foreach (File::files($extractTemp) as $file) {
                        File::move($file, "{$targetPath}/".basename($file));
                    }
                    File::deleteDirectory($extractTemp);
                }
            } else {
                // No wrapper, extract directly
                $zip->extractTo($targetPath);
                $zip->close();
            }
        } catch (\Throwable $e) {
            if ($zip->status === ZipArchive::ER_OK) {
                $zip->close();
            }
            throw $e;
        }
    }

    /**
     * Check if ZIP has a wrapper directory
     */
    protected function zipHasWrapperDirectory(ZipArchive $zip): bool
    {
        // Check if all files are under a single directory
        $firstDir = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = explode('/', $name);

            if (count($parts) > 1) {
                if ($firstDir === null) {
                    $firstDir = $parts[0];
                } elseif ($parts[0] !== $firstDir) {
                    return false;
                }
            }
        }

        // Check if plugin.json is directly in the wrapper
        return $zip->locateName("{$firstDir}/plugin.json") !== false;
    }

    /**
     * Clear all caches after plugin operations
     */
    public function clearAllCaches(): void
    {
        // Clear plugin discovery cache
        $this->clearCache();

        // Clear general application cache
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            Log::debug('Could not clear application cache: '.$e->getMessage());
        }

        // Clear config cache
        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            Log::debug('Could not clear config cache: '.$e->getMessage());
        }

        // Clear view cache (compiled views)
        try {
            Artisan::call('view:clear');
        } catch (\Throwable $e) {
            Log::debug('Could not clear view cache: '.$e->getMessage());
        }

        // Flush view finder cache (in-memory namespace resolution)
        try {
            View::flushFinderCache();
        } catch (\Throwable $e) {
            Log::debug('Could not flush view finder cache: '.$e->getMessage());
        }

        // Clear route cache
        try {
            Artisan::call('route:clear');
        } catch (\Throwable $e) {
            Log::debug('Could not clear route cache: '.$e->getMessage());
        }

        // Clear Filament component cache (pages, resources, widgets)
        try {
            Artisan::call('filament:clear-cached-components');
        } catch (\Throwable $e) {
            Log::debug('Could not clear Filament cache: '.$e->getMessage());
        }

        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Clear file stat cache
        clearstatcache(true);
    }

    /**
     * Get the backup path for a plugin
     */
    public function getBackupPath(string $vendor, string $slug): string
    {
        return storage_path("app/plugin-backups/{$vendor}/{$slug}");
    }
}

/**
 * Plugin install/uninstall result value object
 */
class PluginInstallResult
{
    public function __construct(
        public bool $success,
        public ?Plugin $plugin,
        public array $migrations,
        public array $errors,
        public array $warnings,
        public string $message
    ) {}

    public static function failed(array $errors, string $message = 'Operation failed'): self
    {
        return new self(false, null, [], $errors, [], $message);
    }

    public static function success(
        Plugin $plugin,
        array $migrations = [],
        array $warnings = [],
        string $message = 'Operation successful'
    ): self {
        return new self(true, $plugin, $migrations, [], $warnings, $message);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }
}
