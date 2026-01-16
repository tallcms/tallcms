<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginManager;
use Illuminate\Console\Command;

class PluginUninstallCommand extends Command
{
    protected $signature = 'plugin:uninstall
                            {plugin : Plugin slug (vendor/slug)}
                            {--force : Skip confirmation}';

    protected $description = 'Uninstall a plugin';

    public function handle(PluginManager $manager): int
    {
        $pluginSlug = $this->argument('plugin');
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

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to uninstall '{$plugin->name}'? This will rollback all migrations and delete plugin files.",
                false
            );

            if (! $confirmed) {
                $this->info('Uninstallation cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info("Uninstalling {$plugin->name}...");

        $result = $manager->uninstall($vendor, $slug);

        if (! $result->success) {
            $this->error('Uninstallation failed:');
            foreach ($result->errors as $error) {
                $this->line("  <fg=red>- {$error}</>");
            }

            return self::FAILURE;
        }

        $this->info("Successfully uninstalled: {$plugin->name}");

        if (! empty($result->migrations)) {
            $this->info('Migrations rolled back:');
            foreach ($result->migrations as $migration) {
                $this->line("  - {$migration}");
            }
        }

        return self::SUCCESS;
    }
}
