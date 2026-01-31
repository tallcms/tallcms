<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Plugin
{
    public string $name;

    public string $slug;

    public string $vendor;

    public string $version;

    public string $description;

    public string $author;

    public string $namespace;

    public string $provider;

    public ?string $filamentPlugin = null;

    public array $tags = [];

    public string $path;

    public array $extras = [];

    public bool $licenseRequired = false;

    public function __construct(array $data, string $path)
    {
        $this->name = $data['name'] ?? 'Untitled Plugin';
        $this->slug = $data['slug'] ?? 'untitled';
        $this->vendor = $data['vendor'] ?? 'unknown';
        $this->version = $data['version'] ?? '1.0.0';
        $this->description = $data['description'] ?? '';
        $this->author = $data['author'] ?? 'Unknown';
        $this->namespace = $data['namespace'] ?? '';
        $this->provider = $data['provider'] ?? '';
        $this->filamentPlugin = $data['filament_plugin'] ?? null;
        $this->tags = $data['tags'] ?? [];
        $this->licenseRequired = (bool) ($data['license_required'] ?? false);
        $this->path = $path;

        // Store compatibility info in extras
        $this->extras['compatibility'] = $data['compatibility'] ?? [
            'tallcms' => '*',
            'php' => '^8.2',
            'extensions' => [],
        ];

        // Store routes config in extras (support both 'routes.public' and 'public_routes' formats)
        $this->extras['routes'] = $data['routes'] ?? [
            'public' => [],
        ];

        // Also support the flat 'public_routes' format
        if (isset($data['public_routes'])) {
            $this->extras['routes']['public'] = $data['public_routes'];
        }

        // Store additional metadata
        $this->extras['author_url'] = $data['author_url'] ?? null;
        $this->extras['license'] = $data['license'] ?? null;
        $this->extras['homepage'] = $data['homepage'] ?? null;
        $this->extras['screenshots'] = $data['screenshots'] ?? [
            'primary' => null,
            'gallery' => [],
        ];

        // Store view namespace (defaults to vendor-slug format)
        $this->extras['view_namespace'] = $data['view_namespace'] ?? null;
    }

    /**
     * Get the view namespace for this plugin
     * Used for theme override lookups
     */
    public function getViewNamespace(): string
    {
        // Use explicit view_namespace if set in plugin.json
        if (! empty($this->extras['view_namespace'])) {
            return $this->extras['view_namespace'];
        }

        // Default to vendor-slug format (e.g., tallcms-helloworld)
        return "{$this->vendor}-{$this->slug}";
    }

    /**
     * Get plugin from directory
     */
    public static function fromDirectory(string $path): ?self
    {
        $pluginJsonPath = $path.'/plugin.json';

        if (! File::exists($pluginJsonPath)) {
            return null;
        }

        try {
            $pluginData = json_decode(File::get($pluginJsonPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return new self($pluginData, $path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all available plugins
     */
    public static function all(): Collection
    {
        if (app()->bound('plugin.manager')) {
            return app('plugin.manager')->getInstalledPlugins();
        }

        return self::discoverFromFilesystem();
    }

    /**
     * Discover plugins directly from filesystem
     */
    protected static function discoverFromFilesystem(): Collection
    {
        $pluginsPath = config('tallcms.plugins.path') ?? base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return collect();
        }

        $plugins = collect();

        // Scan vendor directories
        foreach (File::directories($pluginsPath) as $vendorDir) {
            // Scan plugin directories within each vendor
            foreach (File::directories($vendorDir) as $pluginDir) {
                $plugin = self::fromDirectory($pluginDir);
                if ($plugin) {
                    $plugins->push($plugin);
                }
            }
        }

        return $plugins;
    }

    /**
     * Find plugin by vendor and slug
     */
    public static function find(string $vendor, string $slug): ?self
    {
        return self::all()->first(function ($plugin) use ($vendor, $slug) {
            return $plugin->vendor === $vendor && $plugin->slug === $slug;
        });
    }

    /**
     * Find plugin by full slug (vendor/slug)
     */
    public static function findByFullSlug(string $fullSlug): ?self
    {
        $parts = explode('/', $fullSlug, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return self::find($parts[0], $parts[1]);
    }

    /**
     * Get full slug (vendor/slug)
     */
    public function getFullSlug(): string
    {
        return "{$this->vendor}/{$this->slug}";
    }

    /**
     * Check if plugin requires a license
     */
    public function requiresLicense(): bool
    {
        return $this->licenseRequired;
    }

    /**
     * Get the license slug for this plugin
     * Used for license activation/validation
     */
    public function getLicenseSlug(): string
    {
        return $this->getFullSlug();
    }

    /**
     * Get plugin's source path
     */
    public function getSrcPath(string $subPath = ''): string
    {
        return $this->path.'/src'.($subPath ? '/'.$subPath : '');
    }

    /**
     * Get plugin's migration path
     */
    public function getMigrationPath(): string
    {
        return $this->path.'/database/migrations';
    }

    /**
     * Get plugin's config path
     */
    public function getConfigPath(string $configFile = ''): string
    {
        return $this->path.'/config'.($configFile ? '/'.$configFile : '');
    }

    /**
     * Get plugin's view path
     */
    public function getViewPath(string $viewPath = ''): string
    {
        return $this->path.'/resources/views'.($viewPath ? '/'.$viewPath : '');
    }

    /**
     * Get plugin's routes path
     */
    public function getRoutesPath(string $routeFile = ''): string
    {
        return $this->path.'/routes'.($routeFile ? '/'.$routeFile : '');
    }

    /**
     * Get plugin's blocks path
     */
    public function getBlocksPath(): string
    {
        return $this->path.'/src/Blocks';
    }

    /**
     * Check if plugin has migrations
     */
    public function hasMigrations(): bool
    {
        $path = $this->getMigrationPath();

        return File::exists($path) && count(File::files($path)) > 0;
    }

    /**
     * Check if plugin has routes
     */
    public function hasRoutes(): bool
    {
        return File::exists($this->getRoutesPath('public.php'))
            || File::exists($this->getRoutesPath('web.php'));
    }

    /**
     * Check if plugin has public routes
     */
    public function hasPublicRoutes(): bool
    {
        return File::exists($this->getRoutesPath('public.php'));
    }

    /**
     * Check if plugin has prefixed routes
     */
    public function hasPrefixedRoutes(): bool
    {
        return File::exists($this->getRoutesPath('web.php'));
    }

    /**
     * Check if plugin has views
     */
    public function hasViews(): bool
    {
        $path = $this->getViewPath();

        return File::exists($path) && (count(File::files($path)) > 0 || count(File::directories($path)) > 0);
    }

    /**
     * Check if plugin has config
     */
    public function hasConfig(): bool
    {
        $path = $this->getConfigPath();

        return File::exists($path) && count(File::files($path)) > 0;
    }

    /**
     * Check if plugin has blocks
     */
    public function hasBlocks(): bool
    {
        $path = $this->getBlocksPath();

        return File::exists($path) && count(File::files($path)) > 0;
    }

    /**
     * Check if plugin has a Filament plugin class
     */
    public function hasFilamentPlugin(): bool
    {
        return ! empty($this->filamentPlugin);
    }

    /**
     * Get public routes declared in plugin.json
     */
    public function getPublicRoutes(): array
    {
        return $this->extras['routes']['public'] ?? [];
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
     * Check if plugin meets all system requirements
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

        return $unmet;
    }

    /**
     * Get the primary screenshot URL for the plugin
     */
    public function getScreenshotUrl(): ?string
    {
        $primaryScreenshot = $this->extras['screenshots']['primary'] ?? null;

        // Check for configured screenshot in plugin.json
        if ($primaryScreenshot) {
            $screenshotPath = $this->path.'/'.$primaryScreenshot;
            if (File::exists($screenshotPath)) {
                try {
                    $mime = File::mimeType($screenshotPath) ?: 'image/png';
                    $data = base64_encode(File::get($screenshotPath));

                    return "data:{$mime};base64,{$data}";
                } catch (\Throwable $e) {
                    // Continue to fallback
                }
            }
        }

        // Look for common screenshot filenames
        $possibleNames = [
            'screenshot.png',
            'screenshot.jpg',
            'screenshot.jpeg',
            'preview.png',
            'preview.jpg',
            'preview.jpeg',
        ];

        foreach ($possibleNames as $filename) {
            $sourcePath = $this->path.'/'.$filename;
            if (File::exists($sourcePath)) {
                try {
                    $mime = File::mimeType($sourcePath) ?: 'image/png';
                    $data = base64_encode(File::get($sourcePath));

                    return "data:{$mime};base64,{$data}";
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Get author URL
     */
    public function getAuthorUrl(): ?string
    {
        return $this->extras['author_url'] ?? null;
    }

    /**
     * Get plugin license
     */
    public function getLicense(): ?string
    {
        return $this->extras['license'] ?? null;
    }

    /**
     * Get plugin homepage URL
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
        // Handle OR constraints (e.g., "^1.0 || ^2.0")
        if (str_contains($requirement, '||')) {
            $constraints = array_map('trim', explode('||', $requirement));
            foreach ($constraints as $constraint) {
                if ($this->versionSatisfies($current, $constraint)) {
                    return true;
                }
            }

            return false;
        }

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
