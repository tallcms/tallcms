<?php

namespace Tallcms\Pro\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Tallcms\Pro\Filament\Pages\ProLicense;
use Tallcms\Pro\Filament\Pages\ProSettings;
use Tallcms\Pro\Filament\Widgets\LicenseStatusWidget;

class TallcmsProPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tallcms-pro';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                ProLicense::class,
                ProSettings::class,
            ])
            ->widgets([
                LicenseStatusWidget::class,
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
