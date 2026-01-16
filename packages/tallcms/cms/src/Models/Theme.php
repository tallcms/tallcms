<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Theme
{
    /**
     * CANONICAL PRESET LIST - single source of truth
     * Update this when daisyUI adds/removes themes
     * See: https://daisyui.com/docs/themes/
     */
    public const ALL_DAISYUI_PRESETS = [
        'light', 'dark', 'cupcake', 'bumblebee', 'emerald', 'corporate',
        'synthwave', 'retro', 'cyberpunk', 'valentine', 'halloween',
        'garden', 'forest', 'aqua', 'lofi', 'pastel', 'fantasy',
        'wireframe', 'black', 'luxury', 'dracula', 'cmyk', 'autumn',
        'business', 'acid', 'lemonade', 'night', 'coffee', 'winter',
        'dim', 'nord', 'sunset', 'caramellatte', 'abyss', 'silk',
    ];

    /**
     * DaisyUI preset color palettes (main colors only)
     * See: https://daisyui.com/docs/colors/
     */
    public const DAISYUI_PRESET_COLORS = [
        'light' => ['primary' => '#570df8', 'secondary' => '#f000b8', 'accent' => '#37cdbe', 'neutral' => '#3d4451', 'base-100' => '#ffffff'],
        'dark' => ['primary' => '#661ae6', 'secondary' => '#d926aa', 'accent' => '#1fb2a5', 'neutral' => '#2a323c', 'base-100' => '#1d232a'],
        'cupcake' => ['primary' => '#65c3c8', 'secondary' => '#ef9fbc', 'accent' => '#eeaf3a', 'neutral' => '#291334', 'base-100' => '#faf7f5'],
        'bumblebee' => ['primary' => '#e0a82e', 'secondary' => '#f9d72f', 'accent' => '#181830', 'neutral' => '#181830', 'base-100' => '#ffffff'],
        'emerald' => ['primary' => '#66cc8a', 'secondary' => '#377cfb', 'accent' => '#ea5234', 'neutral' => '#333c4d', 'base-100' => '#ffffff'],
        'corporate' => ['primary' => '#4b6bfb', 'secondary' => '#7b92b2', 'accent' => '#67cba0', 'neutral' => '#181a2a', 'base-100' => '#ffffff'],
        'synthwave' => ['primary' => '#e779c1', 'secondary' => '#58c7f3', 'accent' => '#f3cc30', 'neutral' => '#20134e', 'base-100' => '#1a103d'],
        'retro' => ['primary' => '#ef9995', 'secondary' => '#a4cbb4', 'accent' => '#ebdc99', 'neutral' => '#7d7259', 'base-100' => '#e4d8b4'],
        'cyberpunk' => ['primary' => '#ff7598', 'secondary' => '#75d1f0', 'accent' => '#c07eec', 'neutral' => '#423f00', 'base-100' => '#ffee00'],
        'valentine' => ['primary' => '#e96d7b', 'secondary' => '#a991f7', 'accent' => '#88dbdd', 'neutral' => '#af4670', 'base-100' => '#fae7f4'],
        'halloween' => ['primary' => '#f28c18', 'secondary' => '#6d3a9c', 'accent' => '#51a800', 'neutral' => '#212121', 'base-100' => '#212121'],
        'garden' => ['primary' => '#5c7f67', 'secondary' => '#ecf4e7', 'accent' => '#fae5e5', 'neutral' => '#5d5656', 'base-100' => '#e9e7e7'],
        'forest' => ['primary' => '#1eb854', 'secondary' => '#1fd65f', 'accent' => '#d99330', 'neutral' => '#110e0e', 'base-100' => '#171212'],
        'aqua' => ['primary' => '#09ecf3', 'secondary' => '#966fb3', 'accent' => '#ffe999', 'neutral' => '#3b8ac4', 'base-100' => '#345da7'],
        'lofi' => ['primary' => '#0d0d0d', 'secondary' => '#1a1a1a', 'accent' => '#262626', 'neutral' => '#000000', 'base-100' => '#ffffff'],
        'pastel' => ['primary' => '#d1c1d7', 'secondary' => '#f6cbd1', 'accent' => '#b4e9d6', 'neutral' => '#70acc7', 'base-100' => '#ffffff'],
        'fantasy' => ['primary' => '#6e0b75', 'secondary' => '#007ebd', 'accent' => '#f8860d', 'neutral' => '#1f2937', 'base-100' => '#ffffff'],
        'wireframe' => ['primary' => '#b8b8b8', 'secondary' => '#b8b8b8', 'accent' => '#b8b8b8', 'neutral' => '#ebebeb', 'base-100' => '#ffffff'],
        'black' => ['primary' => '#343232', 'secondary' => '#343232', 'accent' => '#343232', 'neutral' => '#272626', 'base-100' => '#000000'],
        'luxury' => ['primary' => '#ffffff', 'secondary' => '#152747', 'accent' => '#513448', 'neutral' => '#331800', 'base-100' => '#09090b'],
        'dracula' => ['primary' => '#ff79c6', 'secondary' => '#bd93f9', 'accent' => '#ffb86c', 'neutral' => '#414558', 'base-100' => '#282a36'],
        'cmyk' => ['primary' => '#45aeee', 'secondary' => '#e8488a', 'accent' => '#fff232', 'neutral' => '#1a1a1a', 'base-100' => '#ffffff'],
        'autumn' => ['primary' => '#8c0327', 'secondary' => '#d85251', 'accent' => '#d59b6a', 'neutral' => '#826a5c', 'base-100' => '#f1f1f1'],
        'business' => ['primary' => '#1c4f82', 'secondary' => '#7c909a', 'accent' => '#eb6b47', 'neutral' => '#23282f', 'base-100' => '#212121'],
        'acid' => ['primary' => '#ff00f4', 'secondary' => '#ff7400', 'accent' => '#cbfd03', 'neutral' => '#1b1d1d', 'base-100' => '#fafafa'],
        'lemonade' => ['primary' => '#519903', 'secondary' => '#e9e92e', 'accent' => '#f7f9ca', 'neutral' => '#191a3e', 'base-100' => '#ffffff'],
        'night' => ['primary' => '#38bdf8', 'secondary' => '#818cf8', 'accent' => '#f471b5', 'neutral' => '#1e293b', 'base-100' => '#0f172a'],
        'coffee' => ['primary' => '#db924b', 'secondary' => '#6f4e37', 'accent' => '#10576d', 'neutral' => '#120c12', 'base-100' => '#20161f'],
        'winter' => ['primary' => '#047aff', 'secondary' => '#463aa1', 'accent' => '#c148ac', 'neutral' => '#021431', 'base-100' => '#ffffff'],
        'dim' => ['primary' => '#9fe88d', 'secondary' => '#ff7d5c', 'accent' => '#c792e9', 'neutral' => '#1c212b', 'base-100' => '#2a303c'],
        'nord' => ['primary' => '#5e81ac', 'secondary' => '#81a1c1', 'accent' => '#88c0d0', 'neutral' => '#4c566a', 'base-100' => '#eceff4'],
        'sunset' => ['primary' => '#ff865b', 'secondary' => '#fd6f9c', 'accent' => '#b387fa', 'neutral' => '#1a103d', 'base-100' => '#121c22'],
        'caramellatte' => ['primary' => '#803d27', 'secondary' => '#e48f70', 'accent' => '#f1d5a8', 'neutral' => '#1c1917', 'base-100' => '#f5f0eb'],
        'abyss' => ['primary' => '#6366f1', 'secondary' => '#818cf8', 'accent' => '#c4b5fd', 'neutral' => '#0f172a', 'base-100' => '#020617'],
        'silk' => ['primary' => '#4e6577', 'secondary' => '#a8c1d1', 'accent' => '#e6d5c3', 'neutral' => '#2f3e46', 'base-100' => '#f8f6f4'],
    ];

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

    /**
     * Whether this theme is bundled with the package (not user-installed)
     */
    public bool $bundled = false;

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

        // Store daisyUI configuration
        $this->extras['daisyui'] = $data['daisyui'] ?? [];
    }

    // =========================================================================
    // DaisyUI Theme Methods
    // =========================================================================

    /**
     * Get the active daisyUI preset name
     */
    public function getDaisyUIPreset(): string
    {
        return $this->extras['daisyui']['preset'] ?? 'light';
    }

    /**
     * Get the dark mode preset name (nullable)
     */
    public function getDaisyUIPrefersDark(): ?string
    {
        return $this->extras['daisyui']['prefersDark'] ?? null;
    }

    /**
     * Get all available daisyUI presets for this theme
     */
    public function getDaisyUIPresets(): array
    {
        $presets = $this->extras['daisyui']['presets'] ?? null;

        // Handle "all" string - return canonical list
        if ($presets === 'all') {
            return self::ALL_DAISYUI_PRESETS;
        }

        // Handle array
        if (is_array($presets)) {
            return $presets;
        }

        // Default: just the active preset (and dark if specified)
        $result = [$this->getDaisyUIPreset()];
        $dark = $this->getDaisyUIPrefersDark();
        if ($dark && $dark !== $this->getDaisyUIPreset()) {
            $result[] = $dark;
        }

        return $result;
    }

    /**
     * Check if theme has a custom daisyUI theme definition
     */
    public function hasCustomDaisyUITheme(): bool
    {
        return isset($this->extras['daisyui']['custom']);
    }

    /**
     * Get the custom daisyUI theme configuration
     */
    public function getDaisyUICustomTheme(): ?array
    {
        return $this->extras['daisyui']['custom'] ?? null;
    }

    /**
     * Get daisyUI theme colors for display
     * Returns colors from: theme.json > CSS parsing (custom) > preset colors
     */
    public function getDaisyUIColors(): ?array
    {
        // First check theme.json for explicit colors
        if (! empty($this->extras['daisyui']['colors'])) {
            return $this->extras['daisyui']['colors'];
        }

        // For custom themes, parse colors from CSS file
        if ($this->hasCustomDaisyUITheme()) {
            return $this->parseColorsFromCss();
        }

        // For preset-based themes, return preset colors
        $preset = $this->getDaisyUIPreset();
        if (isset(self::DAISYUI_PRESET_COLORS[$preset])) {
            return self::DAISYUI_PRESET_COLORS[$preset];
        }

        return null;
    }

    /**
     * Parse daisyUI colors from the theme's CSS file
     */
    protected function parseColorsFromCss(): ?array
    {
        $cssPath = $this->path.'/resources/css/app.css';

        if (! file_exists($cssPath)) {
            return null;
        }

        $css = file_get_contents($cssPath);
        $colors = [];

        // Match color definitions like: --color-primary: oklch(55% 0.3 260);
        $colorNames = ['primary', 'secondary', 'accent', 'neutral', 'base-100', 'info', 'success', 'warning', 'error'];

        foreach ($colorNames as $name) {
            if (preg_match('/--color-'.preg_quote($name, '/').':\s*([^;]+);/', $css, $match)) {
                $colors[$name] = trim($match[1]);
            }
        }

        return ! empty($colors) ? $colors : null;
    }

    /**
     * Check if theme supports runtime theme switching (theme-controller)
     */
    public function supportsThemeController(): bool
    {
        return count($this->getDaisyUIPresets()) > 1;
    }

    /**
     * Get preset list as CSS string for @plugin "daisyui" themes: directive
     *
     * @param  string  $default  The default theme preset
     * @param  string|null  $prefersDark  The dark mode preset (nullable)
     */
    public static function getPresetsCssString(string $default = 'light', ?string $prefersDark = null): string
    {
        $presets = collect(self::ALL_DAISYUI_PRESETS)
            ->map(function ($preset) use ($default, $prefersDark) {
                if ($preset === $default) {
                    return "{$preset} --default";
                }
                if ($prefersDark && $preset === $prefersDark) {
                    return "{$preset} --prefersdark";
                }

                return $preset;
            });

        return $presets->implode(', ');
    }

    /**
     * Get theme from directory
     */
    public static function fromDirectory(string $path): ?self
    {
        $themeJsonPath = $path.'/theme.json';

        if (! File::exists($themeJsonPath)) {
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
        $themesPath = config('tallcms.plugin_mode.themes_path') ?? base_path('themes');

        if (! File::exists($themesPath)) {
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
        return $this->path.'/resources/views'.($viewPath ? '/'.$viewPath : '');
    }

    /**
     * Get theme's public path
     */
    public function getPublicPath(string $assetPath = ''): string
    {
        return $this->path.'/public'.($assetPath ? '/'.$assetPath : '');
    }

    /**
     * Get theme's resource path
     */
    public function getResourcePath(string $resourcePath = ''): string
    {
        return $this->path.'/resources'.($resourcePath ? '/'.$resourcePath : '');
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
        if (! $this->parent) {
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
        while ($current->hasParent() && ! in_array($current->parent, $visited)) {
            $parent = $current->getParent();
            if (! $parent) {
                break;
            }

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
        $fullPath = $this->path.'/resources/views/'.str_replace('.', '/', $viewPath).'.blade.php';
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
            $screenshotPath = $this->path.'/public/'.$primaryScreenshot;
            $candidatePaths[] = [
                'asset' => $primaryScreenshot,
                'source' => $screenshotPath,
            ];

            // Also check if the path already includes public/
            if (str_starts_with($primaryScreenshot, 'public/')) {
                $assetPath = substr($primaryScreenshot, 7);
                $fullPath = $this->path.'/'.$primaryScreenshot;
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
                'source' => $this->path.'/public/'.$filename,
            ];
        }

        foreach ($candidatePaths as $paths) {
            $sourcePath = $paths['source'];
            $assetPath = ltrim($paths['asset'], '/');

            if (! File::exists($sourcePath)) {
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
            $fullPath = $this->path.'/public/'.$screenshot;
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
            if (app()->bound(\TallCms\Cms\Services\ThemeManager::class)) {
                app(\TallCms\Cms\Services\ThemeManager::class)->publishThemeAssets($this);
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
        // Vite 7 puts manifest in .vite/ subdirectory, Vite 6 and earlier put it directly in build/
        $vite7ManifestPath = $this->path.'/public/build/.vite/manifest.json';
        $legacyManifestPath = $this->path.'/public/build/manifest.json';

        return File::exists($vite7ManifestPath) || File::exists($legacyManifestPath);
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
        if (! empty($compatibility['php'])) {
            $requiredPhp = $compatibility['php'];
            if (! $this->versionSatisfies(PHP_VERSION, $requiredPhp)) {
                return false;
            }
        }

        // Check PHP extensions
        if (! empty($compatibility['extensions'])) {
            foreach ($compatibility['extensions'] as $extension) {
                if (! extension_loaded($extension)) {
                    return false;
                }
            }
        }

        // Check TallCMS version
        if (! empty($compatibility['tallcms']) && $compatibility['tallcms'] !== '*') {
            if (! $this->versionSatisfies(self::getTallcmsVersion(), $compatibility['tallcms'])) {
                return false;
            }
        }

        // Check prebuilt requirement in production
        if (! $this->isPrebuilt() && app()->environment('production')) {
            return false;
        }

        // Check if theme is built when prebuilt is expected
        if ($this->isPrebuilt() && ! $this->isBuilt()) {
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
        if (! empty($compatibility['php'])) {
            $requiredPhp = $compatibility['php'];
            if (! $this->versionSatisfies(PHP_VERSION, $requiredPhp)) {
                $unmet[] = "Requires PHP {$requiredPhp}, current is ".PHP_VERSION;
            }
        }

        // Check PHP extensions
        if (! empty($compatibility['extensions'])) {
            foreach ($compatibility['extensions'] as $extension) {
                if (! extension_loaded($extension)) {
                    $unmet[] = "Requires PHP extension: {$extension}";
                }
            }
        }

        // Check TallCMS version
        if (! empty($compatibility['tallcms']) && $compatibility['tallcms'] !== '*') {
            $currentTallcms = self::getTallcmsVersion();
            if (! $this->versionSatisfies($currentTallcms, $compatibility['tallcms'])) {
                $unmet[] = "Requires TallCMS {$compatibility['tallcms']}, current is {$currentTallcms}";
            }
        }

        // Check prebuilt requirement in production
        if (! $this->isPrebuilt() && app()->environment('production')) {
            $unmet[] = 'Source themes cannot be used in production';
        }

        // Check if theme is built
        if ($this->isPrebuilt() && ! $this->isBuilt()) {
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
                && version_compare($current, ($major + 1).'.0.0', '<');
        }

        // Handle tilde (~) version constraints
        if (str_starts_with($requirement, '~')) {
            $minVersion = substr($requirement, 1);
            $parts = explode('.', $minVersion);

            if (count($parts) >= 2) {
                $major = $parts[0];
                $minor = $parts[1];

                return version_compare($current, $minVersion, '>=')
                    && version_compare($current, "{$major}.".($minor + 1).'.0', '<');
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
