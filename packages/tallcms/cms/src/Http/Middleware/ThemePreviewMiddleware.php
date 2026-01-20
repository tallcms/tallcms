<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use TallCms\Cms\Contracts\ThemeInterface;
use TallCms\Cms\Models\Theme;
use TallCms\Cms\Services\FileBasedTheme;
use TallCms\Cms\Services\ThemeManager;

class ThemePreviewMiddleware
{
    public function __construct(
        protected ThemeManager $themeManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get admin panel path from config (defaults to 'admin')
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        // Skip for admin panel routes
        if ($request->is("{$panelPath}/*") || $request->is($panelPath)) {
            return $next($request);
        }

        $previewSlug = $this->getPreviewSlug($request);

        if ($previewSlug) {
            $this->applyThemePreview($previewSlug);
        }

        return $next($request);
    }

    /**
     * Get preview slug from request
     *
     * Preview only applies when ?theme_preview parameter is explicitly in the URL.
     * This prevents preview from bleeding into other browser tabs/windows.
     */
    protected function getPreviewSlug(Request $request): ?string
    {
        // Only apply preview when URL parameter is explicitly present
        // This ensures preview is isolated to the preview tab only
        return $request->query('theme_preview');
    }

    /**
     * Apply theme preview for the current request
     */
    protected function applyThemePreview(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            $this->storePreviewError("Theme '{$slug}' not found");

            return;
        }

        // Validate theme before preview (must be built and meet requirements)
        $previewError = $this->validateThemeForPreview($theme);
        if ($previewError) {
            $this->storePreviewError($previewError);

            return;
        }

        // Clear any previous preview error
        Session::forget('theme.preview_error');

        // Store the preview theme in the request for later reference
        app()->instance('theme.preview_active', $theme);

        // Override the ThemeManager's active theme for this request
        $this->overrideActiveTheme($theme);

        // Reset and re-register view paths for the preview theme
        $this->overrideViewPaths($theme);

        // Update view composers to use preview theme
        $this->updateViewComposers($theme);
    }

    /**
     * Validate theme for preview and return error message if invalid
     */
    protected function validateThemeForPreview(Theme $theme): ?string
    {
        // Theme must be built if it's marked as prebuilt
        if ($theme->isPrebuilt() && ! $theme->isBuilt()) {
            return "Theme '{$theme->name}' has not been built. Run 'npm run build' in the theme directory.";
        }

        // Theme must meet system requirements
        if (! $theme->meetsRequirements()) {
            $unmet = $theme->getUnmetRequirements();

            return "Theme '{$theme->name}' does not meet requirements: ".implode(', ', $unmet);
        }

        return null;
    }

    /**
     * Store preview error for user feedback
     */
    protected function storePreviewError(string $error): void
    {
        Session::flash('theme.preview_error', $error);
    }

    /**
     * Get the last preview error (if any)
     */
    public static function getPreviewError(): ?string
    {
        return Session::get('theme.preview_error');
    }

    /**
     * Override the active theme in ThemeManager for this request
     */
    protected function overrideActiveTheme(Theme $theme): void
    {
        // Use reflection to set the activeTheme property on ThemeManager
        $reflection = new \ReflectionClass($this->themeManager);
        $property = $reflection->getProperty('activeTheme');
        $property->setAccessible(true);
        $property->setValue($this->themeManager, $theme);

        // Rebind the FileBasedTheme with the preview theme
        $fileBasedTheme = new FileBasedTheme($theme);
        app()->instance(ThemeInterface::class, $fileBasedTheme);
    }

    /**
     * Get the themes base path from config or default
     */
    protected function getThemesBasePath(): string
    {
        return config('tallcms.themes.path') ?? base_path('themes');
    }

    /**
     * Override view paths for preview theme
     */
    protected function overrideViewPaths(Theme $theme): void
    {
        // Get the view finder from the View factory (not app('view.finder') which may be a different instance)
        $viewFinder = View::getFinder();

        // Get current paths and filter out theme paths
        $currentPaths = $viewFinder->getPaths();
        $themesBasePath = $this->getThemesBasePath();

        // Remove existing theme view paths
        $filteredPaths = array_filter($currentPaths, function ($path) use ($themesBasePath) {
            return ! str_starts_with($path, $themesBasePath);
        });

        // Reset paths without theme paths
        $viewFinder->setPaths($filteredPaths);

        // Clear existing theme namespaces to prevent accumulation
        $this->clearThemeNamespaces();

        // Get theme hierarchy (child -> parent chain)
        $hierarchy = $theme->getHierarchy();

        // Prepend theme view paths in reverse hierarchy order (parent first, child last)
        // This ensures child theme views take precedence
        foreach (array_reverse($hierarchy) as $hierarchyTheme) {
            $viewPath = $hierarchyTheme->getViewPath();
            if (is_dir($viewPath)) {
                $viewFinder->prependLocation($viewPath);

                // Also register view namespace for explicit theme loading (theme.{slug}::*)
                $this->registerThemeNamespace($hierarchyTheme);
            }
        }

        // Flush the view cache to ensure fresh resolution
        View::flushFinderCache();
    }

    /**
     * Clear existing theme view namespaces to prevent accumulation
     */
    protected function clearThemeNamespaces(): void
    {
        $viewFinder = View::getFinder();

        // Use reflection to access and modify the hints array (namespaces)
        $reflection = new \ReflectionClass($viewFinder);
        if ($reflection->hasProperty('hints')) {
            $hintsProperty = $reflection->getProperty('hints');
            $hintsProperty->setAccessible(true);
            $hints = $hintsProperty->getValue($viewFinder);

            // Remove all theme.* namespaces
            $hints = array_filter($hints, function ($key) {
                return ! str_starts_with($key, 'theme.');
            }, ARRAY_FILTER_USE_KEY);

            $hintsProperty->setValue($viewFinder, $hints);
        }
    }

    /**
     * Register theme view namespace for explicit theme loading
     */
    protected function registerThemeNamespace(Theme $theme): void
    {
        $namespace = "theme.{$theme->slug}";
        $viewPath = $theme->getViewPath();

        if (is_dir($viewPath)) {
            View::addNamespace($namespace, $viewPath);
        }
    }

    /**
     * Update view composers with preview theme data
     */
    protected function updateViewComposers(Theme $theme): void
    {
        // Share the preview theme with all views
        View::share('currentTheme', $theme);
        View::share('themeAsset', function (string $path) use ($theme) {
            return asset("themes/{$theme->slug}/{$path}");
        });
    }

    /**
     * Check if a preview is currently active
     */
    public static function isPreviewActive(): bool
    {
        return app()->bound('theme.preview_active');
    }

    /**
     * Get the currently previewed theme
     */
    public static function getPreviewTheme(): ?Theme
    {
        if (app()->bound('theme.preview_active')) {
            return app('theme.preview_active');
        }

        return null;
    }
}
