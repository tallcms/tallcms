<?php

declare(strict_types=1);

namespace TallCms\Cms;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TallCmsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'tallcms';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile(['tallcms', 'theme'])
            ->hasViews('tallcms')
            ->hasMigrations($this->getMigrations())
            ->hasTranslations()
            ->hasAssets()
            ->hasCommands($this->getCommands());
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        // Only bind TallCmsUpdater service in standalone mode
        if ($this->isStandaloneMode()) {
            // $this->app->singleton(Services\TallCmsUpdater::class);
        }
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Register assets only if published
        $cssPath = public_path('vendor/tallcms/tallcms.css');
        $jsPath = public_path('vendor/tallcms/tallcms.js');

        if (file_exists($cssPath) && file_exists($jsPath)) {
            FilamentAsset::register([
                Css::make('tallcms-styles', $cssPath),
                Js::make('tallcms-scripts', $jsPath),
            ], 'tallcms/cms');
        }

        // Boot mode-specific features
        if ($this->isStandaloneMode()) {
            $this->bootStandaloneFeatures();
        } else {
            $this->bootPluginFeatures();
        }
    }

    /**
     * Determine if running in standalone mode (full TallCMS skeleton)
     * vs plugin mode (installed in existing Filament app)
     */
    public function isStandaloneMode(): bool
    {
        // 1. Explicit config takes precedence
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        // 2. Auto-detect: Check if installed via skeleton vs require
        // Standalone: tallcms/tallcms skeleton (has .tallcms-standalone marker)
        // Plugin: composer require tallcms/cms (no marker)
        return file_exists(base_path('.tallcms-standalone'));
    }

    /**
     * Boot features for standalone mode (full TallCMS installation)
     */
    protected function bootStandaloneFeatures(): void
    {
        // Standalone: all features enabled, routes at root
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    /**
     * Boot features for plugin mode (installed in existing Filament app)
     */
    protected function bootPluginFeatures(): void
    {
        // Plugin mode: routes are OPT-IN and require explicit prefix
        if (config('tallcms.plugin_mode.routes_enabled', false)) {
            $prefix = config('tallcms.plugin_mode.routes_prefix');

            // REQUIRE prefix in plugin mode to avoid route conflicts
            if (empty($prefix)) {
                throw new \RuntimeException(
                    'TallCMS: routes_prefix is required in plugin mode. ' .
                    'Set tallcms.plugin_mode.routes_prefix to a value like "cms" or "pages".'
                );
            }

            // Verify assets are published before enabling frontend
            if (! file_exists(public_path('vendor/tallcms/tallcms.css'))) {
                throw new \RuntimeException(
                    'TallCMS frontend routes require published assets. ' .
                    'Run: php artisan vendor:publish --tag=tallcms-assets'
                );
            }

            Route::prefix($prefix)
                ->middleware(['web'])
                ->group(__DIR__ . '/../routes/web.php');
        }
    }

    /**
     * Get the migrations that should be published.
     */
    protected function getMigrations(): array
    {
        // TODO: Add all 29 migrations
        return [
            // 'create_tallcms_pages_table',
            // 'create_tallcms_posts_table',
            // etc.
        ];
    }

    /**
     * Get the commands that should be registered.
     */
    protected function getCommands(): array
    {
        $commands = [
            // Console\Commands\TallCmsInstall::class,
            // Console\Commands\MakeTheme::class,
        ];

        // Only register updater commands in standalone mode
        if ($this->isStandaloneMode()) {
            // $commands[] = Console\Commands\TallCmsUpdate::class;
        }

        return $commands;
    }
}
