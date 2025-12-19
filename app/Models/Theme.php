<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Theme
{
    public string $name;
    public string $slug;
    public string $version;
    public string $description;
    public string $author;
    public array $tailwind;
    public array $supports;
    public array $build;
    public string $path;
    public ?string $parent = null;
    public array $extras = [];

    public function __construct(array $data, string $path)
    {
        $this->name = $data['name'] ?? 'Untitled Theme';
        $this->slug = $data['slug'] ?? 'untitled';
        $this->version = $data['version'] ?? '1.0.0';
        $this->description = $data['description'] ?? '';
        $this->author = $data['author'] ?? 'Unknown';
        $this->tailwind = $data['tailwind'] ?? [];
        $this->supports = $data['supports'] ?? [];
        $this->build = $data['build'] ?? [];
        $this->parent = $data['parent'] ?? null;
        $this->path = $path;

        // Parse extras including compatibility and screenshots
        $this->extras = $data['extras'] ?? [];

        // Store compatibility info in extras
        $this->extras['compatibility'] = $data['compatibility'] ?? [
            'tallcms' => '*',
            'php' => '^8.2',
            'extensions' => [],
            'prebuilt' => true,
        ];

        // Store screenshots info in extras
        $this->extras['screenshots'] = $data['screenshots'] ?? [
            'primary' => null,
            'gallery' => [],
        ];

        // Store additional metadata
        $this->extras['author_url'] = $data['author_url'] ?? null;
        $this->extras['license'] = $data['license'] ?? null;
        $this->extras['homepage'] = $data['homepage'] ?? null;
    }

    /**
     * Get theme from directory
     */
    public static function fromDirectory(string $path): ?self
    {
        $themeJsonPath = $path . '/theme.json';
        
        if (!File::exists($themeJsonPath)) {
            return null;
        }

        try {
            $themeData = json_decode(File::get($themeJsonPath), true);
            return new self($themeData, $path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all available themes
     */
    public static function all(): Collection
    {
        // Use ThemeManager for cached discovery if available
        if (app()->bound('theme.manager')) {
            return app('theme.manager')->getAvailableThemes();
        }

        // Fallback to direct filesystem discovery
        return self::discoverFromFilesystem();
    }

    /**
     * Discover themes directly from filesystem
     */
    protected static function discoverFromFilesystem(): Collection
    {
        $themesPath = base_path('themes');
        
        if (!File::exists($themesPath)) {
            return collect();
        }

        $themes = collect();
        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $theme = self::fromDirectory($directory);
            if ($theme) {
                $themes->push($theme);
            }
        }

        return $themes;
    }

    /**
     * Find theme by slug
     */
    public static function find(string $slug): ?self
    {
        return self::all()->firstWhere('slug', $slug);
    }


    /**
     * Get theme's view path
     */
    public function getViewPath(string $viewPath = ''): string
    {
        return $this->path . '/resources/views' . ($viewPath ? '/' . $viewPath : '');
    }

    /**
     * Get theme's public path
     */
    public function getPublicPath(string $assetPath = ''): string
    {
        return $this->path . '/public' . ($assetPath ? '/' . $assetPath : '');
    }

    /**
     * Get theme's resource path
     */
    public function getResourcePath(string $resourcePath = ''): string
    {
        return $this->path . '/resources' . ($resourcePath ? '/' . $resourcePath : '');
    }

    /**
     * Check if theme supports a feature
     */
    public function supports(string $feature): bool
    {
        return isset($this->supports[$feature]) && $this->supports[$feature] === true;
    }

    /**
     * Check if theme supports a specific block
     */
    public function supportsBlock(string $blockName): bool
    {
        return isset($this->supports['blocks']) && 
               is_array($this->supports['blocks']) && 
               in_array($blockName, $this->supports['blocks']);
    }

    /**
     * Get all supported blocks
     */
    public function getSupportedBlocks(): array
    {
        return $this->supports['blocks'] ?? [];
    }

    /**
     * Get theme's Tailwind configuration
     */
    public function getTailwindConfig(): array
    {
        return $this->tailwind;
    }

    /**
     * Get theme asset URL
     */
    public function asset(string $path): string
    {
        return asset("themes/{$this->slug}/{$path}");
    }

    /**
     * Get parent theme
     */
    public function getParent(): ?self
    {
        if (!$this->parent) {
            return null;
        }

        return self::find($this->parent);
    }

    /**
     * Check if theme has parent
     */
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    /**
     * Get theme hierarchy chain (including self)
     */
    public function getHierarchy(): array
    {
        $chain = [$this];
        $current = $this;

        // Build chain up to parent themes (prevent circular references)
        $visited = [$this->slug];
        while ($current->hasParent() && !in_array($current->parent, $visited)) {
            $parent = $current->getParent();
            if (!$parent) break;

            $chain[] = $parent;
            $visited[] = $parent->slug;
            $current = $parent;
        }

        return $chain;
    }

    /**
     * Find view with parent fallback
     */
    public function findView(string $viewPath): ?string
    {
        // Try current theme first
        $fullPath = $this->path . '/resources/views/' . str_replace('.', '/', $viewPath) . '.blade.php';
        if (File::exists($fullPath)) {
            return $fullPath;
        }

        // Try parent themes
        $parent = $this->getParent();
        if ($parent) {
            return $parent->findView($viewPath);
        }

        return null;
    }

    /**
     * Check if theme (or parents) has view override
     */
    public function hasViewOverride(string $viewPath): bool
    {
        return $this->findView($viewPath) !== null;
    }

    /**
     * Get the primary screenshot URL for the theme
     * Screenshots must be in the public/ directory to be web-accessible
     */
    public function getScreenshotUrl(): ?string
    {
        $candidatePaths = [];

        // Check for configured screenshot in theme.json
        $primaryScreenshot = $this->extras['screenshots']['primary'] ?? null;
        if ($primaryScreenshot) {
            // Screenshot path must be relative to public/
            $screenshotPath = $this->path . '/public/' . $primaryScreenshot;
            $candidatePaths[] = [
                'asset' => $primaryScreenshot,
                'source' => $screenshotPath,
            ];

            // Also check if the path already includes public/
            if (str_starts_with($primaryScreenshot, 'public/')) {
                $assetPath = substr($primaryScreenshot, 7);
                $fullPath = $this->path . '/' . $primaryScreenshot;
                $candidatePaths[] = [
                    'asset' => $assetPath,
                    'source' => $fullPath,
                ];
            }
        }

        // Look for common screenshot filenames (only in public/ directory)
        $possibleNames = [
            'screenshot.png',
            'screenshot.jpg',
            'screenshot.jpeg',
            'preview.png',
            'preview.jpg',
            'preview.jpeg',
        ];

        foreach ($possibleNames as $filename) {
            $candidatePaths[] = [
                'asset' => $filename,
                'source' => $this->path . '/public/' . $filename,
            ];
        }

        foreach ($candidatePaths as $paths) {
            $sourcePath = $paths['source'];
            $assetPath = ltrim($paths['asset'], '/');

            if (!File::exists($sourcePath)) {
                continue;
            }

            $this->ensureThemeAssetsPublished($assetPath);

            $publicAssetPath = public_path("themes/{$this->slug}/{$assetPath}");
            if (File::exists($publicAssetPath)) {
                return asset("themes/{$this->slug}/{$assetPath}");
            }

            // Fallback to data URI if publish failed
            try {
                $mime = File::mimeType($sourcePath) ?: 'image/png';
                $data = base64_encode(File::get($sourcePath));
                return "data:{$mime};base64,{$data}";
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Get gallery screenshots
     * Screenshots must be in the public/ directory to be web-accessible
     */
    public function getGalleryScreenshots(): array
    {
        $gallery = $this->extras['screenshots']['gallery'] ?? [];
        $urls = [];

        foreach ($gallery as $screenshot) {
            // Screenshots must be in public/ directory
            $fullPath = $this->path . '/public/' . $screenshot;
            if (File::exists($fullPath)) {
                $urls[] = asset("themes/{$this->slug}/{$screenshot}");
            }
        }

        return $urls;
    }

    /**
     * Ensure theme assets are published so screenshots resolve from /public/themes
     */
    protected function ensureThemeAssetsPublished(string $assetPath): void
    {
        $publicAsset = public_path("themes/{$this->slug}/{$assetPath}");

        if (File::exists($publicAsset)) {
            return;
        }

        try {
            if (app()->bound(\App\Services\ThemeManager::class)) {
                app(\App\Services\ThemeManager::class)->publishThemeAssets($this);
            }
        } catch (\Throwable $e) {
            // Silently continue; we'll fallback to data URI if needed
        }
    }

    /**
     * Check if theme is prebuilt (no Node.js build required)
     */
    public function isPrebuilt(): bool
    {
        return $this->extras['compatibility']['prebuilt'] ?? true;
    }

    /**
     * Check if theme has been built (manifest exists)
     */
    public function isBuilt(): bool
    {
        $manifestPath = $this->path . '/public/build/manifest.json';
        return File::exists($manifestPath);
    }

    /**
     * Get compatibility requirements
     */
    public function getCompatibility(): array
    {
        return $this->extras['compatibility'] ?? [];
    }

    /**
     * Get TallCMS version from config (single source of truth)
     */
    public static function getTallcmsVersion(): string
    {
        return config('tallcms.version', '1.0.0');
    }

    /**
     * Check if theme meets all system requirements
     */
    public function meetsRequirements(): bool
    {
        $compatibility = $this->getCompatibility();

        // Check PHP version
        if (!empty($compatibility['php'])) {
            $requiredPhp = $compatibility['php'];
            if (!$this->versionSatisfies(PHP_VERSION, $requiredPhp)) {
                return false;
            }
        }

        // Check PHP extensions
        if (!empty($compatibility['extensions'])) {
            foreach ($compatibility['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    return false;
                }
            }
        }

        // Check TallCMS version
        if (!empty($compatibility['tallcms']) && $compatibility['tallcms'] !== '*') {
            if (!$this->versionSatisfies(self::getTallcmsVersion(), $compatibility['tallcms'])) {
                return false;
            }
        }

        // Check prebuilt requirement in production
        if (!$this->isPrebuilt() && app()->environment('production')) {
            return false;
        }

        // Check if theme is built when prebuilt is expected
        if ($this->isPrebuilt() && !$this->isBuilt()) {
            return false;
        }

        return true;
    }

    /**
     * Get list of unmet requirements
     */
    public function getUnmetRequirements(): array
    {
        $unmet = [];
        $compatibility = $this->getCompatibility();

        // Check PHP version
        if (!empty($compatibility['php'])) {
            $requiredPhp = $compatibility['php'];
            if (!$this->versionSatisfies(PHP_VERSION, $requiredPhp)) {
                $unmet[] = "Requires PHP {$requiredPhp}, current is " . PHP_VERSION;
            }
        }

        // Check PHP extensions
        if (!empty($compatibility['extensions'])) {
            foreach ($compatibility['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    $unmet[] = "Requires PHP extension: {$extension}";
                }
            }
        }

        // Check TallCMS version
        if (!empty($compatibility['tallcms']) && $compatibility['tallcms'] !== '*') {
            $currentTallcms = self::getTallcmsVersion();
            if (!$this->versionSatisfies($currentTallcms, $compatibility['tallcms'])) {
                $unmet[] = "Requires TallCMS {$compatibility['tallcms']}, current is {$currentTallcms}";
            }
        }

        // Check prebuilt requirement in production
        if (!$this->isPrebuilt() && app()->environment('production')) {
            $unmet[] = 'Source themes cannot be used in production';
        }

        // Check if theme is built
        if ($this->isPrebuilt() && !$this->isBuilt()) {
            $unmet[] = 'Theme assets have not been built';
        }

        return $unmet;
    }

    /**
     * Get author URL
     */
    public function getAuthorUrl(): ?string
    {
        return $this->extras['author_url'] ?? null;
    }

    /**
     * Get theme license
     */
    public function getLicense(): ?string
    {
        return $this->extras['license'] ?? null;
    }

    /**
     * Get theme homepage URL
     */
    public function getHomepage(): ?string
    {
        return $this->extras['homepage'] ?? null;
    }

    /**
     * Check if current version satisfies requirement
     */
    protected function versionSatisfies(string $current, string $requirement): bool
    {
        // Handle caret (^) version constraints
        if (str_starts_with($requirement, '^')) {
            $minVersion = substr($requirement, 1);
            $parts = explode('.', $minVersion);
            $major = $parts[0];

            return version_compare($current, $minVersion, '>=')
                && version_compare($current, ($major + 1) . '.0.0', '<');
        }

        // Handle tilde (~) version constraints
        if (str_starts_with($requirement, '~')) {
            $minVersion = substr($requirement, 1);
            $parts = explode('.', $minVersion);

            if (count($parts) >= 2) {
                $major = $parts[0];
                $minor = $parts[1];
                return version_compare($current, $minVersion, '>=')
                    && version_compare($current, "{$major}." . ($minor + 1) . '.0', '<');
            }
        }

        // Handle greater than or equal (>=)
        if (str_starts_with($requirement, '>=')) {
            return version_compare($current, substr($requirement, 2), '>=');
        }

        // Handle wildcard (*)
        if ($requirement === '*') {
            return true;
        }

        // Default to exact or >= comparison
        return version_compare($current, $requirement, '>=');
    }
}
