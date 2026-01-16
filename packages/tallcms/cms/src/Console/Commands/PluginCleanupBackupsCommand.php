<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginCleanupBackupsCommand extends Command
{
    protected $signature = 'plugin:cleanup-backups
                            {plugin? : Plugin slug (vendor/slug) or omit to clean all}
                            {--keep=3 : Number of backups to keep per plugin}
                            {--force : Skip confirmation}';

    protected $description = 'Clean up old plugin backups';

    public function handle(PluginManager $manager): int
    {
        $pluginSlug = $this->argument('plugin');
        $keep = (int) $this->option('keep');

        if ($keep < 0) {
            $this->error('--keep must be a non-negative number');

            return self::FAILURE;
        }

        if ($pluginSlug) {
            return $this->cleanupSinglePlugin($manager, $pluginSlug, $keep);
        }

        return $this->cleanupAllPlugins($manager, $keep);
    }

    protected function cleanupSinglePlugin(PluginManager $manager, string $pluginSlug, int $keep): int
    {
        $parts = explode('/', $pluginSlug, 2);

        if (count($parts) !== 2) {
            $this->error('Invalid plugin format. Use: vendor/slug');

            return self::FAILURE;
        }

        [$vendor, $slug] = $parts;

        $backupPath = $manager->getBackupPath($vendor, $slug);

        if (! File::exists($backupPath)) {
            $this->info("No backups found for {$pluginSlug}");

            return self::SUCCESS;
        }

        $backups = $manager->getAvailableBackups($vendor, $slug);

        if (count($backups) <= $keep) {
            $this->info('No cleanup needed. Found '.count($backups)." backup(s), keeping {$keep}.");

            return self::SUCCESS;
        }

        $toDelete = array_slice($backups, $keep);

        if (! $this->option('force')) {
            $this->table(['Version', 'Date'], array_map(fn ($b) => [$b['version'], $b['date']], $toDelete));

            $confirmed = $this->confirm(
                'Delete '.count($toDelete).' backup(s) for '.$pluginSlug.'?',
                false
            );

            if (! $confirmed) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        $deleted = 0;
        foreach ($toDelete as $backup) {
            File::deleteDirectory($backup['path']);
            $deleted++;
        }

        $this->info("Deleted {$deleted} backup(s) for {$pluginSlug}");

        return self::SUCCESS;
    }

    protected function cleanupAllPlugins(PluginManager $manager, int $keep): int
    {
        $backupBase = storage_path('app/plugin-backups');

        if (! File::exists($backupBase)) {
            $this->info('No plugin backups found.');

            return self::SUCCESS;
        }

        $totalDeleted = 0;
        $plugins = [];

        // Scan all vendor/slug directories
        foreach (File::directories($backupBase) as $vendorDir) {
            $vendor = basename($vendorDir);
            foreach (File::directories($vendorDir) as $slugDir) {
                $slug = basename($slugDir);
                $backups = $manager->getAvailableBackups($vendor, $slug);

                if (count($backups) > $keep) {
                    $plugins["{$vendor}/{$slug}"] = array_slice($backups, $keep);
                }
            }
        }

        if (empty($plugins)) {
            $this->info("No cleanup needed. All plugins have {$keep} or fewer backups.");

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->info('Backups to delete:');
            foreach ($plugins as $pluginSlug => $toDelete) {
                $this->line("  {$pluginSlug}: ".count($toDelete).' backup(s)');
            }

            $totalToDelete = array_sum(array_map('count', $plugins));
            $confirmed = $this->confirm("Delete {$totalToDelete} backup(s) total?", false);

            if (! $confirmed) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        foreach ($plugins as $pluginSlug => $toDelete) {
            foreach ($toDelete as $backup) {
                File::deleteDirectory($backup['path']);
                $totalDeleted++;
            }
        }

        $this->info("Deleted {$totalDeleted} backup(s) across ".count($plugins).' plugin(s)');

        return self::SUCCESS;
    }
}
