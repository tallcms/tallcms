<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use TallCms\Cms\Services\PluginManager;
use TallCms\Cms\Services\PluginValidator;
use ZipArchive;

class PluginPackageCommand extends Command
{
    protected $signature = 'plugin:package
                            {slug? : Plugin slug (e.g., "registration" or "tallcms/registration")}
                            {--output= : Output directory (default: current directory)}
                            {--skip-validate : Skip validation before packaging}';

    protected $description = 'Package a plugin into a clean ZIP ready for upload or distribution';

    /**
     * Files and directories to always exclude from the ZIP.
     */
    protected array $excludePatterns = [
        '.git',
        '.gitignore',
        '.gitattributes',
        '.DS_Store',
        '__MACOSX',
        'Thumbs.db',
        'node_modules',
        'vendor',
        'tests',
        '.phpunit.cache',
        '.phpunit.result.cache',
        'phpunit.xml',
        'phpunit.xml.dist',
        '.env',
        '.env.example',
        'CLAUDE.md',
        '.idea',
        '.vscode',
    ];

    public function handle(PluginManager $manager, PluginValidator $validator): int
    {
        $slug = $this->argument('slug');

        if (! $slug) {
            return $this->selectPlugin($manager, $validator);
        }

        // Normalize slug: "registration" → find by slug, "tallcms/registration" → find by full slug
        $plugin = $manager->getInstalledPlugins()->first(function ($p) use ($slug) {
            return $p->slug === $slug || $p->getFullSlug() === $slug;
        });

        if (! $plugin) {
            $this->error("Plugin '{$slug}' not found. Use `plugin:list` to see installed plugins.");

            return self::FAILURE;
        }

        return $this->packagePlugin($plugin, $validator);
    }

    protected function selectPlugin(PluginManager $manager, PluginValidator $validator): int
    {
        $plugins = $manager->getInstalledPlugins();

        if ($plugins->isEmpty()) {
            $this->error('No plugins installed.');

            return self::FAILURE;
        }

        $choices = $plugins->map(fn ($p) => "{$p->vendor}/{$p->slug} (v{$p->version})")->values()->toArray();

        $selected = $this->choice('Which plugin do you want to package?', $choices);

        $index = array_search($selected, $choices);
        $plugin = $plugins->values()->get($index);

        return $this->packagePlugin($plugin, $validator);
    }

    protected function packagePlugin(object $plugin, PluginValidator $validator): int
    {
        $pluginPath = $plugin->path;
        $vendor = $plugin->vendor;
        $slug = $plugin->slug;
        $version = $plugin->version;

        $this->info("Packaging {$vendor}/{$slug} v{$version}...");

        if (! File::isDirectory($pluginPath)) {
            $this->error("Plugin directory not found: {$pluginPath}");

            return self::FAILURE;
        }

        // Determine output path
        $outputDir = $this->option('output') ?: getcwd();
        $zipFilename = "{$vendor}-{$slug}-{$version}.zip";
        $zipPath = rtrim($outputDir, '/') . '/' . $zipFilename;

        // Build file list (respecting exclusions)
        $files = $this->collectFiles($pluginPath);

        if (empty($files)) {
            $this->error('No files to package.');

            return self::FAILURE;
        }

        $this->info("  Found " . count($files) . " files");

        // Create ZIP
        $zip = new ZipArchive;
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            $this->error("Failed to create ZIP: error code {$result}");

            return self::FAILURE;
        }

        foreach ($files as $absolutePath => $relativePath) {
            $zip->addFile($absolutePath, $relativePath);
        }

        $zip->close();

        $sizeKb = round(filesize($zipPath) / 1024, 1);
        $this->info("  Created: {$zipPath} ({$sizeKb} KB)");

        // Validate the ZIP
        if (! $this->option('skip-validate')) {
            $this->newLine();
            $this->info('Validating ZIP...');

            $validation = $validator->validateZip($zipPath);

            if (! $validation->isValid) {
                $this->error('Validation failed:');
                foreach ($validation->errors as $error) {
                    $this->line("  ✗ {$error}");
                }

                // Remove the invalid ZIP
                File::delete($zipPath);
                $this->error('ZIP removed. Fix the errors above and try again.');

                return self::FAILURE;
            }

            if (! empty($validation->warnings)) {
                foreach ($validation->warnings as $warning) {
                    $this->warn("  ⚠ {$warning}");
                }
            }

            $this->info('  ✓ Validation passed');
        }

        $this->newLine();
        $this->info("✓ Plugin packaged: {$zipFilename}");

        return self::SUCCESS;
    }

    /**
     * Collect all files from the plugin directory, excluding patterns.
     *
     * @return array<string, string> Absolute path → relative path
     */
    protected function collectFiles(string $basePath): array
    {
        $files = [];
        $basePath = rtrim($basePath, '/');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $basePath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = substr($absolutePath, strlen($basePath) + 1);

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $files[$absolutePath] = $relativePath;
        }

        return $files;
    }

    /**
     * Check if a file should be excluded from the ZIP.
     */
    protected function shouldExclude(string $relativePath): bool
    {
        $parts = explode('/', $relativePath);

        foreach ($parts as $part) {
            foreach ($this->excludePatterns as $pattern) {
                if ($part === $pattern) {
                    return true;
                }

                // Glob-style matching for patterns like .env.*
                if (str_contains($pattern, '*') && fnmatch($pattern, $part)) {
                    return true;
                }
            }
        }

        return false;
    }
}
