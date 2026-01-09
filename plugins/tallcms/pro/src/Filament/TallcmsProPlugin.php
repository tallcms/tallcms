<?php

namespace Tallcms\Pro\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Tallcms\Pro\Filament\Pages\ProSettings;
use Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget;

class TallcmsProPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tallcms-pro';
    }

    public function register(Panel $panel): void
    {
        // NOTE: License management is now handled by core TallCMS (Settings > Plugin Licenses)
        // This plugin only registers Pro-specific pages and widgets
        $panel
            ->pages([
                ProSettings::class,
            ])
            ->widgets([
                AnalyticsOverviewWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
