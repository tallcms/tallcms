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

        if ($current === '__all_sites__' || (is_string($current) && is_numeric($current))) {
            $this->selected = (string) $current;

            return;
        }

        if (is_int($current)) {
            $this->selected = (string) $current;

            return;
        }

        // First load: seed session using the same role-based fallback the trait
        // resolves so first dashboard load matches pre-picker behaviour.
        $fallback = $this->getMultisiteSiteId();
        if ($fallback !== null) {
            $this->selected = (string) $fallback;
            session(['multisite_admin_site_id' => $fallback]);
        }
    }

    public function updatedSelected(string|int $value): void
    {
        $stored = $value === '__all_sites__' ? '__all_sites__' : (int) $value;

        session(['multisite_admin_site_id' => $stored]);

        $this->dispatch('dashboard.site-changed', siteId: $stored);
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
