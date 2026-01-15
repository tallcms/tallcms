<?php

declare(strict_types=1);

namespace TallCms\Cms\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use TallCms\Cms\Models\Plugin;
use TallCms\Cms\Services\LicenseProxyClient;
use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\PluginManager;
use TallCms\Cms\Services\PluginMigrationRepository;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Maximum allowed public routes per plugin
     */
    protected const MAX_PUBLIC_ROUTES = 5;

    /**
     * Determine if plugin system is enabled.
     * In standalone mode: always enabled
     * In plugin mode: requires explicit opt-in via config
     */
    protected function isPluginSystemEnabled(): bool
    {
        // Check if running in standalone mode
        if ($this->isStandaloneMode()) {
            return true;
        }

        // In plugin mode, require explicit opt-in
        return config('tallcms.plugin_mode.plugins_enabled', false);
    }

    /**
     * Determine if running in standalone mode
     */
    protected function isStandaloneMode(): bool
    {
        // 1. Explicit config takes precedence
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        // 2. Auto-detect: standalone has .tallcms-standalone marker
        return file_exists(base_path('.tallcms-standalone'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Skip registration entirely if plugin system is not enabled
        if (! $this->isPluginSystemEnabled()) {
            return;
        }

        // Register PluginMigrationRepository as singleton
        $this->app->singleton(PluginMigrationRepository::class);

        // Register PluginManager as singleton
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager;
        });

        // Register plugin manager alias
        $this->app->alias(PluginManager::class, 'plugin.manager');

        // Register license proxy client
        $this->app->singleton(LicenseProxyClient::class, function ($app) {
            return new LicenseProxyClient(
                config('plugin.license.proxy_url', 'https://tallcms.com')
            );
        });

        // Register plugin license service
        $this->app->singleton(PluginLicenseService::class, function ($app) {
            return new PluginLicenseService(
                $app->make(LicenseProxyClient::class),
                $app->make(PluginManager::class)
            );
        });

        // Alias for convenience
        $this->app->alias(PluginLicenseService::class, 'plugin.license');

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
        // Skip boot entirely if plugin system is not enabled
        if (! $this->isPluginSystemEnabled()) {
            return;
        }

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

                // Scan all src/ files for router usage (catches hidden route registration)
                $srcRouterFile = $this->srcFilesContainRouterUsage($plugin);
                if ($srcRouterFile) {
                    Log::error("Plugin src/ file contains router usage - blocked: {$plugin->getFullSlug()}", [
                        'file' => $srcRouterFile,
                    ]);

                    throw new \RuntimeException(
                        "Plugin '{$plugin->getFullSlug()}' contains router usage in {$srcRouterFile}. ".
                        'Routes must only be defined in routes/public.php or routes/web.php.'
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
                if (str_contains($e->getMessage(), 'Route:: calls') || str_contains($e->getMessage(), 'router usage')) {
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

        // Check for direct Route:: calls (case-insensitive - PHP class names are case-insensitive)
        if (preg_match('/\bRoute::/i', $contentWithoutComments)) {
            return true;
        }

        // Check for aliased Route facade (e.g., "use ... Route as R;" then "R::get")
        if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/i', $contentWithoutComments, $matches)) {
            $alias = $matches[1];
            if (preg_match('/\b'.preg_quote($alias, '/').'::/'.'i', $contentWithoutComments)) {
                return true;
            }
        }

        // Check for router instance via app() helper (case-insensitive - PHP function names are case-insensitive)
        if (preg_match('/\bapp\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for router instance via resolve() helper
        if (preg_match('/\bresolve\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for router instance via $this->app container access
        if (preg_match('/\$this\s*->\s*app\s*\[\s*[\'"]router[\'"]\s*\]/i', $contentWithoutComments)) {
            return true;
        }

        // Check for direct Router class usage (FQCN with leading backslash)
        if (preg_match('/\\\\Illuminate\\\\Routing\\\\Router\b/i', $contentWithoutComments)) {
            return true;
        }

        // Check for imported Router class (use Illuminate\Routing\Router;)
        if (preg_match('/\buse\s+Illuminate\\\\Routing\\\\Router\b/i', $contentWithoutComments)) {
            return true;
        }

        // Check for Router::class constant resolution
        if (preg_match('/\b(app|resolve)\s*\(\s*\)?\s*->\s*make\s*\(\s*\\\\?Router::class/i', $contentWithoutComments)) {
            return true;
        }
        if (preg_match('/\bresolve\s*\(\s*\\\\?Router::class\s*\)/i', $contentWithoutComments)) {
            return true;
        }
        if (preg_match('/\bapp\s*\(\s*\\\\?Router::class\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for Route::class constant (enables dynamic dispatch)
        if (preg_match('/\bRoute::class\b/i', $contentWithoutComments)) {
            return true;
        }

        // Check for Route facade class string
        if (preg_match('/[\'"]\\\\?Illuminate\\\\Support\\\\Facades\\\\Route[\'"]/i', $contentWithoutComments)) {
            return true;
        }

        // Check for call_user_func patterns (dynamic dispatch)
        if (preg_match('/\bcall_user_func(_array)?\s*\([^)]*Route/i', $contentWithoutComments)) {
            return true;
        }

        // Check for app()->make('router')
        if (preg_match('/\bapp\s*\(\s*\)\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for App::make('router')
        if (preg_match('/\bApp::\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for Registrar::class usage
        if (preg_match('/\bRegistrar::class\b/i', $contentWithoutComments)) {
            return true;
        }

        // Check for Illuminate\Contracts\Routing\Registrar
        if (preg_match('/\\\\?Illuminate\\\\Contracts\\\\Routing\\\\Registrar\b/i', $contentWithoutComments)) {
            return true;
        }

        // Check for make(Router::class) or make(Registrar::class)
        if (preg_match('/\bmake\s*\(\s*\\\\?(Router|Registrar)::class\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for $this->app->make('router')
        if (preg_match('/\$this\s*->\s*app\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return true;
        }

        // Check for app()['router'] array access
        if (preg_match('/\bapp\s*\(\s*\)\s*\[\s*[\'"]router[\'"]\s*\]/i', $contentWithoutComments)) {
            return true;
        }

        return false;
    }

    /**
     * Router usage patterns to scan for in src/ files (case-insensitive)
     */
    protected const ROUTER_PATTERNS = [
        '/\bRoute::/i' => 'Route::',
        '/\bapp\s*\(\s*[\'"]router[\'"]\s*\)/i' => 'app(\'router\')',
        '/\bresolve\s*\(\s*[\'"]router[\'"]\s*\)/i' => 'resolve(\'router\')',
        '/\$this\s*->\s*app\s*\[\s*[\'"]router[\'"]\s*\]/i' => '$this->app[\'router\']',
        '/\$this\s*->\s*app\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i' => '$this->app->make(\'router\')',
        '/\bapp\s*\(\s*\)\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i' => 'app()->make(\'router\')',
        '/\bapp\s*\(\s*\)\s*\[\s*[\'"]router[\'"]\s*\]/i' => 'app()[\'router\']',
        '/\bApp::\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i' => 'App::make(\'router\')',
        '/\\\\Illuminate\\\\Routing\\\\Router\b/i' => 'Illuminate\\Routing\\Router',
        '/\buse\s+Illuminate\\\\Routing\\\\Router\b/i' => 'Router class import',
        '/\bRoute::class\b/i' => 'Route::class',
        '/\bRouter::class\b/i' => 'Router::class',
        '/\bRegistrar::class\b/i' => 'Registrar::class',
        '/\\\\?Illuminate\\\\Contracts\\\\Routing\\\\Registrar\b/i' => 'Registrar contract',
        '/[\'"]\\\\?Illuminate\\\\Support\\\\Facades\\\\Route[\'"]/i' => 'Route facade class string',
        '/\bcall_user_func(_array)?\s*\([^)]*Route/i' => 'call_user_func with Route',
    ];

    /**
     * Check if any src/ files contain router usage patterns
     * Returns the relative path of the first offending file, or null if clean
     * Results are cached by plugin version + src directory mtime to avoid re-scanning on every request
     */
    protected function srcFilesContainRouterUsage(Plugin $plugin): ?string
    {
        $srcPath = $plugin->getSrcPath();

        if (! File::exists($srcPath) || ! is_dir($srcPath)) {
            return null;
        }

        // Generate cache key based on plugin identity and src directory state
        $cacheKey = $this->getSrcScanCacheKey($plugin, $srcPath);
        $cacheFile = storage_path("framework/cache/plugin-src-scan-{$cacheKey}.php");

        // Check cache - if result exists and is still valid, use it
        if (File::exists($cacheFile)) {
            $cached = include $cacheFile;
            if (is_array($cached) && isset($cached['result'])) {
                return $cached['result'];
            }
        }

        // Perform the actual scan
        $result = $this->scanSrcFilesForRouterUsage($srcPath);

        // Cache the result
        $this->cacheSrcScanResult($cacheFile, $result);

        return $result;
    }

    /**
     * Generate a cache key for src scan based on plugin version and directory state
     */
    protected function getSrcScanCacheKey(Plugin $plugin, string $srcPath): string
    {
        // Get latest mtime from src directory
        $latestMtime = $this->getLatestMtime($srcPath);

        return md5($plugin->getFullSlug().'|'.$plugin->version.'|'.$latestMtime);
    }

    /**
     * Get the latest modification time from a directory (recursive)
     */
    protected function getLatestMtime(string $path): int
    {
        $latestMtime = File::lastModified($path);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $mtime = $file->getMTime();
                if ($mtime > $latestMtime) {
                    $latestMtime = $mtime;
                }
            }
        }

        return $latestMtime;
    }

    /**
     * Cache src scan result to a file
     */
    protected function cacheSrcScanResult(string $cacheFile, ?string $result): void
    {
        $content = '<?php return '.var_export(['result' => $result, 'cached_at' => time()], true).';';

        // Ensure cache directory exists
        $cacheDir = dirname($cacheFile);
        if (! File::exists($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        File::put($cacheFile, $content);
    }

    /**
     * Perform the actual src file scan for router usage patterns
     */
    protected function scanSrcFilesForRouterUsage(string $srcPath): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = 'src/'.str_replace($srcPath.'/', '', $file->getPathname());
            $content = File::get($file->getPathname());

            // Remove comments before checking
            $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
            $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

            // Check for aliased Route facade (case-insensitive)
            if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/i', $contentWithoutComments, $matches)) {
                $alias = $matches[1];
                if (preg_match('/\b'.preg_quote($alias, '/').'::/'.'i', $contentWithoutComments)) {
                    return $relativePath;
                }
            }

            // Check all router patterns
            foreach (self::ROUTER_PATTERNS as $pattern => $description) {
                if (preg_match($pattern, $contentWithoutComments)) {
                    return $relativePath;
                }
            }
        }

        return null;
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

        // === FILE INCLUSION BYPASS DETECTION ===

        // Block require/include statements (could load files that register routes)
        if (preg_match('/\b(require|require_once|include|include_once)\s*[\(\s]/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'File inclusion (require/include) detected. All routes must be defined directly in the route file.',
            ];
        }

        // === BYPASS DETECTION: Block router instance/alias usage (all case-insensitive) ===

        // Check for aliased Route facade (e.g., "use ... Route as R;" then "R::get")
        if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/i', $contentWithoutComments, $matches)) {
            $alias = $matches[1];
            if (preg_match('/\b'.preg_quote($alias, '/').'::/'.'i', $contentWithoutComments)) {
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
        if (preg_match('/\bapp\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'app(\'router\') usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for resolve('router') usage
        if (preg_match('/\bresolve\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'resolve(\'router\') usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for Router::class resolution (app()->make(Router::class), resolve(Router::class))
        if (preg_match('/\b(app|resolve)\s*\(\s*\)?\s*->\s*make\s*\(\s*\\\\?Router::class/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Router::class resolution detected. Only Route:: facade calls are allowed.',
            ];
        }
        if (preg_match('/\bresolve\s*\(\s*\\\\?Router::class\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'resolve(Router::class) usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for app(Router::class) direct resolution
        if (preg_match('/\bapp\s*\(\s*\\\\?Router::class\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'app(Router::class) usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for app(Registrar::class) direct resolution
        if (preg_match('/\bapp\s*\(\s*\\\\?Registrar::class\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'app(Registrar::class) usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for Illuminate\Routing\Router usage (with or without leading backslash)
        if (preg_match('/\\\\?Illuminate\\\\Routing\\\\Router\b/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Direct Router class usage detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Check for imported Router class usage (use Illuminate\Routing\Router; then Router:: or new Router)
        if (preg_match('/\buse\s+Illuminate\\\\Routing\\\\Router\b/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Router class import detected. Only Route:: facade calls are allowed.',
            ];
        }

        // === DYNAMIC CALL BYPASS DETECTION ===

        // Block Route::class constant (enables dynamic dispatch: $r = Route::class; $r::get())
        if (preg_match('/\bRoute::class\b/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Route::class constant detected. Dynamic route dispatch is not allowed.',
            ];
        }

        // Block Illuminate\Support\Facades\Route as string (for call_user_func, etc.)
        if (preg_match('/[\'"]\\\\?Illuminate\\\\Support\\\\Facades\\\\Route[\'"]/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Route facade class string detected. Dynamic route dispatch is not allowed.',
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

        // Block call_user_func / forward_static_call only when Route-related
        if (preg_match('/\b(call_user_func|call_user_func_array|forward_static_call|forward_static_call_array)\s*\([^)]*Route/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Dynamic function call with Route detected. Only literal Route:: calls are allowed.',
            ];
        }

        // === CONTAINER-BASED ROUTER BYPASS DETECTION ===

        // Block app()->make('router') or app()->make("router")
        if (preg_match('/\bapp\s*\(\s*\)\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'app()->make(\'router\') detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block App::make('router')
        if (preg_match('/\bApp::\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'App::make(\'router\') detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block Registrar::class (Illuminate\Contracts\Routing\Registrar)
        if (preg_match('/\bRegistrar::class\b/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Registrar::class detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block Illuminate\Contracts\Routing\Registrar usage
        if (preg_match('/\\\\?Illuminate\\\\Contracts\\\\Routing\\\\Registrar\b/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Registrar contract detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block container make with Router::class or Registrar::class
        if (preg_match('/\bmake\s*\(\s*\\\\?(Router|Registrar)::class\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'Container make with Router/Registrar class detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block $this->app->make('router') pattern
        if (preg_match('/\$this\s*->\s*app\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => '$this->app->make(\'router\') detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block app()['router'] array access pattern
        if (preg_match('/\bapp\s*\(\s*\)\s*\[\s*[\'"]router[\'"]\s*\]/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => 'app()[\'router\'] array access detected. Only Route:: facade calls are allowed.',
            ];
        }

        // Block $this->app['router'] array access pattern
        if (preg_match('/\$this\s*->\s*app\s*\[\s*[\'"]router[\'"]\s*\]/i', $contentWithoutComments)) {
            return [
                'routes' => [],
                'valid' => false,
                'error' => '$this->app[\'router\'] array access detected. Only Route:: facade calls are allowed.',
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
        $totalRouteCalls = preg_match_all('/\bRoute::[a-zA-Z]+\s*\(/i', $contentWithoutComments);
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
