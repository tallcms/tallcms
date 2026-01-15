<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\File;
use TallCms\Cms\Models\Plugin;
use ZipArchive;

class PluginValidator
{
    /**
     * Required files for a valid plugin
     */
    protected array $requiredFiles = [
        'plugin.json',
    ];

    /**
     * Required fields in plugin.json
     */
    protected array $requiredFields = [
        'name',
        'slug',
        'vendor',
        'version',
        'description',
        'author',
        'namespace',
        'provider',
    ];

    /**
     * Allowed PHP file paths (whitelist)
     */
    protected array $allowedPhpPaths = [
        'src/',                      // Plugin source code
        'database/migrations/',      // Migrations (flat only)
        'routes/public.php',         // Public routes file
        'routes/web.php',            // Prefixed routes file
    ];

    /**
     * Blocked directories
     */
    protected array $blockedDirectories = [
        'vendor/',
        'bootstrap/',
    ];

    /**
     * Forbidden file patterns
     */
    protected array $forbiddenPatterns = [
        '.htaccess',
        '.env',
        '.env.*',
    ];

    /**
     * Dangerous route patterns to scan for
     */
    protected array $dangerousRoutePatterns = [
        'Route::fallback',
        "Route::any('/')",
        'Route::any("/")',
        "Route::get('/')",
        'Route::get("/")',
        'Route::domain(',
    ];

    /**
     * Validate a plugin directory before operations (preflight check)
     */
    public function preflightCheck(Plugin $plugin): PluginValidationResult
    {
        $errors = [];
        $warnings = [];

        // Verify plugin directory exists
        if (! File::exists($plugin->path)) {
            return PluginValidationResult::failed(['Plugin directory not found: '.$plugin->path]);
        }

        // Verify plugin.json exists and is readable
        $pluginJsonPath = "{$plugin->path}/plugin.json";
        if (! File::exists($pluginJsonPath)) {
            return PluginValidationResult::failed(['plugin.json is missing']);
        }

        // Parse plugin.json
        $pluginData = json_decode(File::get($pluginJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return PluginValidationResult::failed(['plugin.json contains invalid JSON: '.json_last_error_msg()]);
        }

        // Validate required fields
        foreach ($this->requiredFields as $field) {
            if (empty($pluginData[$field])) {
                $errors[] = "Missing required field in plugin.json: {$field}";
            }
        }

        // Check compatibility
        $compatResult = $this->checkCompatibility($pluginData);
        $errors = array_merge($errors, $compatResult->errors);
        $warnings = array_merge($warnings, $compatResult->warnings);

        // Scan for forbidden files
        $forbiddenFiles = $this->scanForForbiddenFiles($plugin->path);
        foreach ($forbiddenFiles as $file) {
            $errors[] = "Forbidden file found: {$file}";
        }

        // Scan provider for Route:: calls
        $routeErrors = $this->scanProviderForRoutes($plugin);
        $errors = array_merge($errors, $routeErrors);

        // Scan all src/ files for router usage (catches hidden route registration)
        $srcRouteErrors = $this->scanSrcForRouterUsage($plugin->getSrcPath());
        $errors = array_merge($errors, $srcRouteErrors);

        return new PluginValidationResult(
            empty($errors),
            $errors,
            $warnings,
            $pluginData
        );
    }

    /**
     * Validate a ZIP file before extraction
     */
    public function validateZip(string $zipPath): PluginValidationResult
    {
        $errors = [];
        $warnings = [];
        $pluginData = [];

        if (! File::exists($zipPath)) {
            return PluginValidationResult::failed(['ZIP file not found']);
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            return PluginValidationResult::failed(['Unable to open ZIP file: error code '.$openResult]);
        }

        try {
            // Check ZIP size (prevent ZIP bombs)
            $totalSize = 0;
            $fileCount = 0;
            $maxSize = 100 * 1024 * 1024; // 100MB uncompressed limit
            $maxFiles = 5000;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $totalSize += $stat['size'];
                $fileCount++;

                if ($totalSize > $maxSize) {
                    $errors[] = 'ZIP file exceeds maximum uncompressed size (100MB)';
                    break;
                }

                if ($fileCount > $maxFiles) {
                    $errors[] = 'ZIP file contains too many files (max 5000)';
                    break;
                }
            }

            // Check for symlinks
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                // Check external attributes for symlink (Unix: 0120000)
                // Note: 'external' key may not exist in all PHP/ZipArchive versions
                if (isset($stat['external']) && (($stat['external'] >> 16) & 0170000) === 0120000) {
                    $errors[] = "Symlink detected in ZIP: {$stat['name']}";
                }
            }

            // Find and validate plugin.json
            $pluginJsonIndex = $this->findPluginJsonInZip($zip);
            if ($pluginJsonIndex === null) {
                $errors[] = 'plugin.json not found in ZIP archive';
            } else {
                $pluginJsonContent = $zip->getFromIndex($pluginJsonIndex);
                $pluginData = json_decode($pluginJsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'plugin.json contains invalid JSON: '.json_last_error_msg();
                } else {
                    // Validate required fields
                    foreach ($this->requiredFields as $field) {
                        if (empty($pluginData[$field])) {
                            $errors[] = "Missing required field in plugin.json: {$field}";
                        }
                    }

                    // Validate vendor/slug format
                    $vendorSlugErrors = $this->validateVendorSlug(
                        $pluginData['vendor'] ?? '',
                        $pluginData['slug'] ?? ''
                    );
                    $errors = array_merge($errors, $vendorSlugErrors);

                    // Check compatibility
                    $compatResult = $this->checkCompatibility($pluginData);
                    $errors = array_merge($errors, $compatResult->errors);
                    $warnings = array_merge($warnings, $compatResult->warnings);
                }
            }

            // Scan for forbidden files and paths
            $forbiddenInZip = $this->scanZipForForbiddenFiles($zip);
            foreach ($forbiddenInZip as $file) {
                $errors[] = "Forbidden file in ZIP: {$file}";
            }

            // Check for path traversal attempts
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($this->hasPathTraversal($name)) {
                    $errors[] = "Invalid path in ZIP (potential path traversal): {$name}";
                }
            }

