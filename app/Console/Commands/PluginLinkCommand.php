<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginLinkCommand extends Command
{
    protected $signature = 'plugin:link {source : Absolute path to plugin source directory} {--vendor=tallcms : Plugin vendor} {--slug= : Plugin slug (defaults to source directory name)}';

    protected $description = 'Create a symlink for local plugin development';

    public function handle(): int
    {
        $source = $this->argument('source');
        $vendor = $this->option('vendor');
        $slug = $this->option('slug') ?: basename($source);

        // Validate source exists
        if (! File::exists($source)) {
            $this->error("Source directory does not exist: {$source}");
            return 1;
        }

        // Validate plugin.json exists in source
        if (! File::exists("{$source}/plugin.json")) {
            $this->error("No plugin.json found in source directory");
            return 1;
        }

        $targetDir = base_path("plugins/{$vendor}");
        $targetPath = "{$targetDir}/{$slug}";

        // Ensure vendor directory exists
        File::ensureDirectoryExists($targetDir, 0755);

        // Check if target already exists
        if (File::exists($targetPath) || is_link($targetPath)) {
            if (is_link($targetPath)) {
                $this->warn("Symlink already exists: {$targetPath} -> " . readlink($targetPath));
                if (! $this->confirm('Remove existing symlink and create new one?')) {
                    return 0;
                }
                unlink($targetPath);
            } else {
                $this->error("Target path exists and is not a symlink: {$targetPath}");
                $this->error("Remove it manually if you want to create a symlink.");
                return 1;
            }
        }

        // Create symlink
        if (symlink($source, $targetPath)) {
            $this->info("Symlink created: {$targetPath} -> {$source}");
            $this->newLine();
            $this->line("Run migrations with:");
            $this->line("  php artisan plugin:migrate {$vendor}/{$slug}");
            return 0;
        } else {
            $this->error("Failed to create symlink");
            return 1;
        }
    }
}
