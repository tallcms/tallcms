<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class SiteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $siteId = $this->resolveCurrentSiteId();

        if ($siteId === 'all') {
            // Explicit "All Sites" admin mode — no filter
            return;
        }

        if ($siteId) {
            // Strict: only show content belonging to this site
            $builder->where($model->getTable().'.site_id', $siteId);

            return;
        }

        // No site context resolved. On frontend this means unknown domain.
        // Show nothing to prevent cross-site content leakage.
        // On console/testing, skip filtering entirely.
        if (app()->runningInConsole()) {
            return;
        }

        $builder->whereRaw('1 = 0');
    }

    /**
     * Resolve the current site ID using the same two-tier strategy as
     * SiteSetting: admin session first, resolver second.
     *
     * Returns int (site ID), 'all' (All Sites mode), or null (no context).
     */
    protected function resolveCurrentSiteId(): int|string|null
    {
        // Tier 1: Admin session — authoritative in admin context.
        // Covers Livewire update requests where MarkAdminContext middleware
        // doesn't run (Livewire uses 'web' group, not Filament panel stack).
        $sessionValue = session('multisite_admin_site_id');

        if ($sessionValue === CurrentSiteResolver::ALL_SITES_SENTINEL) {
            return 'all';
        }

        if ($sessionValue && is_numeric($sessionValue)) {
            // Verify the site exists and is active
            try {
                $exists = DB::table('tallcms_sites')
                    ->where('id', $sessionValue)
                    ->where('is_active', true)
                    ->exists();

                if ($exists) {
                    return (int) $sessionValue;
                }
            } catch (QueryException) {
                // Table doesn't exist
            }
        }

        // Tier 2: Resolver singleton — authoritative on frontend (domain-based).
        if (app()->bound(CurrentSiteResolver::class)) {
            $resolver = app(CurrentSiteResolver::class);

            if (! $resolver->isResolved() && ! app()->runningInConsole()) {
                $resolver->resolve(request());
            }

            if ($resolver->isResolved()) {
                if ($resolver->isAllSitesMode()) {
                    return 'all';
                }
                if ($resolver->id()) {
                    return $resolver->id();
                }
            }
        }

        return null;
    }
}
