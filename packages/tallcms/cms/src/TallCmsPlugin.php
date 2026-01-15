<?php

declare(strict_types=1);

namespace TallCms\Cms;

use Filament\Contracts\Plugin;
use Filament\Panel;

class TallCmsPlugin implements Plugin
{
    protected ?string $navigationGroup = 'CMS';

    protected ?int $navigationSort = null;

    public function getId(): string
    {
        return 'tallcms';
    }

    public function register(Panel $panel): void
    {
        // Store config for resources/pages to access
        config(['tallcms.filament.navigation_group' => $this->navigationGroup]);
        config(['tallcms.filament.navigation_sort' => $this->navigationSort]);

        // Always register core CMS resources
        $panel
            ->resources($this->getResources())
            ->pages($this->getPages())
            ->widgets($this->getWidgets());

        // Standalone-only pages (theme manager, plugin manager, updater)
        if (app(TallCmsServiceProvider::class)->isStandaloneMode()) {
            $panel->pages($this->getStandalonePages());
        }

        // Load TallCMS plugins and register their Filament components
        if ($this->shouldLoadPlugins()) {
            $this->registerTallCmsPlugins($panel);
        }
    }

    public function boot(Panel $panel): void
    {
        // Post-registration setup
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Set the navigation group for all TallCMS resources and pages.
     */
    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Set the navigation sort order for TallCMS resources and pages.
     */
    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Get the core CMS resources to register.
     *
     * @return array<class-string>
     */
    protected function getResources(): array
    {
        // TODO: Return actual resources after extraction
        return [
            // Filament\Resources\CmsPageResource::class,
            // Filament\Resources\CmsPostResource::class,
            // Filament\Resources\CmsCategoryResource::class,
            // Filament\Resources\TallcmsMediaResource::class,
            // Filament\Resources\TallcmsMenuResource::class,
            // Filament\Resources\TallcmsContactSubmissionResource::class,
        ];
    }

    /**
     * Get the pages to register in all modes.
     *
     * @return array<class-string>
     */
    protected function getPages(): array
    {
        // TODO: Return actual pages after extraction
        return [
            // Filament\Pages\SiteSettings::class,
            // Filament\Pages\MenuItemsManager::class,
        ];
    }

    /**
     * Get pages only available in standalone mode.
     *
     * @return array<class-string>
     */
    protected function getStandalonePages(): array
    {
        // TODO: Return actual standalone pages after extraction
        return [
            // Filament\Pages\ThemeManager::class,
            // Filament\Pages\PluginManager::class,
            // Filament\Pages\PluginLicenses::class,
            // Filament\Pages\SystemUpdates::class,
            // Filament\Pages\UpdateProgress::class,
            // Filament\Pages\UpdateManual::class,
        ];
    }

    /**
     * Get the widgets to register.
     *
     * @return array<class-string>
     */
    protected function getWidgets(): array
    {
        // TODO: Return actual widgets after extraction
        return [
            // Filament\Widgets\MenuOverviewWidget::class,
            // Filament\Widgets\PluginUpdatesWidget::class,
        ];
    }

    /**
     * Determine if TallCMS plugins should be loaded.
     */
    protected function shouldLoadPlugins(): bool
    {
        $pluginsPath = config('tallcms.plugin_mode.plugins_path');

        // Standalone: always load from plugins/
        if (app(TallCmsServiceProvider::class)->isStandaloneMode()) {
            return is_dir(base_path('plugins'));
        }

        // Plugin mode: only if plugins_path is configured
        return $pluginsPath !== null && is_dir($pluginsPath);
    }

    /**
     * Register TallCMS plugins with the Filament panel.
     */
    protected function registerTallCmsPlugins(Panel $panel): void
    {
        // TODO: Implement after PluginManager is extracted
        // $tallcmsPlugins = app(Services\PluginManager::class)->getFilamentPlugins();
        // foreach ($tallcmsPlugins as $plugin) {
        //     $plugin->register($panel);
        // }
    }
}
