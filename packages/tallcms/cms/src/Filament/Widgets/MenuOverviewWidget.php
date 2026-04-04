<?php

namespace TallCms\Cms\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MenuOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $siteId = $this->getMultisiteSiteId();
        $siteName = $this->getMultisiteName($siteId);

        // Use direct DB queries scoped by site — avoids reliance on SiteScope
        // which may not be resolved at widget render time in admin context.
        $pageQuery = DB::table('tallcms_pages')->whereNull('deleted_at');
        $publishedPageQuery = DB::table('tallcms_pages')->whereNull('deleted_at')->where('status', 'published');
        $menuQuery = DB::table('tallcms_menus');
        $activeMenuQuery = DB::table('tallcms_menus')->where('is_active', true);

        if ($siteId) {
            $pageQuery->where('site_id', $siteId);
            $publishedPageQuery->where('site_id', $siteId);
            $menuQuery->where('site_id', $siteId);
            $activeMenuQuery->where('site_id', $siteId);
        }

        $totalPages = $pageQuery->count();
        $publishedPages = $publishedPageQuery->count();
        $totalMenus = $menuQuery->count();
        $activeMenus = $activeMenuQuery->count();

        // Menu items: scope through menus
        $menuIds = ($siteId ? DB::table('tallcms_menus')->where('site_id', $siteId) : DB::table('tallcms_menus'))
            ->pluck('id');
        $totalItems = $menuIds->isNotEmpty()
            ? DB::table('tallcms_menu_items')->whereIn('menu_id', $menuIds)->count()
            : 0;

        return [
            Stat::make('Pages', $totalPages)
                ->description("{$publishedPages} published")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Menus', $totalMenus)
                ->description("{$activeMenus} active")
                ->descriptionIcon('heroicon-m-bars-3')
                ->color('info'),

            Stat::make('Menu Items', $totalItems)
                ->description($totalMenus > 0 ? round($totalItems / $totalMenus, 1).' avg per menu' : 'No menus')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getHeading(): ?string
    {
        $siteId = $this->getMultisiteSiteId();
        $siteName = $this->getMultisiteName($siteId);

        return $siteName ? "Content Overview — {$siteName}" : 'Content Overview';
    }

    /**
     * Get the current admin-selected site ID from session.
     * Returns null when multisite is not active or "All Sites" is selected.
     */
    protected function getMultisiteSiteId(): ?int
    {
        $sessionValue = session('multisite_admin_site_id');

        // Explicit "All Sites" selection
        if ($sessionValue === '__all_sites__') {
            return null;
        }

        // Specific site selected
        if ($sessionValue && is_numeric($sessionValue)) {
            return (int) $sessionValue;
        }

        // No session yet (first login) — fall back based on role
        try {
            if (auth()->check() && ! auth()->user()->hasRole('super_admin')) {
                // Non-super-admin: first owned site (deterministic: oldest)
                $firstOwned = DB::table('tallcms_sites')
                    ->where('user_id', auth()->id())
                    ->where('is_active', true)
                    ->orderBy('created_at')
                    ->value('id');

                return $firstOwned ? (int) $firstOwned : null;
            }

            // Super-admin: global default site
            $default = DB::table('tallcms_sites')->where('is_default', true)->value('id');

            return $default ? (int) $default : null;
        } catch (QueryException) {
            return null;
        }
    }

    /**
     * Get the display name for the current multisite context.
     */
    protected function getMultisiteName(?int $siteId): ?string
    {
        $sessionValue = session('multisite_admin_site_id');

        if ($sessionValue === '__all_sites__') {
            return 'All Sites';
        }

        if (! $siteId) {
            return null;
        }

        try {
            $site = DB::table('tallcms_sites')->where('id', $siteId)->first();

            return $site?->name;
        } catch (QueryException) {
            return null;
        }
    }
}
