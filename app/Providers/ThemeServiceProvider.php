<?php

namespace App\Providers;

use App\Contracts\ThemeInterface;
use App\Services\ThemeResolver;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the theme resolver
        $this->app->singleton(ThemeResolver::class);
        
        // Bind the active theme to the container
        $this->bindActiveTheme();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish theme configuration
        $this->publishes([
            __DIR__ . '/../../config/theme.php' => config_path('theme.php'),
        ], 'theme-config');
    }
    
    /**
     * Bind the currently active theme to the container
     */
    protected function bindActiveTheme(): void
    {
        try {
            $activeTheme = config('theme.active', 'default');
            ThemeResolver::bindTheme($activeTheme);
        } catch (\Exception $e) {
            // Fallback to default theme if binding fails
            \Log::warning("Failed to bind active theme: {$e->getMessage()}. Falling back to default theme.");
            ThemeResolver::bindTheme('default');
        }
    }
}