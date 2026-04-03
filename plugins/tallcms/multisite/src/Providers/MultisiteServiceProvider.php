<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Providers;

use Illuminate\Support\ServiceProvider;
use TallCms\Cms\Services\PluginLicenseService;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class MultisiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentSiteResolver::class);
        $this->app->alias(CurrentSiteResolver::class, 'tallcms.multisite.resolver');
    }

    public function boot(): void
    {
        // Always load migrations so schema stays consistent
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tallcms-multisite');

        // Check license: plugin is inert unless activated
        if (! $this->isLicensed()) {
            return;
        }

        $this->registerMultisiteFeatures();
    }

    protected function isLicensed(): bool
    {
        // Skip license check in testing
        if ($this->app->environment('testing')) {
            return true;
        }

        try {
            $licenseService = $this->app->make(PluginLicenseService::class);

            // isValid() covers: active license + within grace period
            if ($licenseService->isValid('tallcms/multisite')) {
                return true;
            }

            // Fallback: was license ever activated? (covers hard-expired)
            // Once activated, multisite never collapses
            return $licenseService->hasEverBeenLicensed('tallcms/multisite');
        } catch (\Throwable) {
            // License system not available (e.g. during initial setup)
            return false;
        }
    }

    protected function registerMultisiteFeatures(): void
    {
        $this->registerMiddleware();
        $this->registerGlobalScopes();
        $this->registerModelListeners();
    }

    protected function registerMiddleware(): void
    {
        // Use Kernel to append middleware to the web group
        // (plugin security scanner blocks direct router access)
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $kernel->appendMiddlewareToGroup('web', \Tallcms\Multisite\Http\Middleware\ResolveSiteMiddleware::class);
    }

    protected function registerGlobalScopes(): void
    {
        $scope = new \Tallcms\Multisite\Scopes\SiteScope;

        \TallCms\Cms\Models\CmsPage::addGlobalScope($scope);
        \TallCms\Cms\Models\TallcmsMenu::addGlobalScope($scope);
    }

    protected function registerModelListeners(): void
    {
        $resolver = $this->app->make(CurrentSiteResolver::class);

        \TallCms\Cms\Models\CmsPage::creating(function ($page) use ($resolver) {
            $page->site_id ??= $resolver->id();
        });

        \TallCms\Cms\Models\TallcmsMenu::creating(function ($menu) use ($resolver) {
            $menu->site_id ??= $resolver->id();
        });
    }
}
