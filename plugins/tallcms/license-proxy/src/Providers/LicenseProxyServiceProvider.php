<?php

namespace Tallcms\LicenseProxy\Providers;

use Illuminate\Support\ServiceProvider;

class LicenseProxyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'license-proxy');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
