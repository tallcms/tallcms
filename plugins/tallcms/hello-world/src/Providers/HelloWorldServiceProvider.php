<?php

namespace Tallcms\HelloWorld\Providers;

use Illuminate\Support\ServiceProvider;

class HelloWorldServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tallcms-helloworld');

        // Publish views (optional)
        // $this->publishes([
        //     __DIR__.'/../../resources/views' => resource_path('views/vendor/tallcms-helloworld'),
        // ], 'views');
    }
}
