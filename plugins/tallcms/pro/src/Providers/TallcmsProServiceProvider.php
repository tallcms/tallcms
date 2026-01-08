<?php

namespace Tallcms\Pro\Providers;

use Illuminate\Support\ServiceProvider;
use Tallcms\Pro\Services\AnystackClient;
use Tallcms\Pro\Services\LicenseService;

class TallcmsProServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * IMPORTANT: Do NOT register routes here - plugin routes are handled
     * by PluginServiceProvider via routes/public.php and routes/web.php
     */
    public function register(): void
    {
        // Merge plugin configuration
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'tallcms-pro');

        // Register the Anystack client as a singleton
        $this->app->singleton(AnystackClient::class, function ($app) {
            return new AnystackClient(
                config('tallcms-pro.anystack.api_url'),
                config('tallcms-pro.anystack.product_id')
            );
        });

        // Register the license service as a singleton
        $this->app->singleton(LicenseService::class, function ($app) {
            return new LicenseService(
                $app->make(AnystackClient::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * IMPORTANT: Do NOT register routes here - plugin routes are handled
     * by PluginServiceProvider via routes/public.php and routes/web.php
     */
    public function boot(): void
    {
        // Load views from the plugin's resources/views directory
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tallcms-pro');
    }
}
