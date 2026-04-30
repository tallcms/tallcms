<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filter a resource's list query to records the current user owns, scoped via
 * site ownership.
 *
 * Why a query-level filter on top of the policy: Filament policies gate
 * individual-record access (view/update/etc.), but list queries don't invoke
 * policy methods per row — they just hit the database and render whatever
 * comes back. Without a query-level filter, a site_owner's Comments page
 * happily lists every tenant's comments, even though the policy would block
 * them from clicking into a row.
 *
 * super_admins bypass the filter. Single-site installs (no site_id column)
 * short-circuit before any filtering. Users with no owned sites get zero rows.
 */
trait ScopesQueryToOwnedSites
{
    protected static function scopeQueryToOwnedSites(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $query;
        }

        // Gate on the multisite plugin actually being booted, not just the
        // presence of a site_id column. If multisite was uninstalled or
        // disabled but the columns/tables linger, schema-only detection would
        // leave normal admins filtered to zero rows against a stale
        // tallcms_sites table.
        if (! function_exists('tallcms_multisite_active') || ! tallcms_multisite_active()) {
            return $query;
        }

        $table = $query->getModel()->getTable();
        if (! Schema::hasColumn($table, 'site_id')) {
            return $query;
        }

        try {
            $ownedSiteIds = DB::table('tallcms_sites')
                ->where('user_id', $user->getAuthIdentifier())
                ->pluck('id');
        } catch (\Throwable) {
            // tallcms_sites doesn't exist yet — nothing to scope against.
            return $query;
        }

        return $query->whereIn($table.'.site_id', $ownedSiteIds);
    }

    /**
     * Like scopeQueryToOwnedSites, but falls back to filtering by user_id
     * (record creator) when the table has no site_id column.
     *
     * Use this for resources that exist in both multisite and standalone:
     *   - multisite install (site_id present) → scope by owned sites
     *   - standalone install (site_id absent, user_id present) → scope by creator
     *   - super_admin → bypass either way
     *
     * scopeQueryToOwnedSites is a no-op in standalone, which is correct for
     * resources that have no per-user notion of ownership outside multisite
     * (Pages, Menus, Comments, Contact Submissions). For Posts/Categories/Media,
     * the prior single-site behavior was a per-creator filter — this helper
     * preserves it.
     */
    protected static function scopeQueryToOwnedTenants(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $query;
        }

        $table = $query->getModel()->getTable();
        $multisiteActive = function_exists('tallcms_multisite_active') && tallcms_multisite_active();

        try {
            if ($multisiteActive && Schema::hasColumn($table, 'site_id')) {
                $ownedSiteIds = DB::table('tallcms_sites')
                    ->where('user_id', $user->getAuthIdentifier())
                    ->pluck('id');

                return $query->whereIn($table.'.site_id', $ownedSiteIds);
            }

            if (Schema::hasColumn($table, 'user_id')) {
                return $query->where($table.'.user_id', $user->getAuthIdentifier());
            }
        } catch (\Throwable) {
            // Schema not yet migrated; fall through to unfiltered query.
        }

        return $query;
    }
}
