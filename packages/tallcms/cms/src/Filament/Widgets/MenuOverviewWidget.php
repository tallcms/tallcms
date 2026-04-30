<?php

namespace TallCms\Cms\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use TallCms\Cms\Filament\Widgets\Concerns\HasMultisiteWidgetContext;

class MenuOverviewWidget extends BaseWidget
{
    use HasMultisiteWidgetContext;

    #[On('dashboard.site-changed')]
    public function onSiteChanged(): void
    {
        // Empty body — Livewire re-renders the widget on event receipt,
        // which re-runs getStats() against the new session value.
    }

    protected function getStats(): array
    {
        $siteId = $this->getMultisiteSiteId();
        $siteName = $this->getMultisiteName($siteId);

        // site_id is only added to content tables when the multisite plugin
        // is installed. On standalone installs the column does not exist,
        // so we must guard every filter — the default-site fallback in
        // getMultisiteSiteId() will still hand us an id even in single-site mode.
        $pagesHaveSiteId = Schema::hasColumn('tallcms_pages', 'site_id');
        $menusHaveSiteId = Schema::hasColumn('tallcms_menus', 'site_id');

        // Use direct DB queries scoped by site — avoids reliance on SiteScope
        // which may not be resolved at widget render time in admin context.
        $pageQuery = DB::table('tallcms_pages')->whereNull('deleted_at');
        $publishedPageQuery = DB::table('tallcms_pages')->whereNull('deleted_at')->where('status', 'published');
        $menuQuery = DB::table('tallcms_menus');
        $activeMenuQuery = DB::table('tallcms_menus')->where('is_active', true);

        if ($siteId && $pagesHaveSiteId) {
            $pageQuery->where('site_id', $siteId);
            $publishedPageQuery->where('site_id', $siteId);
        }

        if ($siteId && $menusHaveSiteId) {
            $menuQuery->where('site_id', $siteId);
            $activeMenuQuery->where('site_id', $siteId);
        }

        $totalPages = $pageQuery->count();
        $publishedPages = $publishedPageQuery->count();
        $totalMenus = $menuQuery->count();
        $activeMenus = $activeMenuQuery->count();

        // Menu items: scope through menus
        $menuIds = ($siteId && $menusHaveSiteId
            ? DB::table('tallcms_menus')->where('site_id', $siteId)
            : DB::table('tallcms_menus'))
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

}
