<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
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

        // Override theme if site has one assigned
        if ($site->theme) {
            Config::set('theme.active', $site->theme);

            // Reset ThemeManager singleton so it re-reads from config
            // ThemeManager caches $activeTheme on first getActiveTheme() call
            app()->forgetInstance(ThemeManager::class);
        }

        // Override locale if site has one assigned
        if ($site->locale) {
            app()->setLocale($site->locale);
        }

        return $next($request);
    }
}
