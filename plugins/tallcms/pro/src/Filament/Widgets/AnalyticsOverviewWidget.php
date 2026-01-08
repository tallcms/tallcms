<?php

namespace Tallcms\Pro\Filament\Widgets;

use Filament\Widgets\Widget;
use Tallcms\Pro\Services\Analytics\AnalyticsManager;
use Tallcms\Pro\Services\LicenseService;

class AnalyticsOverviewWidget extends Widget
{
    protected string $view = 'tallcms-pro::filament.widgets.analytics-overview';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public string $period = '7d';

    public array $metrics = [];

    public array $topPages = [];

    public array $trafficSources = [];

    public array $visitorTrend = [];

    public bool $isConfigured = false;

    public bool $isLicensed = false;

    public function mount(): void
    {
        $this->isLicensed = app(LicenseService::class)->isValid();

        if (! $this->isLicensed) {
            return;
        }

        $manager = app(AnalyticsManager::class);
        $this->isConfigured = $manager->isConfigured();

        if ($this->isConfigured) {
            $this->loadData();
        }
    }

    public function loadData(): void
    {
        $manager = app(AnalyticsManager::class);

        $this->metrics = $manager->getOverviewMetrics($this->period);
        $this->topPages = $manager->getTopPages(5, $this->period);
        $this->trafficSources = $manager->getTrafficSources(5, $this->period);
        $this->visitorTrend = $manager->getVisitorTrend($this->period);
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;

        if ($this->isConfigured && $this->isLicensed) {
            $this->loadData();
        }
    }

    public function refreshData(): void
    {
        if (! $this->isLicensed) {
            return;
        }

        $manager = app(AnalyticsManager::class);
        $manager->clearCache();
        $this->loadData();
    }

    public static function canView(): bool
    {
        // Always show the widget, but display appropriate message if not configured
        return true;
    }

    protected function getViewData(): array
    {
        return [
            'period' => $this->period,
            'metrics' => $this->metrics,
            'topPages' => $this->topPages,
            'trafficSources' => $this->trafficSources,
            'visitorTrend' => $this->visitorTrend,
            'isConfigured' => $this->isConfigured,
            'isLicensed' => $this->isLicensed,
            'periods' => [
                '24h' => 'Last 24 hours',
                '7d' => 'Last 7 days',
                '30d' => 'Last 30 days',
                '90d' => 'Last 90 days',
            ],
        ];
    }
}