            // Scan route files for dangerous patterns
            $routeErrors = $this->scanZipRoutesForDangerousPatterns($zip);
            $errors = array_merge($errors, $routeErrors);

            // Scan all src/ files for router usage (catches hidden route registration)
            $srcRouteErrors = $this->scanZipSrcForRouterUsage($zip);
            $errors = array_merge($errors, $srcRouteErrors);

        } finally {
            $zip->close();
        }

        return new PluginValidationResult(
            empty($errors),
            $errors,
            $warnings,
            $pluginData
        );
    }

    /**
     * Validate an extracted plugin directory
     */
    public function validateDirectory(string $path): PluginValidationResult
    {
        $errors = [];
        $warnings = [];
        $pluginData = [];

        if (! File::exists($path)) {
            return PluginValidationResult::failed(['Directory does not exist']);
        }

        // Check required files
        foreach ($this->requiredFiles as $file) {
            if (! File::exists("{$path}/{$file}")) {
                $errors[] = "Missing required file: {$file}";
            }
        }

        // Validate plugin.json
        $pluginJsonPath = "{$path}/plugin.json";
        if (File::exists($pluginJsonPath)) {
            $pluginData = json_decode(File::get($pluginJsonPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON in plugin.json: '.json_last_error_msg();
            } else {
                // Validate required fields
                foreach ($this->requiredFields as $field) {
                    if (empty($pluginData[$field])) {
                        $errors[] = "Missing required field: {$field}";
                    }
                }

                // Validate vendor/slug format
                $vendorSlugErrors = $this->validateVendorSlug(
                    $pluginData['vendor'] ?? '',
                    $pluginData['slug'] ?? ''
                );
                $errors = array_merge($errors, $vendorSlugErrors);

                // Check compatibility
                $compatResult = $this->checkCompatibility($pluginData);
                $errors = array_merge($errors, $compatResult->errors);
                $warnings = array_merge($warnings, $compatResult->warnings);
            }
        }

        // Scan for forbidden files
        $forbiddenFiles = $this->scanForForbiddenFiles($path);
        foreach ($forbiddenFiles as $file) {
            $errors[] = "Forbidden file found: {$file}";
        }

        // Scan route files for dangerous patterns
        $routeErrors = $this->scanDirectoryRoutesForDangerousPatterns($path);
        $errors = array_merge($errors, $routeErrors);

        // Scan all src/ files for router usage (catches hidden route registration)
        $srcRouteErrors = $this->scanSrcForRouterUsage("{$path}/src");
        $errors = array_merge($errors, $srcRouteErrors);

        return new PluginValidationResult(
            empty($errors),
            $errors,
            $warnings,
            $pluginData
        );
    }

    /**
     * Validate vendor and slug format
     */
    public function validateVendorSlug(string $vendor, string $slug): array
    {
        $errors = [];
        $pattern = '/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/';

        if (empty($vendor)) {
            $errors[] = 'Vendor is required';
        } elseif (! preg_match($pattern, $vendor)) {
            $errors[] = 'Vendor must contain only lowercase letters, numbers, and hyphens';
        } elseif (strlen($vendor) > 64) {
            $errors[] = 'Vendor must be 64 characters or less';
        }

        if (empty($slug)) {
            $errors[] = 'Slug is required';
        } elseif (! preg_match($pattern, $slug)) {
            $errors[] = 'Slug must contain only lowercase letters, numbers, and hyphens';
        } elseif (strlen($slug) > 64) {
            $errors[] = 'Slug must be 64 characters or less';
        }

        return $errors;
    }

    /**
     * Check compatibility requirements
     */
    public function checkCompatibility(array $pluginData): PluginValidationResult
    {
        $errors = [];
        $warnings = [];

        $compatibility = $pluginData['compatibility'] ?? [];

        // Check PHP version
        if (! empty($compatibility['php'])) {
            $requiredPhp = $compatibility['php'];
            $currentPhp = PHP_VERSION;

            if (! $this->versionSatisfies($currentPhp, $requiredPhp)) {
                $errors[] = "Plugin requires PHP {$requiredPhp}, current version is {$currentPhp}";
            }
        }

        // Check PHP extensions
        if (! empty($compatibility['extensions'])) {
            foreach ($compatibility['extensions'] as $extension) {
                if (! extension_loaded($extension)) {
                    $errors[] = "Plugin requires PHP extension: {$extension}";
                }
            }
        }

        // Check TallCMS version
        if (! empty($compatibility['tallcms']) && $compatibility['tallcms'] !== '*') {
            $requiredTallcms = $compatibility['tallcms'];
            $currentTallcms = config('tallcms.version', '1.0.0');
            if (! $this->versionSatisfies($currentTallcms, $requiredTallcms)) {
                $errors[] = "Plugin requires TallCMS {$requiredTallcms}, current version is {$currentTallcms}";
            }
        }

        return new PluginValidationResult(
            empty($errors),
            $errors,
            $warnings,
            []
        );
    }

    /**
     * Validate that provider and filament_plugin classes exist after autoload
     */
    public function validateClassesExist(Plugin $plugin): PluginValidationResult
    {
        $errors = [];

        // Check provider class exists
        if (! empty($plugin->provider) && ! class_exists($plugin->provider)) {
            $errors[] = "Provider class not found: {$plugin->provider}";
        }

        // Check filament plugin class exists (if specified)
        if (! empty($plugin->filamentPlugin) && ! class_exists($plugin->filamentPlugin)) {
            $errors[] = "Filament plugin class not found: {$plugin->filamentPlugin}";
        }

        return new PluginValidationResult(
            empty($errors),
            $errors,
            [],
            []
        );
    }

    /**
     * Scan all PHP files in src/ directory for router usage
     * This catches route registration hidden in helper classes
     */
    public function scanSrcForRouterUsage(string $srcPath): array
    {
        $errors = [];

        if (! File::exists($srcPath) || ! is_dir($srcPath)) {
            return $errors;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($srcPath.'/', '', $file->getPathname());
            $content = File::get($file->getPathname());

            // Remove comments before checking
            $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
            $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

            // Check for Route:: calls (case-insensitive - PHP class names are case-insensitive)
            if (preg_match('/\bRoute::/i', $contentWithoutComments)) {
                $errors[] = "Route registration found in src/{$relativePath}. Routes must only be defined in routes/public.php or routes/web.php.";

                continue;
            }

            // Check for aliased Route facade (case-insensitive)
            if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/i', $contentWithoutComments, $matches)) {
                $alias = $matches[1];
                if (preg_match('/\b'.preg_quote($alias, '/').'::/'.'i', $contentWithoutComments)) {
                    $errors[] = "Aliased Route facade ({$alias}::) found in src/{$relativePath}. Routes must only be defined in routes/public.php or routes/web.php.";

                    continue;
                }
            }

            // Check for router instance patterns (all case-insensitive)
            $routerPatterns = [
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

            foreach ($routerPatterns as $pattern => $description) {
                if (preg_match($pattern, $contentWithoutComments)) {
                    $errors[] = "Router access ({$description}) found in src/{$relativePath}. Routes must only be defined in routes/public.php or routes/web.php.";
                    break; // Only report first issue per file
                }
            }
        }

        return $errors;
    }

    /**
     * Scan all PHP files in ZIP's src/ directory for router usage
     */
    public function scanZipSrcForRouterUsage(ZipArchive $zip): array
    {
        $errors = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            // Skip directories
            if (str_ends_with($name, '/')) {
                continue;
            }

            // Normalize and check if it's in src/
            $normalizedName = $this->normalizeZipPath($name);
            if (! str_starts_with($normalizedName, 'src/') || ! str_ends_with($normalizedName, '.php')) {
                continue;
            }

            // Skip blade templates
            if (str_ends_with($normalizedName, '.blade.php')) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            $relativePath = $normalizedName;

            // Remove comments before checking
            $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
            $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

            // Check for Route:: calls (case-insensitive)
            if (preg_match('/\bRoute::/i', $contentWithoutComments)) {
                $errors[] = "Route registration found in {$relativePath}. Routes must only be defined in routes/public.php or routes/web.php.";

                continue;
            }

            // Check for aliased Route facade (case-insensitive)
            if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/i', $contentWithoutComments, $matches)) {
                $alias = $matches[1];
                if (preg_match('/\b'.preg_quote($alias, '/').'::/'.'i', $contentWithoutComments)) {
                    $errors[] = "Aliased Route facade ({$alias}::) found in {$relativePath}. Routes must only be defined in routes/public.php or routes/web.php.";

                    continue;
                }
            }

            // Check for router instance patterns (all case-insensitive)
            $routerPatterns = [
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

            foreach ($routerPatterns as $pattern => $description) {
                if (preg_match($pattern, $contentWithoutComments)) {
                    $errors[] = "Router access ({$description}) found in {$relativePath}. Routes must only be defined in routes/public.php or routes/web.php.";
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Scan plugin's service provider for Route:: calls
     */
    public function scanProviderForRoutes(Plugin $plugin): array
    {
        $errors = [];

        if (empty($plugin->provider)) {
            return $errors;
        }

        // Build path to provider file
        $providerClass = $plugin->provider;
        $providerPath = $plugin->getSrcPath().'/'.str_replace('\\', '/', substr($providerClass, strlen($plugin->namespace) + 1)).'.php';

        if (! File::exists($providerPath)) {
            // Try alternative path
            $providerPath = $plugin->path.'/src/'.basename(str_replace('\\', '/', $providerClass)).'.php';
        }

        if (! File::exists($providerPath)) {
            return $errors;
        }

        $content = File::get($providerPath);

        // Remove comments before checking for route registration patterns
        $contentWithoutComments = preg_replace('#//.*$#m', '', $content);
        $contentWithoutComments = preg_replace('#/\*.*?\*/#s', '', $contentWithoutComments);

        // Check for direct Route:: calls (case-insensitive - PHP class names are case-insensitive)
        if (preg_match('/\bRoute::/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Use routes/public.php or routes/web.php instead. Found Route:: call in provider.';

            return $errors;
        }

        // Check for aliased Route facade (e.g., "use ... Route as R;" then "R::get")
        if (preg_match('/\buse\s+[^;]*\\\\Route\s+as\s+(\w+)\s*;/i', $contentWithoutComments, $matches)) {
            $alias = $matches[1];
            if (preg_match('/\b'.preg_quote($alias, '/').'::/'.'i', $contentWithoutComments)) {
                $errors[] = "Plugin providers must not register routes directly. Found aliased Route facade usage ({$alias}::).";

                return $errors;
            }
        }

        // Check for router instance via app() helper (case-insensitive - PHP function names are case-insensitive)
        if (preg_match('/\bapp\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found app(\'router\') usage in provider.';

            return $errors;
        }

        // Check for router instance via resolve() helper
        if (preg_match('/\bresolve\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found resolve(\'router\') usage in provider.';

            return $errors;
        }

        // Check for router instance via $this->app container access
        if (preg_match('/\$this\s*->\s*app\s*\[\s*[\'"]router[\'"]\s*\]/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found $this->app[\'router\'] usage in provider.';

            return $errors;
        }

        // Check for direct Router class usage (FQCN with leading backslash)
        if (preg_match('/\\\\Illuminate\\\\Routing\\\\Router\b/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Illuminate\\Routing\\Router usage in provider.';

            return $errors;
        }

        // Check for imported Router class (use Illuminate\Routing\Router;)
        if (preg_match('/\buse\s+Illuminate\\\\Routing\\\\Router\b/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Router class import in provider.';

            return $errors;
        }

        // Check for Router::class constant resolution
        if (preg_match('/\b(app|resolve)\s*\(\s*\)?\s*->\s*make\s*\(\s*\\\\?Router::class/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Router::class resolution in provider.';

            return $errors;
        }
        if (preg_match('/\bresolve\s*\(\s*\\\\?Router::class\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found resolve(Router::class) in provider.';

            return $errors;
        }
        if (preg_match('/\bapp\s*\(\s*\\\\?Router::class\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found app(Router::class) in provider.';

            return $errors;
        }

        // Check for Route::class constant (enables dynamic dispatch)
        if (preg_match('/\bRoute::class\b/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Route::class constant in provider.';

            return $errors;
        }

        // Check for Route facade class string
        if (preg_match('/[\'"]\\\\?Illuminate\\\\Support\\\\Facades\\\\Route[\'"]/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Route facade class string in provider.';

            return $errors;
        }

        // Check for call_user_func patterns with Route
        if (preg_match('/\bcall_user_func(_array)?\s*\([^)]*Route/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found call_user_func with Route in provider.';

            return $errors;
        }

        // Check for app()->make('router')
        if (preg_match('/\bapp\s*\(\s*\)\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found app()->make(\'router\') in provider.';

            return $errors;
        }

        // Check for App::make('router')
        if (preg_match('/\bApp::\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found App::make(\'router\') in provider.';

            return $errors;
        }

        // Check for Registrar::class usage
        if (preg_match('/\bRegistrar::class\b/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Registrar::class in provider.';

            return $errors;
        }

        // Check for Illuminate\Contracts\Routing\Registrar
        if (preg_match('/\\\\?Illuminate\\\\Contracts\\\\Routing\\\\Registrar\b/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found Registrar contract in provider.';

            return $errors;
        }

        // Check for make(Router::class) or make(Registrar::class)
        if (preg_match('/\bmake\s*\(\s*\\\\?(Router|Registrar)::class\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found make(Router/Registrar::class) in provider.';

            return $errors;
        }

        // Check for $this->app->make('router')
        if (preg_match('/\$this\s*->\s*app\s*->\s*make\s*\(\s*[\'"]router[\'"]\s*\)/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found $this->app->make(\'router\') in provider.';

            return $errors;
        }

        // Check for app()['router'] array access
        if (preg_match('/\bapp\s*\(\s*\)\s*\[\s*[\'"]router[\'"]\s*\]/i', $contentWithoutComments)) {
            $errors[] = 'Plugin providers must not register routes directly. Found app()[\'router\'] in provider.';

            return $errors;
        }

        return $errors;
    }

    /**
     * Scan directory for forbidden files
     */
    protected function scanForForbiddenFiles(string $path): array
    {
        $forbidden = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                $relativePath = str_replace($path.'/', '', $file->getPathname());

                // Skip node_modules
                if (str_contains($relativePath, 'node_modules/')) {
                    continue;
                }

                // Check for blocked directories
                foreach ($this->blockedDirectories as $blockedDir) {
                    if (str_starts_with($relativePath, $blockedDir)) {
                        $forbidden[] = $relativePath." (blocked directory: {$blockedDir})";

                        continue 2;
                    }
                }

                // Check against forbidden patterns
                if ($this->matchesForbiddenPattern($filename)) {
                    $forbidden[] = $relativePath;

                    continue;
                }

                // Check PHP files are in allowed locations
                if ($this->isPotentialPhpFile($filename)) {
                    if (! $this->isPhpFileAllowed($relativePath)) {
                        $forbidden[] = $relativePath.' (PHP files only allowed in src/, database/migrations/, routes/public.php, routes/web.php)';
                    }
                }
            }

            // Check for symlinks
            if ($file->isLink()) {
                $relativePath = str_replace($path.'/', '', $file->getPathname());
                $forbidden[] = $relativePath.' (symlinks not allowed)';
            }
        }

        return $forbidden;
    }

    /**
     * Scan ZIP for forbidden files
     */
    protected function scanZipForForbiddenFiles(ZipArchive $zip): array
    {
        $forbidden = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            // Skip directories
            if (str_ends_with($name, '/')) {
                continue;
            }

            // Normalize path (remove leading directory if nested)
            $normalizedName = $this->normalizeZipPath($name);

            // Skip node_modules
            if (str_contains($normalizedName, 'node_modules/')) {
                continue;
            }

            // Check for blocked directories
            foreach ($this->blockedDirectories as $blockedDir) {
                if (str_starts_with($normalizedName, $blockedDir)) {
                    $forbidden[] = $name." (blocked directory: {$blockedDir})";

                    continue 2;
                }
            }

            $filename = basename($name);

            // Check against forbidden patterns
            if ($this->matchesForbiddenPattern($filename)) {
                $forbidden[] = $name;

                continue;
            }

            // Check PHP files are in allowed locations
            if ($this->isPotentialPhpFile($filename)) {
                if (! $this->isPhpFileAllowed($normalizedName)) {
                    $forbidden[] = $name.' (PHP files only allowed in src/, database/migrations/, routes/public.php, routes/web.php)';
                }
            }
        }

        return $forbidden;
    }

    /**
     * Check if a PHP file is in an allowed location
     */
    protected function isPhpFileAllowed(string $relativePath): bool
    {
        // Allow blade.php anywhere in resources/views
        if (str_ends_with($relativePath, '.blade.php') && str_starts_with($relativePath, 'resources/views/')) {
            return true;
        }

        // Check allowed paths
        foreach ($this->allowedPhpPaths as $allowedPath) {
            // Exact file match
            if ($relativePath === $allowedPath) {
                return true;
            }

            // Directory match (for src/ and database/migrations/)
            if (str_ends_with($allowedPath, '/') && str_starts_with($relativePath, $allowedPath)) {
                // For database/migrations/, only allow flat structure
                if ($allowedPath === 'database/migrations/') {
                    $remaining = substr($relativePath, strlen($allowedPath));
                    // Should not contain any more slashes (flat only)
                    if (str_contains($remaining, '/')) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Check if filename matches forbidden patterns
     */
    protected function matchesForbiddenPattern(string $filename): bool
    {
        foreach ($this->forbiddenPatterns as $pattern) {
            // Handle wildcard patterns
            if (str_starts_with($pattern, '*.')) {
                $extension = substr($pattern, 1);
                if (str_ends_with($filename, $extension)) {
                    return true;
                }
            } elseif (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($filename, $prefix)) {
                    return true;
                }
            } elseif ($filename === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scan route files in ZIP for dangerous patterns
     */
    protected function scanZipRoutesForDangerousPatterns(ZipArchive $zip): array
    {
        $errors = [];
        $routeFiles = ['routes/public.php', 'routes/web.php'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $normalizedName = $this->normalizeZipPath($name);

            if (in_array($normalizedName, $routeFiles)) {
                $content = $zip->getFromIndex($i);
                $fileErrors = $this->scanContentForDangerousRoutePatterns($content, $normalizedName);
                $errors = array_merge($errors, $fileErrors);
            }
        }

        return $errors;
    }

    /**
     * Scan route files in directory for dangerous patterns
     */
    protected function scanDirectoryRoutesForDangerousPatterns(string $path): array
    {
        $errors = [];
        $routeFiles = ['routes/public.php', 'routes/web.php'];

        foreach ($routeFiles as $routeFile) {
            $filePath = "{$path}/{$routeFile}";
            if (File::exists($filePath)) {
                $content = File::get($filePath);
                $fileErrors = $this->scanContentForDangerousRoutePatterns($content, $routeFile);
                $errors = array_merge($errors, $fileErrors);
            }
        }

        return $errors;
    }

    /**
     * Scan content for dangerous route patterns
     */
    protected function scanContentForDangerousRoutePatterns(string $content, string $filename): array
    {
        $errors = [];

        foreach ($this->dangerousRoutePatterns as $pattern) {
            if (str_contains($content, $pattern)) {
                $errors[] = "Dangerous route pattern found in {$filename}: {$pattern}";
            }
        }

        // Check for catch-all parameters at root level
        if (preg_match('/Route::[a-z]+\s*\(\s*[\'"]\/?\{[^}]+\}/', $content)) {
            $errors[] = "Catch-all route parameter at root level found in {$filename}";
        }

        return $errors;
    }

    /**
     * Find plugin.json in ZIP (may be in root or in a subdirectory)
     */
    protected function findPluginJsonInZip(ZipArchive $zip): ?int
    {
        // First, look for plugin.json in root
        $index = $zip->locateName('plugin.json');
        if ($index !== false) {
            return $index;
        }

        // Look for plugin.json in a single subdirectory (common pattern)
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^[^\/]+\/plugin\.json$/', $name)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Known plugin directories that should NOT be stripped as wrapper directories
     */
    protected array $knownPluginDirectories = [
        'src',
        'database',
        'routes',
        'resources',
        'config',
        'public',
        'tests',
        'lang',
    ];

    /**
     * Normalize ZIP path (remove leading directory if nested)
     *
     * Only strips wrapper directories (like GitHub's repo-name-branch/ prefix),
     * NOT legitimate plugin directories like src/, database/, routes/, etc.
     */
    protected function normalizeZipPath(string $path): string
    {
        // If path starts with a single directory, check if it's a wrapper
        if (preg_match('/^([^\/]+)\/(.+)$/', $path, $matches)) {
            $firstDir = $matches[1];

            // Don't strip known plugin directories - these are NOT wrappers
            if (in_array(strtolower($firstDir), $this->knownPluginDirectories)) {
                return $path;
            }

            // Also don't strip if first dir is a file (no remaining path after it)
            // This handles root-level files like plugin.json, README.md, etc.

            // Strip wrapper directories (like project-name-main/, vendor-plugin-v1.0.0/)
            // These typically contain hyphens and look like repo/package names
            if (preg_match('/^[a-z0-9]+-[a-z0-9-]+$/i', $firstDir)) {
                return $matches[2];
            }
        }

        return $path;
    }

    /**
     * Check for path traversal attempts
     */
    protected function hasPathTraversal(string $path): bool
    {
        $decoded = urldecode($path);

        $patterns = [
            '..',
            '//',
            '\\',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($decoded, $pattern)) {
                return true;
            }
        }

        if (str_starts_with($decoded, '/') || preg_match('/^[a-zA-Z]:/', $decoded)) {
            return true;
        }

        $normalized = $this->normalizePath($decoded);
        if (str_starts_with($normalized, '../') || $normalized === '..') {
            return true;
        }

        return false;
    }

    /**
     * Normalize a path by resolving . and .. components
     */
    protected function normalizePath(string $path): string
    {
        $parts = [];
        $segments = explode('/', str_replace('\\', '/', $path));

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if (empty($parts)) {
                    return '../';
                }
                array_pop($parts);
            } else {
                $parts[] = $segment;
            }
        }

        return implode('/', $parts);
    }

    /**
     * Check if filename could be executable PHP
     */
    protected function isPotentialPhpFile(string $filename): bool
    {
        $lowername = strtolower($filename);

        $phpExtensions = ['.php', '.phtml', '.php3', '.php4', '.php5', '.phar', '.phps'];
        foreach ($phpExtensions as $ext) {
            if (str_contains($lowername, $ext)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if version satisfies requirement
     */
    protected function versionSatisfies(string $current, string $requirement): bool
    {
        if (str_starts_with($requirement, '^')) {
            $minVersion = substr($requirement, 1);
            $parts = explode('.', $minVersion);
            $major = $parts[0];

            return version_compare($current, $minVersion, '>=')
                && version_compare($current, ($major + 1).'.0.0', '<');
        }

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

        if (str_starts_with($requirement, '>=')) {
            return version_compare($current, substr($requirement, 2), '>=');
        }

        if ($requirement === '*') {
            return true;
        }

        return version_compare($current, $requirement, '>=');
    }
}

/**
 * Plugin validation result value object
 */
class PluginValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors,
        public array $warnings,
        public array $pluginData
    ) {}

    public static function failed(array $errors): self
    {
        return new self(false, $errors, [], []);
    }

    public static function success(array $pluginData = [], array $warnings = []): self
    {
        return new self(true, [], $warnings, $pluginData);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }
}
