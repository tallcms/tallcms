<?php

namespace App\Providers;

use App\Models\Plugin;
use App\Services\PluginManager;
use App\Services\PluginMigrationRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Maximum allowed public routes per plugin
     */
    protected const MAX_PUBLIC_ROUTES = 5;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PluginMigrationRepository as singleton
        $this->app->singleton(PluginMigrationRepository::class);

        // Register PluginManager as singleton
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager;
        });

        // Register plugin manager alias
        $this->app->alias(PluginManager::class, 'plugin.manager');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure plugins directory exists
        $this->ensurePluginsDirectory();

        // Boot all installed plugins (with route detection)
        $this->bootInstalledPlugins();

        // Register plugin view namespaces
        $this->registerPluginViewPaths();

        // Load plugin routes (after providers are booted)
        $this->loadPluginRoutes();
    }

    /**
     * Ensure plugins directory exists
     */
    protected function ensurePluginsDirectory(): void
    {
        $pluginManager = $this->app->make(PluginManager::class);
        $pluginManager->ensurePluginsDirectoryExists();
    }

    /**
     * Boot all installed plugins with route detection
     */
    protected function bootInstalledPlugins(): void
    {
        $pluginManager = $this->app->make(PluginManager::class);

        foreach ($pluginManager->getInstalledPlugins() as $plugin) {
            try {
                // Register PSR-4 autoloading
                $pluginManager->registerAutoload($plugin);

                // Scan provider for Route:: calls before booting (hard fail)
                if ($this->providerRegistersRoutes($plugin)) {
                    Log::error("Plugin provider contains Route:: calls - blocked: {$plugin->getFullSlug()}");

                    throw new \RuntimeException(
                        "Plugin '{$plugin->getFullSlug()}' provider contains Route:: calls. ".
                        'Plugins must use routes/public.php or routes/web.php for route registration.'
                    );
                }

                // Boot the plugin's service provider
                $pluginManager->bootPlugin($plugin);
            } catch (\Throwable $e) {
                Log::error("Failed to boot plugin {$plugin->getFullSlug()}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Re-throw to prevent plugin from loading with potentially unsafe state
                if (str_contains($e->getMessage(), 'Route:: calls')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Check if a plugin's provider contains Route:: calls
     */
    protected function providerRegistersRoutes(Plugin $plugin): bool
    {
        if (empty($plugin->provider)) {
            return false;
        }

        // Build path to provider file
        $providerClass = $plugin->provider;
        $relativePath = str_replace('\\', '/', substr($providerClass, strlen($plugin->namespace) + 1)).'.php';
        $providerPath = $plugin->getSrcPath().'/'.$relativePath;

        if (! File::exists($providerPath)) {
            // Try alternative path (just the class name)
            $providerPath = $plugin->path.'/src/'.basename(str_replace('\\', '/', $providerClass)).'.php';
        }

        if (! File::exists($providerPath)) {
            return false;
        }

        $content = File::get($providerPath);

        // Check for Route:: facade calls (excluding comments)
        // Remove single-line comments first
        $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
        // Remove multi-line comments
        $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

        return (bool) preg_match('/\bRoute::/', $contentWithoutComments);
    }

    /**
     * Register plugin view namespaces
     */
    protected function registerPluginViewPaths(): void
    {
        $pluginManager = $this->app->make(PluginManager::class);

        foreach ($pluginManager->getInstalledPlugins() as $plugin) {
            $viewPath = $plugin->getViewPath();

            if (File::exists($viewPath)) {
                View::addNamespace(
                    "plugin.{$plugin->vendor}.{$plugin->slug}",
                    $viewPath
                );
            }
        }
    }

    /**
     * Load plugin routes with guardrails
     */
    protected function loadPluginRoutes(): void
    {
        $pluginManager = $this->app->make(PluginManager::class);

        foreach ($pluginManager->getInstalledPlugins() as $plugin) {
            try {
                // Load public routes (no prefix) - with strict validation
                if ($plugin->hasPublicRoutes()) {
                    $this->loadPublicRoutes($plugin);
                }

                // Load prefixed routes
                if ($plugin->hasPrefixedRoutes()) {
                    $this->loadPrefixedRoutes($plugin);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to load routes for plugin {$plugin->getFullSlug()}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Load public routes for a plugin with strict validation
     */
    protected function loadPublicRoutes(Plugin $plugin): void
    {
        $publicRoutesPath = $plugin->getRoutesPath('public.php');

        if (! File::exists($publicRoutesPath)) {
            return;
        }

        $declaredRoutes = $plugin->getPublicRoutes();

        // Enforce max public routes limit
        if (count($declaredRoutes) > self::MAX_PUBLIC_ROUTES) {
            Log::warning("Plugin {$plugin->getFullSlug()} exceeds max public routes limit (".self::MAX_PUBLIC_ROUTES.')');

            return;
        }

        // Validate all declared paths are safe
        foreach ($declaredRoutes as $path) {
            if (! $this->isRouteSafe($path)) {
                Log::warning("Unsafe public route path blocked: {$path}", [
                    'plugin' => $plugin->getFullSlug(),
                ]);

                return;
            }
        }

        // Parse the route file and extract actual routes
        $actualRoutes = $this->parseRouteFile($publicRoutesPath);

        // Verify every route in the file is declared in the whitelist
        foreach ($actualRoutes as $route) {
            if (! in_array($route, $declaredRoutes, true)) {
                Log::warning("Undeclared route in public.php blocked: {$route}", [
                    'plugin' => $plugin->getFullSlug(),
                    'declared' => $declaredRoutes,
                    'actual' => $actualRoutes,
                ]);

                return;
            }
        }

        // All routes validated - safe to load
        Route::middleware(['web', 'throttle:60,1'])
            ->name("plugin.{$plugin->vendor}.{$plugin->slug}.")
            ->group($publicRoutesPath);
    }

    /**
     * Parse a route file and extract route paths
     */
    protected function parseRouteFile(string $path): array
    {
        $content = File::get($path);
        $routes = [];

        // Match Route::get('/path', ...), Route::post('/path', ...), etc.
        // Supports both single and double quotes
        $pattern = '/Route::(get|post|put|patch|delete|options|any|match)\s*\(\s*[\'"]([^\'"]+)[\'"]/i';

        if (preg_match_all($pattern, $content, $matches)) {
            $routes = array_unique($matches[2]);
        }

        return array_values($routes);
    }

    /**
     * Load prefixed routes for a plugin
     */
    protected function loadPrefixedRoutes(Plugin $plugin): void
    {
        $webRoutesPath = $plugin->getRoutesPath('web.php');

        if (! File::exists($webRoutesPath)) {
            return;
        }

        Route::middleware(['web', 'throttle:60,1'])
            ->name("plugin.{$plugin->vendor}.{$plugin->slug}.")
            ->prefix("_plugins/{$plugin->vendor}/{$plugin->slug}")
            ->group($webRoutesPath);
    }

    /**
     * Check if a public route path is safe
     */
    protected function isRouteSafe(string $path): bool
    {
        // Must start with /
        if (! str_starts_with($path, '/')) {
            return false;
        }

        // Cannot be root
        if ($path === '/') {
            return false;
        }

        // Cannot be admin or api routes
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
            return false;
        }

        // Cannot be install route
        if (str_starts_with($path, '/install')) {
            return false;
        }

        // Cannot contain parameters
        if (str_contains($path, '{')) {
            return false;
        }

        // Cannot contain wildcards
        if (str_contains($path, '*')) {
            return false;
        }

        return true;
    }
}
