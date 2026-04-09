<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\CurrentSiteResolver;

/**
 * Marks the current request as running within the Filament admin panel
 * and enforces site ownership for non-super-admins.
 */
class MarkAdminContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('tallcms.admin_context', true);

        $this->enforceOwnership();

        return $next($request);
    }

    /**
     * Ensure non-super-admins can only access sites they own.
     *
     * Resets session to first owned site if:
     * - "__all_sites__" sentinel (disabled for non-super-admins)
     * - A site_id they don't own
     * - No session yet (first login)
     */
    protected function enforceOwnership(): void
    {
        if (! auth()->check() || auth()->user()->hasRole('super_admin')) {
            return;
        }

        $sessionValue = session('multisite_admin_site_id');
        $needsReset = false;

        if ($sessionValue === CurrentSiteResolver::ALL_SITES_SENTINEL) {
            $needsReset = true;
        } elseif ($sessionValue && is_numeric($sessionValue)) {
            $owns = Site::where('id', $sessionValue)
                ->where('user_id', auth()->id())
                ->exists();
            if (! $owns) {
                $needsReset = true;
            }
        } elseif (! $sessionValue) {
            $needsReset = true;
        }

        if ($needsReset) {
            $firstOwned = Site::where('user_id', auth()->id())
                ->where('is_active', true)
                ->orderBy('created_at')
                ->value('id');

            if ($firstOwned) {
                session(['multisite_admin_site_id' => $firstOwned]);
            } else {
                // User has no sites — clear session and let the UI handle it
                session()->forget('multisite_admin_site_id');
            }
        }
    }
}
