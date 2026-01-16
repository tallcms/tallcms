<?php

namespace TallCms\Cms\Console\Commands;

use TallCms\Cms\Models\Theme;
use TallCms\Cms\Services\ThemeManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeBuild extends Command
{
    protected $signature = 'theme:build {slug? : The theme slug to build (optional, builds active theme if not specified)}
                            {--force : Force rebuild even if assets exist}';

    protected $description = 'Build TallCMS theme assets';

    public function __construct(
        protected ThemeManager $themeManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $force = $this->option('force');

        // Determine which theme to build
        if ($slug) {
            $theme = Theme::find($slug);
            if (! $theme) {
                $this->error("Theme '{$slug}' not found!");

                return 1;
            }
        } else {
            $theme = $this->themeManager->getActiveTheme();
            $this->info("Building active theme: {$theme->name}");
        }

        return $this->buildTheme($theme, $force);
    }

    protected function buildTheme(Theme $theme, bool $force = false): int
    {
        $this->info("Building theme: {$theme->name} ({$theme->slug})");

        // Check if theme directory exists
        if (! File::exists($theme->path)) {
            $this->error("Theme directory not found: {$theme->path}");

            return 1;
        }

        // Check if package.json exists
        $packageJsonPath = $theme->path.'/package.json';
        if (! File::exists($packageJsonPath)) {
            $this->error('No package.json found in theme directory!');
            $this->line('Theme may not have build configuration.');

            return 1;
        }

        // Check if root node_modules exists
        if (! File::exists(base_path('node_modules'))) {
            $this->error('Root node_modules not found!');
            $this->line("Run 'npm install' from the project root first.");

            return 1;
        }

        // Create symlinks for public assets
        $this->line('Publishing theme assets...');
        $this->themeManager->publishThemeAssets($theme);

        // Build assets (uses root node_modules via NODE_PATH)
        $this->line('Building theme assets...');
        if ($this->themeManager->buildThemeAssets($theme)) {
            $this->info("✅ Theme '{$theme->name}' built successfully!");

            // Show build output information
            $this->showBuildInfo($theme);

            return 0;
        }

        $this->error('❌ Failed to build theme assets!');
        $this->line("Check the theme's build configuration and try again.");

        return 1;
    }

    protected function showBuildInfo(Theme $theme): void
    {
        $this->newLine();
        $this->comment('Build Information:');

        // Check for built assets
        $publicPath = public_path("themes/{$theme->slug}");
        if (File::exists($publicPath)) {
            $this->line("Public assets: {$publicPath}");
        }

        $buildPath = $theme->path.'/public/build';
        if (File::exists($buildPath)) {
            $this->line("Built assets: {$buildPath}");

            // Show asset files
            $assetFiles = File::files($buildPath);
            if ($assetFiles) {
                $this->line('Generated files:');
                foreach ($assetFiles as $file) {
                    $size = $this->formatBytes(File::size($file));
                    $this->line("  • {$file->getFilename()} ({$size})");
                }
            }
        }

        $this->newLine();
        $this->line('Theme is ready to use!');
        $this->line("Activate with: php artisan theme:activate {$theme->slug}");
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
