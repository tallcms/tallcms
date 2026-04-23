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

        return $query->whereIn('site_id', $ownedSiteIds);
    }
}
