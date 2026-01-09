<?php

namespace Tallcms\Pro\Providers;

use Illuminate\Support\ServiceProvider;

class TallcmsProServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * IMPORTANT: Do NOT register routes here - plugin routes are handled
     * by PluginServiceProvider via routes/public.php and routes/web.php
     *
     * NOTE: License services are now handled by core TallCMS PluginLicenseService.
     * This plugin only needs to set "license_required": true in plugin.json.
     */
    public function register(): void
    {
        // Merge plugin configuration
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'tallcms-pro');
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
