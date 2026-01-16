<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginManager;
use Illuminate\Console\Command;

class PluginInstallCommand extends Command
{
    protected $signature = 'plugin:install
                            {path : Path to the plugin ZIP file}
                            {--no-migrate : Skip running migrations}';

    protected $description = 'Install a plugin from a ZIP file';

    public function handle(PluginManager $manager): int
    {
        $zipPath = $this->argument('path');

        if (! file_exists($zipPath)) {
            $this->error("File not found: {$zipPath}");

            return self::FAILURE;
        }

        if (! $manager->uploadsAllowed()) {
            $this->error('Plugin uploads are disabled. Enable by setting PLUGIN_ALLOW_UPLOADS=true');

            return self::FAILURE;
        }

        // Temporarily disable auto_migrate if --no-migrate is passed
        $originalAutoMigrate = config('plugin.auto_migrate');
        if ($this->option('no-migrate')) {
            config(['plugin.auto_migrate' => false]);
        }

        $this->info('Installing plugin...');

        $result = $manager->installFromZip($zipPath);

        // Restore original setting
        config(['plugin.auto_migrate' => $originalAutoMigrate]);

        if (! $result->success) {
            $this->error('Installation failed:');
            foreach ($result->errors as $error) {
                $this->line("  <fg=red>- {$error}</>");
            }

            return self::FAILURE;
        }

        // Show warnings
        foreach ($result->warnings as $warning) {
            $this->warn("Warning: {$warning}");
        }

        $plugin = $result->plugin;
        $this->info("Successfully installed: {$plugin->name} v{$plugin->version}");

        if (! empty($result->migrations)) {
            $this->info('Migrations ran:');
            foreach ($result->migrations as $migration) {
                $this->line("  - {$migration}");
            }
        }

        return self::SUCCESS;
    }
}
