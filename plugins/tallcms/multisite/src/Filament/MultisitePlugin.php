<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use TallCms\Cms\Services\PluginLicenseService;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;
use Tallcms\Multisite\Http\Middleware\MarkAdminContext;
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
        // Only register admin UI when licensed
        if (! $this->isLicensed()) {
            return;
        }

        // Mark admin context on every Filament panel request (including Livewire).
        // The CurrentSiteResolver reads this attribute to reliably detect admin
        // context without depending on URL patterns or Referer headers.
        $panel->middleware([
            MarkAdminContext::class,
        ]);

        $panel->resources([
            SiteResource::class,
        ]);

        $panel->renderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn () => Blade::render(
                '@include("tallcms-multisite::filament.site-switcher", [
                    "sites" => $sites,
                    "currentSite" => $currentSite,
                    "allSitesMode" => $allSitesMode,
                ])',
                [
                    'sites' => Site::where('is_active', true)->orderBy('name')->get(),
                    'currentSite' => app(CurrentSiteResolver::class)->get(),
                    'allSitesMode' => app(CurrentSiteResolver::class)->isAllSitesMode(),
                ]
            ),
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function isLicensed(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        try {
            $licenseService = app(PluginLicenseService::class);

            if ($licenseService->isValid('tallcms/multisite')) {
                return true;
            }

            return $licenseService->hasEverBeenLicensed('tallcms/multisite');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
