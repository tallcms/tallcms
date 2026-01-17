<?php

declare(strict_types=1);

namespace TallCms\Cms;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use TallCms\Cms\Filament\Pages\MenuItemsManager;
use TallCms\Cms\Filament\Pages\PluginLicenses;
use TallCms\Cms\Filament\Pages\PluginManager;
use TallCms\Cms\Filament\Pages\SiteSettings;
use TallCms\Cms\Filament\Pages\SystemUpdates;
use TallCms\Cms\Filament\Pages\ThemeManager;
use TallCms\Cms\Filament\Pages\UpdateManual;
use TallCms\Cms\Filament\Pages\UpdateProgress;
use TallCms\Cms\Filament\Resources\CmsCategories\CmsCategoryResource;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;
use TallCms\Cms\Filament\Resources\Users\UserResource;
use TallCms\Cms\Filament\Widgets\MenuOverviewWidget;
use TallCms\Cms\Filament\Widgets\PluginUpdatesWidget;

class TallCmsPlugin implements Plugin
{
    protected bool $hasCategories = true;

    protected bool $hasPages = true;

    protected bool $hasPosts = true;

    protected bool $hasContactSubmissions = true;

    protected bool $hasMedia = true;

    protected bool $hasMenus = true;

    protected bool $hasUsers = true;

    protected bool $hasSiteSettings = true;

    protected bool $hasPluginManager = true;

    protected bool $hasThemeManager = true;

    protected bool $hasSystemUpdates = true;

    protected bool $hasShieldPlugin = true;

    protected ?string $shieldNavigationGroup = 'User Management';

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

