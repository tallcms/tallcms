<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Services\PluginManager;
use TallCms\Cms\Services\PluginMigrator;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    protected $signature = 'plugin:list
                            {--detailed : Show detailed information}
                            {--tag= : Filter by tag}
                            {--vendor= : Filter by vendor}';

    protected $description = 'List all installed plugins';

    public function handle(PluginManager $manager, PluginMigrator $migrator): int
    {
        $plugins = $manager->getInstalledPlugins();

        // Apply filters
        if ($vendor = $this->option('vendor')) {
            $plugins = $plugins->filter(fn ($p) => $p->vendor === $vendor);
        }

        if ($tag = $this->option('tag')) {
            $plugins = $plugins->filter(fn ($p) => in_array($tag, $p->tags));
        }

        if ($plugins->isEmpty()) {
            $this->info('No plugins installed.');

            return self::SUCCESS;
        }

        if ($this->option('detailed')) {
            $this->displayDetailed($plugins, $migrator);
        } else {
            $this->displaySimple($plugins);
        }

        return self::SUCCESS;
    }

    protected function displaySimple($plugins): void
    {
        $rows = $plugins->map(fn ($plugin) => [
            $plugin->getFullSlug(),
            $plugin->name,
            $plugin->version,
            implode(', ', $plugin->tags),
        ])->toArray();

        $this->table(
            ['Slug', 'Name', 'Version', 'Tags'],
            $rows
        );

        $this->info("Total: {$plugins->count()} plugin(s)");
    }

    protected function displayDetailed($plugins, PluginMigrator $migrator): void
    {
        foreach ($plugins as $plugin) {
            $this->newLine();
            $this->line("<fg=cyan;options=bold>{$plugin->name}</> <fg=gray>v{$plugin->version}</>");
            $this->line("  <fg=gray>Slug:</> {$plugin->getFullSlug()}");
            $this->line("  <fg=gray>Author:</> {$plugin->author}");

            if ($plugin->description) {
                $this->line("  <fg=gray>Description:</> {$plugin->description}");
            }

            if (! empty($plugin->tags)) {
                $this->line('  <fg=gray>Tags:</> '.implode(', ', $plugin->tags));
            }

            // Features
            $features = [];
            if ($plugin->hasFilamentPlugin()) {
                $features[] = 'Filament';
            }
            if ($plugin->hasPublicRoutes()) {
                $features[] = 'Public Routes';
            }
            if ($plugin->hasPrefixedRoutes()) {
                $features[] = 'Prefixed Routes';
            }
            if ($plugin->hasMigrations()) {
                $features[] = 'Migrations';
            }

            if (! empty($features)) {
                $this->line('  <fg=gray>Features:</> '.implode(', ', $features));
            }

            // Migration status
            if ($plugin->hasMigrations()) {
                $hasPending = $migrator->hasPendingMigrations($plugin);
                if ($hasPending) {
                    $this->line('  <fg=yellow>Migrations:</> Pending migrations available');
                } else {
                    $this->line('  <fg=green>Migrations:</> Up to date');
                }
            }

            // Requirements
            if (! $plugin->meetsRequirements()) {
                $this->line('  <fg=red>Status:</> Requirements not met');
                foreach ($plugin->getUnmetRequirements() as $req) {
                    $this->line("    <fg=red>- {$req}</>");
                }
            }

            $this->line("  <fg=gray>Path:</> {$plugin->path}");
        }

        $this->newLine();
        $this->info("Total: {$plugins->count()} plugin(s)");
    }
}
