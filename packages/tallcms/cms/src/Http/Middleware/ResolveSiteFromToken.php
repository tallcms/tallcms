<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets site context from the authenticated Sanctum token's site_id.
 *
 * When multisite is active and the token has a site_id, this middleware
 * overrides the CurrentSiteResolver so SiteScope filters all queries
 * to the token's site. No per-controller changes needed.
 *
 * Tokens with site_id = null are installation-wide (super-admin).
 */
class ResolveSiteFromToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->currentAccessToken()) {
            return $next($request);
        }

        // Check if the token table has site_id (multisite plugin installed + migrated)
        if (! Schema::hasColumn('personal_access_tokens', 'site_id')) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        $siteId = $token->site_id ?? null;

        if ($siteId && app()->bound('tallcms.multisite.resolver')) {
            $site = \Tallcms\Multisite\Models\Site::where('id', $siteId)
                ->where('is_active', true)
                ->first();

            if ($site) {
                app('tallcms.multisite.resolver')->overrideForRequest($site);
            } else {
                // Token's site no longer exists or is inactive
                abort(403, 'The site associated with this API token is no longer available.');
            }
        }

        return $next($request);
    }
}