    public function getId(): string
    {
        return 'tallcms';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources($this->getResources())
            ->pages($this->getPages())
            ->widgets($this->getWidgets());

        // Register FilamentShieldPlugin for role/permission management
        if ($this->hasShieldPlugin) {
            $shieldPlugin = FilamentShieldPlugin::make();

            if ($this->shieldNavigationGroup !== null) {
                $shieldPlugin->navigationGroup($this->shieldNavigationGroup);
            }

            $panel->plugin($shieldPlugin);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Get all enabled CMS pages.
     *
     * @return array<class-string>
     */
    public function getPages(): array
    {
        $pages = [];

        if ($this->hasSiteSettings) {
            $pages[] = SiteSettings::class;
        }

        // MenuItemsManager is always included when menus are enabled
        // (it's hidden from nav, used by TallcmsMenuResource)
        if ($this->hasMenus) {
            $pages[] = MenuItemsManager::class;
        }

        // Plugin Manager: visible in standalone mode OR when explicitly enabled in plugin mode
        if ($this->hasPluginManager && $this->isPluginSystemEnabled()) {
            $pages[] = PluginManager::class;
            $pages[] = PluginLicenses::class;
        }

        // Theme Manager: visible in standalone mode OR when explicitly enabled in plugin mode
        if ($this->hasThemeManager && $this->isThemeSystemEnabled()) {
            $pages[] = ThemeManager::class;
        }

        // System Updates only available in standalone mode
        // (requires TallCmsUpdater service and GitHub release infrastructure)
        if ($this->isStandaloneMode() && $this->hasSystemUpdates) {
            $pages[] = SystemUpdates::class;
            $pages[] = UpdateManual::class;
            $pages[] = UpdateProgress::class;
        }

        return $pages;
    }

    /**
     * Check if the plugin system is enabled.
     * Enabled by default in both standalone and plugin modes.
     */
    protected function isPluginSystemEnabled(): bool
    {
        return config('tallcms.plugin_mode.plugins_enabled', true);
    }

    /**
     * Check if the theme system is enabled.
     * Enabled by default in both standalone and plugin modes.
     */
    protected function isThemeSystemEnabled(): bool
    {
        return config('tallcms.plugin_mode.themes_enabled', true);
    }

    /**
     * Check if running in standalone mode.
     */
    protected function isStandaloneMode(): bool
    {
        // 1. Explicit config takes precedence
        if (config('tallcms.mode') !== null) {
            return config('tallcms.mode') === 'standalone';
        }

        // 2. Auto-detect: standalone has .tallcms-standalone marker
        return file_exists(base_path('.tallcms-standalone'));
    }

    /**
     * Get all enabled CMS widgets.
     *
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        $widgets = [];

        if ($this->hasMenus) {
            $widgets[] = MenuOverviewWidget::class;
        }

        // Plugin updates widget: only when plugin system is enabled
        if ($this->hasPluginManager && $this->isPluginSystemEnabled()) {
            $widgets[] = PluginUpdatesWidget::class;
        }

        return $widgets;
    }

    /**
     * Get all enabled CMS resources.
     *
     * @return array<class-string>
     */
    public function getResources(): array
    {
        $resources = [];

        if ($this->hasCategories) {
            $resources[] = CmsCategoryResource::class;
        }

        if ($this->hasPages) {
            $resources[] = CmsPageResource::class;
        }

        if ($this->hasPosts) {
            $resources[] = CmsPostResource::class;
        }

        if ($this->hasContactSubmissions) {
            $resources[] = TallcmsContactSubmissionResource::class;
        }

        if ($this->hasMedia) {
            $resources[] = TallcmsMediaResource::class;
        }

        if ($this->hasMenus) {
            $resources[] = TallcmsMenuResource::class;
        }

        if ($this->hasUsers) {
            $resources[] = UserResource::class;
        }

        return $resources;
    }

    /**
     * Disable categories resource.
     */
    public function withoutCategories(): static
    {
        $this->hasCategories = false;

        return $this;
    }

    /**
     * Disable pages resource.
     */
    public function withoutPages(): static
    {
        $this->hasPages = false;

        return $this;
    }

    /**
     * Disable posts resource.
     */
    public function withoutPosts(): static
    {
        $this->hasPosts = false;

        return $this;
    }

    /**
     * Disable contact submissions resource.
     */
    public function withoutContactSubmissions(): static
    {
        $this->hasContactSubmissions = false;

        return $this;
    }

    /**
     * Disable media resource.
     */
    public function withoutMedia(): static
    {
        $this->hasMedia = false;

        return $this;
    }

    /**
     * Disable menus resource.
     */
    public function withoutMenus(): static
    {
        $this->hasMenus = false;

        return $this;
    }

    /**
     * Disable users resource.
     *
     * Use this if your app already has a UserResource
     * or you want to manage users differently.
     */
    public function withoutUsers(): static
    {
        $this->hasUsers = false;

        return $this;
    }

    /**
     * Disable site settings page.
     */
    public function withoutSiteSettings(): static
    {
        $this->hasSiteSettings = false;

        return $this;
    }

    /**
     * Disable plugin manager page.
     */
    public function withoutPluginManager(): static
    {
        $this->hasPluginManager = false;

        return $this;
    }

    /**
     * Disable theme manager page.
     */
    public function withoutThemeManager(): static
    {
        $this->hasThemeManager = false;

        return $this;
    }

    /**
     * Disable system updates pages.
     */
    public function withoutSystemUpdates(): static
    {
        $this->hasSystemUpdates = false;

        return $this;
    }

    /**
     * Disable automatic FilamentShieldPlugin registration.
     *
     * Use this if you want to register FilamentShieldPlugin yourself
     * with custom configuration.
     */
    public function withoutShieldPlugin(): static
    {
        $this->hasShieldPlugin = false;

        return $this;
    }

    /**
     * Set the navigation group for Shield's role management.
     *
     * @param  string|null  $group  Navigation group name, or null to use Shield's default
     */
    public function shieldNavigationGroup(?string $group): static
    {
        $this->shieldNavigationGroup = $group;

        return $this;
    }
}
