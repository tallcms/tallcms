<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks the current request as running within the Filament admin panel.
 *
 * Added to the Filament panel middleware stack by MultisitePlugin.
 * The CurrentSiteResolver reads this attribute to determine admin context
 * reliably, without depending on URL patterns or Referer headers.
 */
class MarkAdminContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('tallcms.admin_context', true);

        return $next($request);
    }
}
