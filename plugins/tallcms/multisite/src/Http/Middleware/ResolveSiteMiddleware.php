<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use TallCms\Cms\Contracts\ThemeInterface;
use TallCms\Cms\Services\FileBasedTheme;
use TallCms\Cms\Services\ThemeManager;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class ResolveSiteMiddleware
{
    public function __construct(
        protected CurrentSiteResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->resolver->resolve($request);

        $site = $this->resolver->get();

        if (! $site) {
            // No site matched this domain.
            // 404 only for frontend page routes — not plugin routes, API, etc.
            $panelPath = config('tallcms.filament.panel_path', 'admin');
            $isFrontendRoute = ! $request->is("{$panelPath}*")
                && ! $request->is('_plugins/*')
                && ! $request->is('api/*')
                && ! $request->is('livewire/*');

            if ($isFrontendRoute) {
                abort(404);
            }

            return $next($request);
        }

        // Override theme config if site has one assigned
        if ($site->theme) {
            Config::set('theme.active', $site->theme);
        }

        // Always normalize theme/view state for the current request.
        // Even when the site has no theme override, we must reset to ensure
        // a previous request's theme paths don't bleed (long-lived workers).
        app()->forgetInstance(ThemeManager::class);

        $manager = app(ThemeManager::class);
        $manager->resetViewPaths();
        $manager->registerThemeViewPaths();

        $activeTheme = $manager->getActiveTheme();
        $manager->registerPluginViewOverrides($activeTheme);

        // Flush view finder cache (matches core's View::flushFinderCache() pattern)
        View::flushFinderCache();

        // Rebind ThemeInterface for color/preset resolution in view composers
        if ($activeTheme && isset($activeTheme->path)) {
            app()->instance(ThemeInterface::class, new FileBasedTheme($activeTheme));
        }

        // Override locale if site has one assigned
        if ($site->locale) {
            app()->setLocale($site->locale);
        }

        return $next($request);
    }
}
