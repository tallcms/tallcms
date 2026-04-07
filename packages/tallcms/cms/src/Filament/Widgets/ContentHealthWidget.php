<?php

namespace TallCms\Cms\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContentHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $siteId = $this->getMultisiteSiteId();

        $postQuery = DB::table('tallcms_posts')
            ->whereNull('deleted_at')
            ->where('status', 'published');

        if ($siteId && Schema::hasColumn('tallcms_posts', 'site_id')) {
            $postQuery->where('site_id', $siteId);
        }

        $publishedPosts = (clone $postQuery)->count();

        // Posts needing review: never reviewed OR reviewed > 6 months ago
        $staleThreshold = Carbon::now()->subMonths(6);
        $needsReview = 0;
        if (Schema::hasColumn('tallcms_posts', 'last_reviewed_at')) {
            $needsReview = (clone $postQuery)
                ->where(function ($q) use ($staleThreshold) {
                    $q->whereNull('last_reviewed_at')
                        ->orWhere('last_reviewed_at', '<', $staleThreshold);
                })
                ->count();
        }

        // Posts with missing meta description
        $missingMeta = (clone $postQuery)
            ->where(function ($q) {
                $q->whereNull('meta_description')
                    ->orWhere('meta_description', '');
            })
            ->count();

        // Posts missing featured image
        $missingImage = (clone $postQuery)
            ->where(function ($q) {
                $q->whereNull('featured_image')
                    ->orWhere('featured_image', '');
            })
            ->count();

        return [
            Stat::make('Published Posts', $publishedPosts)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Needs Review', $needsReview)
                ->description('Not reviewed in 6+ months')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($needsReview > 0 ? 'danger' : 'success'),

            Stat::make('Missing Meta', $missingMeta)
                ->description("{$missingImage} also missing image")
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color($missingMeta > 0 ? 'warning' : 'success'),
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

        return $siteName ? "Content Health — {$siteName}" : 'Content Health';
    }

    protected function getMultisiteSiteId(): ?int
    {
        $sessionValue = session('multisite_admin_site_id');

        if ($sessionValue === '__all_sites__') {
            return null;
        }

        if ($sessionValue && is_numeric($sessionValue)) {
            return (int) $sessionValue;
        }

        try {
            if (auth()->check() && ! auth()->user()->hasRole('super_admin')) {
                $firstOwned = DB::table('tallcms_sites')
                    ->where('user_id', auth()->id())
                    ->where('is_active', true)
                    ->orderBy('created_at')
                    ->value('id');

                return $firstOwned ? (int) $firstOwned : null;
            }

            $default = DB::table('tallcms_sites')->where('is_default', true)->value('id');

            return $default ? (int) $default : null;
        } catch (QueryException) {
            return null;
        }
    }

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
