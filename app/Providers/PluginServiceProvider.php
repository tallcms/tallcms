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

        // Register PSR-4 autoloading early so Filament can find plugin classes
        // This must happen during register() before AdminPanelProvider's panel() runs
        $this->registerPluginAutoloading();
    }

    /**
     * Register PSR-4 autoloading for all plugins
     * Must run during register() phase for Filament integration
     */
    protected function registerPluginAutoloading(): void
    {
        $pluginManager = $this->app->make(PluginManager::class);

        // Use discover() directly to avoid cache facade during register phase
        foreach ($pluginManager->discover() as $plugin) {
            $pluginManager->registerAutoload($plugin);
        }
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
     * Check if a plugin's provider contains route registration patterns
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

        // Remove comments before checking for route registration patterns
        $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
        $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

        // Check for direct Route:: calls
        if (preg_match('/\bRoute::/', $contentWithoutComments)) {
            return true;
        }

        // Check for aliased Route facade (e.g., "use ... Route as R;" then "R::get")
        if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/', $contentWithoutComments, $matches)) {
            $alias = $matches[1];
            if (preg_match('/\b'.preg_quote($alias, '/').'::/', $contentWithoutComments)) {
                return true;
            }
        }

        // Check for router instance via app() helper
        if (preg_match('/\bapp\s*\(\s*[\'"]router[\'"]\s*\)/', $contentWithoutComments)) {
            return true;
        }

        // Check for router instance via resolve() helper
        if (preg_match('/\bresolve\s*\(\s*[\'"]router[\'"]\s*\)/', $contentWithoutComments)) {
            return true;
        }

        // Check for router instance via $this->app container access
        if (preg_match('/\$this\s*->\s*app\s*\[\s*[\'"]router[\'"]\s*\]/', $contentWithoutComments)) {
            return true;
        }

        // Check for direct Router class usage (FQCN with leading backslash)
        if (preg_match('/\\\\Illuminate\\\\Routing\\\\Router\b/', $contentWithoutComments)) {
            return true;
        }

        // Check for imported Router class (use Illuminate\Routing\Router;)
        if (preg_match('/\buse\s+Illuminate\\\\Routing\\\\Router\b/', $contentWithoutComments)) {
            return true;
        }

        // Check for Router::class constant resolution
        if (preg_match('/\b(app|resolve)\s*\(\s*\)?\s*->\s*make\s*\(\s*\\\\?Router::class/', $contentWithoutComments)) {
            return true;
        }
        if (preg_match('/\bresolve\s*\(\s*\\\\?Router::class\s*\)/', $contentWithoutComments)) {
            return true;
        }
        if (preg_match('/\bapp\s*\(\s*\\\\?Router::class\s*\)/', $contentWithoutComments)) {
            return true;
        }

        // Check for Route::class constant (enables dynamic dispatch)
        if (preg_match('/\bRoute::class\b/', $contentWithoutComments)) {
            return true;
        }

        // Check for Route facade class string
        if (preg_match('/[\'"]\\\\?Illuminate\\\\Support\\\\Facades\\\\Route[\'"]/', $contentWithoutComments)) {
            return true;
        }

        // Check for call_user_func patterns (dynamic dispatch)
        if (preg_match('/\bcall_user_func(_array)?\s*\([^)]*Route/', $contentWithoutComments)) {
            return true;
        }

        return false;
    }

    /**
     * Register plugin view namespaces with theme override support
     */
    protected function registerPluginViewPaths(): void
    {
        $pluginManager = $this->app->make(PluginManager::class);

        foreach ($pluginManager->getInstalledPlugins() as $plugin) {
            $viewPath = $plugin->getViewPath();

            if (File::exists($viewPath)) {
                // Register the canonical plugin namespace (plugin.vendor.slug)
                View::addNamespace(
                    "plugin.{$plugin->vendor}.{$plugin->slug}",
                    $viewPath
                );
            }
        }

        // After all plugin namespaces are registered, prepend theme override paths
        $this->registerThemeOverridesForPlugins();
    }

    /**
     * Register theme override paths for all plugin view namespaces
     * Theme path: themes/{active}/resources/views/vendor/{view-namespace}/
     */
    protected function registerThemeOverridesForPlugins(): void
    {
        // Check if theme manager is available
        if (! $this->app->bound('theme.manager')) {
            return;
        }

        try {
            $themeManager = $this->app->make('theme.manager');
            $activeTheme = $themeManager->getActiveTheme();

            if (! $activeTheme) {
                return;
            }

            $pluginManager = $this->app->make(PluginManager::class);

            foreach ($pluginManager->getInstalledPlugins() as $plugin) {
                $this->registerThemeOverrideForPlugin($plugin, $activeTheme);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to register theme overrides for plugins', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register theme override path for a specific plugin
     */
    protected function registerThemeOverrideForPlugin(Plugin $plugin, $theme): void
    {
        $viewNamespace = $plugin->getViewNamespace();

        // Build theme override path: themes/{slug}/resources/views/vendor/{view-namespace}/
        $themePath = base_path("themes/{$theme->slug}/resources/views/vendor/{$viewNamespace}");

        if (! File::exists($themePath)) {
            return;
        }

        // Prepend theme path to the plugin's view namespace so theme wins
        View::prependNamespace($viewNamespace, $themePath);

        // Also prepend to the canonical plugin namespace
        View::prependNamespace("plugin.{$plugin->vendor}.{$plugin->slug}", $themePath);
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
        $parseResult = $this->parseRouteFile($publicRoutesPath);

        // Check if parsing detected forbidden patterns
        if (! $parseResult['valid']) {
            Log::warning("Plugin public.php blocked: {$parseResult['error']}", [
                'plugin' => $plugin->getFullSlug(),
            ]);

            return;
        }

        $actualRoutes = $parseResult['routes'];

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
     * Allowed Route methods that we can safely parse
     */
    protected const ALLOWED_ROUTE_METHODS = [
        'get', 'post', 'put', 'patch', 'delete', 'options',
    ];

    /**
     * Forbidden Route methods that could bypass guardrails
     */
    protected const FORBIDDEN_ROUTE_METHODS = [
        'any', 'match', 'view', 'redirect', 'resource', 'resources',
        'apiResource', 'apiResources', 'singleton', 'controller',
        'group', 'middleware', 'prefix', 'name', 'domain',
        'fallback', 'permanentRedirect',
    ];

    /**
     * Parse a route file and extract route paths
     * Returns ['routes' => [...], 'valid' => bool, 'error' => string|null]
     */
    protected function parseRouteFile(string $path): array
    {
        $content = File::get($path);

        // Remove comments to avoid false positives
        $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
        $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

        // === BYPASS DETECTION: Block router instance/alias usage ===

        // Check for aliased Route facade (e.g., "use ... Route as R;" then "R::get")
        if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/', $contentWithoutComments, $matches)) {
            $alias = $matches[1];
            if (preg_match('/\b'.preg_quote($alias, '/').'::/', $contentWithoutComments)) {
                return [
                    'routes' => [],
                    'valid' => false,
                    'error' => "Route facade alias detected ({$alias}::). Only Route:: is allowed in route files.",
                ];
            }
        }

        // Check for $router variable usage (any variable ending in router/Router)
        if (preg_match('/\$\w*[rR]outer\s*->\s*(get|post|put|patch|delete|any|match|group)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Router instance variable usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for app('router') or app("router") usage
        if (preg_match('/\bapp\s*\(\s*[\'"]router[\'"]\s*\)/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'app(\'router\') usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for resolve('router') usage
        if (preg_match('/\bresolve\s*\(\s*[\'"]router[\'"]\s*\)/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'resolve(\'router\') usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for Router::class resolution (app()->make(Router::class), resolve(Router::class))
        if (preg_match('/\b(app|resolve)\s*\(\s*\)?\s*->\s*make\s*\(\s*\\\\?Router::class/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Router::class resolution detected. Only Route:: facade calls are allowed.',
            ];
        }
        if (preg_match('/\bresolve\s*\(\s*\\\\?Router::class\s*\)/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'resolve(Router::class) usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for Illuminate\Routing\Router usage (with or without leading backslash)
        if (preg_match('/\\\\?Illuminate\\\\Routing\\\\Router\b/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Direct Router class usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for imported Router class usage (use Illuminate\Routing\Router; then Router:: or new Router)
        if (preg_match('/\buse\s+Illuminate\\\\Routing\\\\Router\b/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Router class import detected. Only Route:: facade calls are allowed.',
            ];
        }

        // === DYNAMIC CALL BYPASS DETECTION ===

        // Block Route::class constant (enables dynamic dispatch: $r = Route::class; $r::get())
        if (preg_match('/\bRoute::class\b/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Route::class constant detected. Dynamic route dispatch is not allowed.',
            ];
        }

        // Block Illuminate\Support\Facades\Route as string (for call_user_func, etc.)
        if (preg_match('/[\'"]\\\\?Illuminate\\\\Support\\\\Facades\\\\Route[\'"]/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Route facade class string detected. Dynamic route dispatch is not allowed.',
            ];
        }

        // Block call_user_func / call_user_func_array with any Route-related patterns
        if (preg_match('/\bcall_user_func(_array)?\s*\(/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'call_user_func detected. Dynamic function calls are not allowed in route files.',
            ];
        }

        // Block variable-based class dispatch ($class::method pattern where $class could be Route)
        if (preg_match('/\$\w+::(get|post|put|patch|delete|any|match|group)\s*\(/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Variable-based static method call detected. Only literal Route:: calls are allowed.',
            ];
        }

        // Block forward_static_call patterns
        if (preg_match('/\bforward_static_call(_array)?\s*\(/', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'forward_static_call detected. Dynamic calls are not allowed in route files.',
            ];
        }

        // === FORBIDDEN ROUTE METHODS ===

        // Check for forbidden Route methods that could bypass guardrails
        foreach (self::FORBIDDEN_ROUTE_METHODS as $method) {
            if (preg_match('/\bRoute::'.$method.'\s*\(/i', $contentWithoutComments)) {
                return [
                    'routes' => [],
                    'valid' => false,
                    'error' => "Forbidden route method detected: Route::{$method}(). Only basic HTTP methods are allowed.",
                ];
            }
        }

        // Check for chained route definitions (e.g., Route::get()->name()->middleware())
        // These are fine, but Route::middleware()->group() is not
        if (preg_match('/\bRoute::(middleware|prefix|name|domain)\s*\([^)]*\)\s*->\s*(group|get|post)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Route grouping/chaining detected. Only simple route definitions are allowed.',
            ];
        }

        // === PARSE VALID ROUTES ===

        // Parse allowed route methods
        $routes = [];
        $allowedMethodsPattern = implode('|', self::ALLOWED_ROUTE_METHODS);
        $pattern = '/Route::('.$allowedMethodsPattern.')\s*\(\s*[\'"]([^\'"]+)[\'"]/i';

        if (preg_match_all($pattern, $contentWithoutComments, $matches)) {
            $routes = array_unique($matches[2]);
        }

        // Count total Route:: calls vs parsed routes to detect unparseable patterns
        $totalRouteCalls = preg_match_all('/\bRoute::[a-zA-Z]+\s*\(/', $contentWithoutComments);
        if ($totalRouteCalls > count($routes)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Detected Route:: calls that could not be parsed. All routes must use simple Route::method(\'/path\', ...) format.',
            ];
        }

        return [
            'routes' => array_values($routes),
            'valid' => true,
            'error' => null,
        ];
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
