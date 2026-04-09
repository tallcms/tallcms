<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use TallCms\Cms\Services\PluginLicenseService;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;
use Tallcms\Multisite\Http\Middleware\MarkAdminContext;

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
        $panel->middleware([
            MarkAdminContext::class,
        ]);

        // Prepend Platform group to navigation order
        $panel->navigationGroups([
            \Filament\Navigation\NavigationGroup::make(config('tallcms.navigation.groups.platform', 'Platform')),
            \Filament\Navigation\NavigationGroup::make(config('tallcms.navigation.groups.content', 'Content')),
            \Filament\Navigation\NavigationGroup::make(config('tallcms.navigation.groups.appearance', 'Appearance')),
            \Filament\Navigation\NavigationGroup::make(config('tallcms.navigation.groups.configuration', 'Configuration')),
            \Filament\Navigation\NavigationGroup::make(config('tallcms.navigation.groups.system', 'System')),
        ]);

        $panel->resources([
            SiteResource::class,
        ]);

        $panel->renderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn () => view('tallcms-multisite::livewire.site-switcher-hook')->render(),
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

            // Check hasEverBeenLicensed first (DB-only, no proxy call)
            if ($licenseService->hasEverBeenLicensed('tallcms/multisite')) {
                return true;
            }

            return $licenseService->isValid('tallcms/multisite');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
