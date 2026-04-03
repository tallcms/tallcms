<?php

namespace TallCms\Cms\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\TallcmsMenu;
use TallCms\Cms\Models\TallcmsMenuItem;

class MenuOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $siteName = $this->getMultisiteName();

        // Page stats — CmsPage has SiteScope, so count is site-aware
        $totalPages = CmsPage::count();
        $publishedPages = CmsPage::where('status', 'published')->count();

        // Menu stats — TallcmsMenu has SiteScope
        $totalMenus = TallcmsMenu::count();
        $activeMenus = TallcmsMenu::where('is_active', true)->count();

        // Menu item stats — no SiteScope on items, so scope through menus
        $menuIds = TallcmsMenu::pluck('id');
        $totalItems = TallcmsMenuItem::whereIn('menu_id', $menuIds)->count();

        $stats = [
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

        return $stats;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getHeading(): ?string
    {
        $siteName = $this->getMultisiteName();

        return $siteName ? "Content Overview — {$siteName}" : 'Content Overview';
    }

    /**
     * Get the current site name for multisite context indicator.
     */
    protected function getMultisiteName(): ?string
    {
        $sessionValue = session('multisite_admin_site_id');

        if (! $sessionValue || $sessionValue === '__all_sites__') {
            // Check if multisite is active but "All Sites" is selected
            if ($sessionValue === '__all_sites__') {
                return 'All Sites';
            }

            return null;
        }

        try {
            $site = DB::table('tallcms_sites')->where('id', $sessionValue)->first();

            return $site?->name;
        } catch (QueryException) {
            return null;
        }
    }
}
