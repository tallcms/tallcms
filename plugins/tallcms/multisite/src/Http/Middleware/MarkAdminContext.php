<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tallcms\Multisite\Services\CurrentSiteResolver;

/**
 * Marks the current request as running within the Filament admin panel
 * and forces the site resolver to use admin context.
 *
 * Added to the Filament panel middleware stack by MultisitePlugin.
 * This must override any stale resolution that may have occurred during
 * boot (e.g. SiteScope lazy resolution triggered before middleware ran).
 */
class MarkAdminContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('tallcms.admin_context', true);

        // Force re-resolution in admin context.
        // SiteScope's lazy resolution may have already resolved as frontend
        // (by domain) during boot, before this middleware ran. Reset and
        // re-resolve so the admin session selection takes effect.
        if (app()->bound(CurrentSiteResolver::class)) {
            $resolver = app(CurrentSiteResolver::class);
            $resolver->reset();
            $resolver->resolve($request);
        }

        return $next($request);
    }
}
