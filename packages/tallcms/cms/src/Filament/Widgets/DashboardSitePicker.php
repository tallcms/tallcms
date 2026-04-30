<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Filament\Widgets\Concerns\HasMultisiteWidgetContext;

/**
 * Dashboard scope picker.
 *
 * Surfaces "which site's data should the dashboard widgets show?"
 * as an explicit affordance, separate from SiteSwitcher (which navigates
 * into a site's edit page and intentionally does NOT mutate session).
 *
 * Writes session('multisite_admin_site_id') and broadcasts the
 * 'dashboard.site-changed' Livewire event so other widgets refresh
 * without a full page reload.
 */
class DashboardSitePicker extends Widget
{
    use HasMultisiteWidgetContext;

    protected string $view = 'tallcms::filament.widgets.dashboard-site-picker';

    protected static ?int $sort = -100;

    protected int|string|array $columnSpan = 'full';

    public ?string $selected = null;

    public function mount(): void
    {
        $current = session('multisite_admin_site_id');
        $authorized = $this->resolveAuthorizedSiteValue($current);

        if ($authorized !== null) {
            $this->selected = is_int($authorized) ? (string) $authorized : $authorized;

            // Normalize stale/typed session value (e.g. coerce string id → int)
            // so downstream consumers see a consistent shape.
            if ($current !== $authorized) {
                session(['multisite_admin_site_id' => $authorized]);
            }

            return;
        }

        // No valid session value yet (or a stale value the current user can't
        // access) — clear it first so the trait's role-based fallback is reached.
        // (The trait short-circuits on a session value of '__all_sites__' or any
        // numeric id without validating ownership; for unauthorized values we
        // need a fresh fallback resolution.)
        session()->forget('multisite_admin_site_id');

        $fallback = $this->getMultisiteSiteId();
        if ($fallback !== null) {
            $this->selected = (string) $fallback;
            session(['multisite_admin_site_id' => $fallback]);
        }
    }

    public function updatedSelected(string|int $value): void
    {
        $authorized = $this->resolveAuthorizedSiteValue($value);

        if ($authorized === null) {
            // Tampered or unauthorized value (e.g. non-super-admin trying to
            // set __all_sites__, or any user trying to pick a site they don't
            // own). Revert the picker UI to the current session value without
            // dispatching, so other widgets don't refresh against bad scope.
            $this->selected = is_string(session('multisite_admin_site_id'))
                || is_int(session('multisite_admin_site_id'))
                    ? (string) session('multisite_admin_site_id')
                    : null;

            return;
        }

        session(['multisite_admin_site_id' => $authorized]);

        $this->dispatch('dashboard.site-changed', siteId: $authorized);
    }

    /**
     * Validate a candidate scope value against the current user's access.
     * Returns the canonical value to write to session, or null if the value
     * is not allowed for this user.
     *
     * - '__all_sites__' is only valid for super_admins.
     * - A numeric site_id is only valid if the site exists, is active, and
     *   either the user is super_admin or owns the site.
     * - Anything else (null, garbage strings, expired ids) returns null.
     */
    protected function resolveAuthorizedSiteValue(mixed $value): null|int|string
    {
        if (! auth()->check()) {
            return null;
        }

        if ($value === '__all_sites__') {
            return $this->isSuperAdmin() ? '__all_sites__' : null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $siteId = (int) $value;

        try {
            $query = DB::table('tallcms_sites')
                ->where('id', $siteId)
                ->where('is_active', true);

            if (! $this->isSuperAdmin()) {
                $query->where('user_id', auth()->id());
            }

            return $query->exists() ? $siteId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, object{id:int,name:string}>
     */
    public function getSitesForUserProperty(): Collection
    {
        try {
            $query = DB::table('tallcms_sites')
                ->where('is_active', true)
                ->orderBy('name');

            if (auth()->check() && ! auth()->user()->hasRole('super_admin')) {
                $query->where('user_id', auth()->id());
            }

            return $query->get(['id', 'name']);
        } catch (\Throwable) {
            return collect();
        }
    }

    public function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canView(): bool
    {
        try {
            if (! Schema::hasTable('tallcms_sites')) {
                return false;
            }

            if (! auth()->check()) {
                return false;
            }

            // Super-admins see the picker even with no owned sites
            // (they can pick any site or "All Sites").
            if (auth()->user()->hasRole('super_admin')) {
                return true;
            }

            // Regular users only see the picker if they own at least one site.
            $ownedCount = DB::table('tallcms_sites')
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->count();

            return $ownedCount > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
