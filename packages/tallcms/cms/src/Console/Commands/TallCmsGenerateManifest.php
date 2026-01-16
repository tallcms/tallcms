<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class TallCmsGenerateManifest extends Command
{
    protected $signature = 'tallcms:generate-manifest
                            {--output= : Output file path (default: checksums.json)}';

    protected $description = 'Generate a manifest of all tracked files with SHA256 hashes for release signing';

    /**
     * Directories to track in the manifest.
     */
    private array $trackedDirs = [
        'app',
        'bootstrap',
        'config',
        'database/migrations',
        'public',
        'resources',
        'routes',
        'vendor',
    ];

    /**
     * Root files to track.
     */
    private array $trackedFiles = [
        'artisan',
        'composer.json',
        'composer.lock',
    ];

    /**
     * Patterns to exclude from tracking.
     */
    private array $excludePatterns = [
        '/\.git/',
        '/node_modules/',
        '/storage\//',
        '/\.env/',
        '/\.log$/',
        '/\.DS_Store/',
        '/Thumbs\.db/',
    ];

    public function handle(): int
    {
        $this->info('Generating TallCMS release manifest...');

        $manifest = [
            'version' => config('tallcms.version'),
            'generated_at' => now()->toIso8601String(),
            'files' => [],
            'directories' => [],
        ];

        // Hash all tracked directories
        foreach ($this->trackedDirs as $dir) {
            $fullPath = base_path($dir);
            if (! is_dir($fullPath)) {
                $this->warn("Directory not found: {$dir}");

                continue;
            }

            $manifest['directories'][] = $dir.'/';
            $this->hashDirectory($fullPath, $dir, $manifest['files']);
        }

        // Hash root files
        foreach ($this->trackedFiles as $file) {
            $fullPath = base_path($file);
            if (file_exists($fullPath)) {
                $manifest['files'][$file] = 'sha256:'.hash_file('sha256', $fullPath);
            }
        }

        // Add composer.lock hash for vendor integrity checking
        if (file_exists(base_path('composer.lock'))) {
            $manifest['composer_lock_hash'] = hash_file('sha256', base_path('composer.lock'));
        }

        // Sort files for consistent output
        ksort($manifest['files']);
        sort($manifest['directories']);

        $this->components->twoColumnDetail('Files tracked', (string) count($manifest['files']));
        $this->components->twoColumnDetail('Directories', (string) count($manifest['directories']));

        // Write output
        $outputPath = $this->option('output') ?? base_path('checksums.json');
        file_put_contents(
            $outputPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->components->info("Manifest written to: {$outputPath}");

        return 0;
    }

    /**
     * Recursively hash all files in a directory.
     */
    private function hashDirectory(string $fullPath, string $relativePath, array &$files): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getPathname();
            $relativeFilePath = $relativePath.'/'.substr($filePath, strlen($fullPath) + 1);

            // Check exclusions
            if ($this->shouldExclude($relativeFilePath)) {
                continue;
            }

            $files[$relativeFilePath] = 'sha256:'.hash_file('sha256', $filePath);
        }
    }

    /**
     * Check if a file should be excluded from the manifest.
     */
    private function shouldExclude(string $path): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
