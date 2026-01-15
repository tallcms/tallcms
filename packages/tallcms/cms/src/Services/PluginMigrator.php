<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TallCms\Cms\Models\Plugin;

class PluginMigrator
{
    protected PluginMigrationRepository $repository;

    protected Filesystem $files;

    public function __construct(PluginMigrationRepository $repository)
    {
        $this->repository = $repository;
        $this->files = app(Filesystem::class);
    }

    /**
     * Run migrations for a plugin
     */
    public function migrate(Plugin $plugin): MigrationResult
    {
        $migrationPath = $plugin->getMigrationPath();
        $errors = [];
        $ranMigrations = [];

        if (! File::exists($migrationPath)) {
            return new MigrationResult(true, [], [], 'No migrations to run');
        }

        $files = $this->getMigrationFiles($migrationPath);

        if (empty($files)) {
            return new MigrationResult(true, [], [], 'No migrations to run');
        }

        $ran = $this->repository->getRan($plugin->vendor, $plugin->slug)->toArray();
        $pendingMigrations = array_diff(array_keys($files), $ran);

        if (empty($pendingMigrations)) {
            return new MigrationResult(true, [], [], 'All migrations have already been run');
        }

        $batch = $this->repository->getNextBatchNumber($plugin->vendor, $plugin->slug);

        foreach ($pendingMigrations as $migrationName) {
            $migrationFile = $files[$migrationName];

            try {
                $this->runMigration($migrationFile, 'up');
                $this->repository->log($plugin->vendor, $plugin->slug, $migrationName, $batch);
                $ranMigrations[] = $migrationName;

                Log::info("Plugin migration ran: {$migrationName}", [
                    'plugin' => $plugin->getFullSlug(),
                    'batch' => $batch,
                ]);
            } catch (\Throwable $e) {
                $errors[] = "Migration {$migrationName} failed: ".$e->getMessage();

                Log::error("Plugin migration failed: {$migrationName}", [
                    'plugin' => $plugin->getFullSlug(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Rollback already run migrations in this batch
                $this->rollbackBatch($plugin, $batch, $files);

                break;
            }
        }

        return new MigrationResult(
            empty($errors),
            $ranMigrations,
            $errors,
            empty($errors) ? 'Migrations completed successfully' : 'Migration failed with rollback'
        );
    }

    /**
     * Rollback all migrations for a plugin
     */
    public function rollback(Plugin $plugin): MigrationResult
    {
        $migrationPath = $plugin->getMigrationPath();
        $errors = [];
        $rolledBack = [];

        Log::debug("Plugin rollback starting", [
            'plugin' => $plugin->getFullSlug(),
            'migrationPath' => $migrationPath,
            'pathExists' => File::exists($migrationPath),
        ]);

        if (! File::exists($migrationPath)) {
            // Still need to clean up migration records even if files don't exist
            $deletedCount = $this->repository->deleteAll($plugin->vendor, $plugin->slug);

            Log::warning("Plugin rollback: migration path not found, cleared {$deletedCount} records", [
                'plugin' => $plugin->getFullSlug(),
                'migrationPath' => $migrationPath,
            ]);

            return new MigrationResult(true, [], [], 'No migration files found');
        }

        $files = $this->getMigrationFiles($migrationPath);
        $ran = $this->repository->getRan($plugin->vendor, $plugin->slug)->toArray();

        Log::debug("Plugin rollback: found migrations", [
            'plugin' => $plugin->getFullSlug(),
            'filesCount' => count($files),
            'ranCount' => count($ran),
            'ran' => $ran,
        ]);

        if (empty($ran)) {
            return new MigrationResult(true, [], [], 'No migrations to rollback');
        }

        // Rollback in reverse order
        $toRollback = array_reverse($ran);

        foreach ($toRollback as $migrationName) {
            if (! isset($files[$migrationName])) {
                // Migration file doesn't exist, just remove the record
                $this->repository->delete($plugin->vendor, $plugin->slug, $migrationName);
                $rolledBack[] = $migrationName.' (file missing)';

                continue;
            }

            $migrationFile = $files[$migrationName];

            try {
                $this->runMigration($migrationFile, 'down');
                $this->repository->delete($plugin->vendor, $plugin->slug, $migrationName);
                $rolledBack[] = $migrationName;

                Log::info("Plugin migration rolled back: {$migrationName}", [
                    'plugin' => $plugin->getFullSlug(),
                ]);
            } catch (\Throwable $e) {
                $errors[] = "Rollback of {$migrationName} failed: ".$e->getMessage();

                Log::error("Plugin migration rollback failed: {$migrationName}", [
                    'plugin' => $plugin->getFullSlug(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Still delete the migration record to prevent orphaned entries
                // The table may still exist, but reinstallation will recreate it
                $this->repository->delete($plugin->vendor, $plugin->slug, $migrationName);
                $rolledBack[] = $migrationName.' (record cleared, table may remain)';

                // Continue with other rollbacks even if one fails
            }
        }

        return new MigrationResult(
            empty($errors),
            $rolledBack,
            $errors,
            empty($errors) ? 'Rollback completed successfully' : 'Rollback completed with errors'
        );
    }

    /**
     * Rollback a specific batch of migrations
     */
    protected function rollbackBatch(Plugin $plugin, int $batch, array $files): void
    {
        $migrations = $this->repository->getMigrationsByBatch($plugin->vendor, $plugin->slug, $batch)->toArray();

        foreach (array_reverse($migrations) as $migrationName) {
            if (! isset($files[$migrationName])) {
                $this->repository->delete($plugin->vendor, $plugin->slug, $migrationName);

                continue;
            }

            try {
                $this->runMigration($files[$migrationName], 'down');
                $this->repository->delete($plugin->vendor, $plugin->slug, $migrationName);

                Log::info("Rolled back migration during failed batch: {$migrationName}", [
                    'plugin' => $plugin->getFullSlug(),
                    'batch' => $batch,
                ]);
            } catch (\Throwable $e) {
                Log::error("Failed to rollback migration during batch rollback: {$migrationName}", [
                    'plugin' => $plugin->getFullSlug(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get migration files from a directory
     */
    protected function getMigrationFiles(string $path): array
    {
        $files = [];

        if (! File::exists($path)) {
            return $files;
        }

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $name = $file->getFilenameWithoutExtension();
            $files[$name] = $file->getPathname();
        }

        // Sort by filename (timestamp prefix ensures correct order)
        ksort($files);

        return $files;
    }

    /**
     * Run a migration file
     */
    protected function runMigration(string $file, string $method): void
    {
        $migration = $this->resolveMigration($file);
        $migration->{$method}();
    }

    /**
     * Resolve a migration instance from a file
     */
    protected function resolveMigration(string $file): object
    {
        $class = $this->getMigrationClass($file);

        if (class_exists($class)) {
            return app($class);
        }

        // Include the file and try to find the class
        require_once $file;

        // Try the class name based on the file
        if (class_exists($class)) {
            return app($class);
        }

        // For anonymous class migrations (Laravel 8+), return the instance directly
        $migration = require $file;
        if (is_object($migration)) {
            return $migration;
        }

        throw new \RuntimeException("Unable to resolve migration class from: {$file}");
    }

    /**
     * Get the class name from a migration file
     */
    protected function getMigrationClass(string $file): string
    {
        $filename = basename($file, '.php');

        // Remove date prefix if present (e.g., 2024_01_01_000000_create_table)
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);

        return Str::studly($name);
    }

    /**
     * Get the status of all migrations for a plugin
     */
    public function getMigrationStatus(Plugin $plugin): array
    {
        $migrationPath = $plugin->getMigrationPath();
        $status = [];

        if (! File::exists($migrationPath)) {
            return $status;
        }

        $files = $this->getMigrationFiles($migrationPath);
        $ran = $this->repository->getRan($plugin->vendor, $plugin->slug)->toArray();

        foreach ($files as $name => $path) {
            $status[$name] = [
                'name' => $name,
                'ran' => in_array($name, $ran),
                'batch' => $this->repository->getBatch($plugin->vendor, $plugin->slug, $name),
            ];
        }

        return $status;
    }

    /**
     * Check if a plugin has pending migrations
     */
    public function hasPendingMigrations(Plugin $plugin): bool
    {
        $migrationPath = $plugin->getMigrationPath();

        if (! File::exists($migrationPath)) {
            return false;
        }

        $files = $this->getMigrationFiles($migrationPath);
        $ran = $this->repository->getRan($plugin->vendor, $plugin->slug)->toArray();

        return count(array_diff(array_keys($files), $ran)) > 0;
    }
}

/**
 * Migration result value object
 */
class MigrationResult
{
    public function __construct(
        public bool $success,
        public array $migrations,
        public array $errors,
        public string $message
    ) {}

    public static function failed(array $errors, string $message = 'Migration failed'): self
    {
        return new self(false, [], $errors, $message);
    }

    public static function success(array $migrations = [], string $message = 'Success'): self
    {
        return new self(true, $migrations, [], $message);
    }
}
