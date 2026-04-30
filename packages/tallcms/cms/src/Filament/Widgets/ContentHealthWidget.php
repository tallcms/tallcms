<?php

namespace TallCms\Cms\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use TallCms\Cms\Filament\Widgets\Concerns\HasMultisiteWidgetContext;

class ContentHealthWidget extends BaseWidget
{
    use HasMultisiteWidgetContext;

    protected static ?int $sort = 1;

    #[On('dashboard.site-changed')]
    public function onSiteChanged(): void
    {
        // Empty body — Livewire re-renders the widget on event receipt,
        // which re-runs getStats() against the new session value.
    }

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

}
