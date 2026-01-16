<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginManager;
use TallCms\Cms\Services\PluginMigrator;
use Illuminate\Console\Command;

class PluginMigrateCommand extends Command
{
    protected $signature = 'plugin:migrate
                            {plugin? : Plugin slug (vendor/slug) or omit to migrate all}
                            {--rollback : Rollback migrations instead of running them}
                            {--status : Show migration status}';

    protected $description = 'Run or rollback migrations for plugins';

    public function handle(PluginManager $manager, PluginMigrator $migrator): int
    {
        $pluginSlug = $this->argument('plugin');

        if ($pluginSlug) {
            return $this->handleSinglePlugin($manager, $migrator, $pluginSlug);
        }

        return $this->handleAllPlugins($manager, $migrator);
    }

    protected function handleSinglePlugin(PluginManager $manager, PluginMigrator $migrator, string $pluginSlug): int
    {
        $parts = explode('/', $pluginSlug, 2);

        if (count($parts) !== 2) {
            $this->error('Invalid plugin format. Use: vendor/slug');

            return self::FAILURE;
        }

        [$vendor, $slug] = $parts;

        $plugin = $manager->find($vendor, $slug);

        if (! $plugin) {
            $this->error("Plugin not found: {$pluginSlug}");

            return self::FAILURE;
        }

        if ($this->option('status')) {
            $this->showMigrationStatus($plugin, $migrator);

            return self::SUCCESS;
        }

        if ($this->option('rollback')) {
            return $this->rollbackPlugin($plugin, $migrator);
        }

        return $this->migratePlugin($plugin, $migrator);
    }

    protected function handleAllPlugins(PluginManager $manager, PluginMigrator $migrator): int
    {
        $plugins = $manager->getInstalledPlugins();

        if ($plugins->isEmpty()) {
            $this->info('No plugins installed.');

            return self::SUCCESS;
        }

        if ($this->option('status')) {
            foreach ($plugins as $plugin) {
                $this->showMigrationStatus($plugin, $migrator);
                $this->newLine();
            }

            return self::SUCCESS;
        }

        if ($this->option('rollback')) {
            $this->warn('Rolling back all plugins is not supported. Please specify a plugin.');

            return self::FAILURE;
        }

        $this->info('Running migrations for all plugins...');

        $hasErrors = false;

        foreach ($plugins as $plugin) {
            if (! $plugin->hasMigrations()) {
                continue;
            }

            $this->line("Processing: {$plugin->getFullSlug()}");
            $result = $migrator->migrate($plugin);

            if (! $result->success) {
                $this->error("  Failed: {$result->message}");
                $hasErrors = true;
            } elseif (! empty($result->migrations)) {
                foreach ($result->migrations as $migration) {
                    $this->line("  <fg=green>Migrated:</> {$migration}");
                }
            } else {
                $this->line('  <fg=gray>Nothing to migrate</>');
            }
        }

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    protected function migratePlugin($plugin, PluginMigrator $migrator): int
    {
        $this->info("Running migrations for: {$plugin->name}");

        $result = $migrator->migrate($plugin);

        if (! $result->success) {
            $this->error("Migration failed: {$result->message}");
            foreach ($result->errors as $error) {
                $this->line("  <fg=red>- {$error}</>");
            }

            return self::FAILURE;
        }

        if (empty($result->migrations)) {
            $this->info('Nothing to migrate.');
        } else {
            $this->info('Migrations ran:');
            foreach ($result->migrations as $migration) {
                $this->line("  <fg=green>- {$migration}</>");
            }
        }

        return self::SUCCESS;
    }

    protected function rollbackPlugin($plugin, PluginMigrator $migrator): int
    {
        $this->info("Rolling back migrations for: {$plugin->name}");

        $result = $migrator->rollback($plugin);

        if (! $result->success) {
            $this->error("Rollback failed: {$result->message}");
            foreach ($result->errors as $error) {
                $this->line("  <fg=red>- {$error}</>");
            }

            return self::FAILURE;
        }

        if (empty($result->migrations)) {
            $this->info('Nothing to rollback.');
        } else {
            $this->info('Migrations rolled back:');
            foreach ($result->migrations as $migration) {
                $this->line("  <fg=yellow>- {$migration}</>");
            }
        }

        return self::SUCCESS;
    }

    protected function showMigrationStatus($plugin, PluginMigrator $migrator): void
    {
        $this->line("<fg=cyan;options=bold>{$plugin->name}</> ({$plugin->getFullSlug()})");

        $status = $migrator->getMigrationStatus($plugin);

        if (empty($status)) {
            $this->line('  <fg=gray>No migrations</>');

            return;
        }

        foreach ($status as $migration) {
            if ($migration['ran']) {
                $batch = $migration['batch'] ? " (Batch {$migration['batch']})" : '';
                $this->line("  <fg=green>[Ran]</> {$migration['name']}{$batch}");
            } else {
                $this->line("  <fg=yellow>[Pending]</> {$migration['name']}");
            }
        }
    }
}
