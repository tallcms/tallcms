<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Tallcms\Multisite\Filament\Pages\SiteSettingsPage;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class MultisitePlugin implements Plugin
{
    public function getId(): string
    {
        return 'tallcms-multisite';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SiteResource::class,
        ]);

        $panel->pages([
            SiteSettingsPage::class,
        ]);

        $panel->renderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn () => Blade::render(
                '@include("tallcms-multisite::filament.site-switcher", [
                    "sites" => $sites,
                    "currentSite" => $currentSite,
                ])',
                [
                    'sites' => Site::where('is_active', true)->orderBy('name')->get(),
                    'currentSite' => app(CurrentSiteResolver::class)->get(),
                ]
            ),
        );
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
