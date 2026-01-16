<?php

declare(strict_types=1);

namespace TallCms\Cms;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TallCms\Cms\Providers\PluginServiceProvider;
use TallCms\Cms\Providers\ThemeServiceProvider;

class TallCmsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'tallcms';

    /**
     * Class aliases for backwards compatibility.
     * Maps App\* classes to their new TallCms\Cms\* locations.
     */
    protected array $classAliases = [
        // Contracts
        'App\\Contracts\\ThemeInterface' => Contracts\ThemeInterface::class,

        // Exceptions
        'App\\Exceptions\\ConfigurationException' => Exceptions\ConfigurationException::class,
        'App\\Exceptions\\DownloadException' => Exceptions\DownloadException::class,
        'App\\Exceptions\\ExtractionException' => Exceptions\ExtractionException::class,
        'App\\Exceptions\\IncompatiblePlatformException' => Exceptions\IncompatiblePlatformException::class,
        'App\\Exceptions\\IncompatibleVersionException' => Exceptions\IncompatibleVersionException::class,
        'App\\Exceptions\\InsufficientDiskSpaceException' => Exceptions\InsufficientDiskSpaceException::class,
        'App\\Exceptions\\IntegrityException' => Exceptions\IntegrityException::class,
        'App\\Exceptions\\InvalidReleaseException' => Exceptions\InvalidReleaseException::class,
        'App\\Exceptions\\MissingDependencyException' => Exceptions\MissingDependencyException::class,
        'App\\Exceptions\\SecurityException' => Exceptions\SecurityException::class,
        'App\\Exceptions\\SignatureException' => Exceptions\SignatureException::class,
        'App\\Exceptions\\UpdateException' => Exceptions\UpdateException::class,
        'App\\Exceptions\\UpdateInProgressException' => Exceptions\UpdateInProgressException::class,

        // Models
        'App\\Models\\CmsCategory' => Models\CmsCategory::class,
        'App\\Models\\CmsPage' => Models\CmsPage::class,
        'App\\Models\\CmsPost' => Models\CmsPost::class,
        'App\\Models\\Plugin' => Models\Plugin::class,
        'App\\Models\\PluginLicense' => Models\PluginLicense::class,
        'App\\Models\\Theme' => Models\Theme::class,
        'App\\Models\\TallcmsMenu' => Models\TallcmsMenu::class,
        'App\\Models\\TallcmsMenuItem' => Models\TallcmsMenuItem::class,
        'App\\Models\\SiteSetting' => Models\SiteSetting::class,
        'App\\Models\\TallcmsContactSubmission' => Models\TallcmsContactSubmission::class,
        'App\\Models\\CmsRevision' => Models\CmsRevision::class,
        'App\\Models\\CmsPreviewToken' => Models\CmsPreviewToken::class,
        'App\\Models\\TallcmsMedia' => Models\TallcmsMedia::class,
        'App\\Models\\MediaCollection' => Models\MediaCollection::class,

        // Model Concerns
        'App\\Models\\Concerns\\HasPublishingWorkflow' => Models\Concerns\HasPublishingWorkflow::class,
        'App\\Models\\Concerns\\HasPreviewTokens' => Models\Concerns\HasPreviewTokens::class,
        'App\\Models\\Concerns\\HasRevisions' => Models\Concerns\HasRevisions::class,

        // Services
        'App\\Services\\BlockLinkResolver' => Services\BlockLinkResolver::class,
        'App\\Services\\ContentDiffService' => Services\ContentDiffService::class,
        'App\\Services\\CustomBlockDiscoveryService' => Services\CustomBlockDiscoveryService::class,
        'App\\Services\\EnvWriter' => Services\EnvWriter::class,
        'App\\Services\\EnvironmentChecker' => Services\EnvironmentChecker::class,
        'App\\Services\\FileBasedTheme' => Services\FileBasedTheme::class,
        'App\\Services\\HtmlSanitizerService' => Services\HtmlSanitizerService::class,
        'App\\Services\\InstallerRunner' => Services\InstallerRunner::class,
        'App\\Services\\LicenseProxyClient' => Services\LicenseProxyClient::class,
        'App\\Services\\MenuUrlResolver' => Services\MenuUrlResolver::class,
        'App\\Services\\MergeTagService' => Services\MergeTagService::class,
        'App\\Services\\PluginLicenseService' => Services\PluginLicenseService::class,
        'App\\Services\\PluginManager' => Services\PluginManager::class,
        'App\\Services\\PluginMigrationRepository' => Services\PluginMigrationRepository::class,
        'App\\Services\\PluginMigrator' => Services\PluginMigrator::class,
        'App\\Services\\PluginValidator' => Services\PluginValidator::class,
        'App\\Services\\PublishingWorkflowService' => Services\PublishingWorkflowService::class,
        'App\\Services\\TallCmsUpdater' => Services\TallCmsUpdater::class,
        'App\\Services\\ThemeManager' => Services\ThemeManager::class,
        'App\\Services\\ThemeResolver' => Services\ThemeResolver::class,
        'App\\Services\\ThemeValidator' => Services\ThemeValidator::class,

        // Providers
        'App\\Providers\\PluginServiceProvider' => Providers\PluginServiceProvider::class,
        'App\\Providers\\ThemeServiceProvider' => Providers\ThemeServiceProvider::class,

        // Events
        'App\\Events\\PluginInstalled' => Events\PluginInstalled::class,
        'App\\Events\\PluginInstalling' => Events\PluginInstalling::class,
        'App\\Events\\PluginUninstalled' => Events\PluginUninstalled::class,
        'App\\Events\\PluginUninstalling' => Events\PluginUninstalling::class,
        'App\\Events\\ThemeActivated' => Events\ThemeActivated::class,
        'App\\Events\\ThemeActivating' => Events\ThemeActivating::class,
        'App\\Events\\ThemeInstalled' => Events\ThemeInstalled::class,
        'App\\Events\\ThemeInstalling' => Events\ThemeInstalling::class,
        'App\\Events\\ThemeRollback' => Events\ThemeRollback::class,

        // Notifications
        'App\\Notifications\\ContentApprovedNotification' => Notifications\ContentApprovedNotification::class,
        'App\\Notifications\\ContentRejectedNotification' => Notifications\ContentRejectedNotification::class,
        'App\\Notifications\\ContentSubmittedForReviewNotification' => Notifications\ContentSubmittedForReviewNotification::class,

        // Enums
        'App\\Enums\\ContentStatus' => Enums\ContentStatus::class,

        // Controllers
        'App\\Http\\Controllers\\ContactFormController' => Http\Controllers\ContactFormController::class,
        'App\\Http\\Controllers\\PreviewController' => Http\Controllers\PreviewController::class,

        // Middleware
        'App\\Http\\Middleware\\MaintenanceModeMiddleware' => Http\Middleware\MaintenanceModeMiddleware::class,
        'App\\Http\\Middleware\\ThemePreviewMiddleware' => Http\Middleware\ThemePreviewMiddleware::class,

        // Livewire Components
        'App\\Livewire\\CmsPageRenderer' => Livewire\CmsPageRenderer::class,

        // Mail
        'App\\Mail\\ContactFormAdminNotification' => Mail\ContactFormAdminNotification::class,
        'App\\Mail\\ContactFormAutoReply' => Mail\ContactFormAutoReply::class,

        // Console Commands
        'App\\Console\\Commands\\CleanExpiredPreviewTokens' => Console\Commands\CleanExpiredPreviewTokens::class,
        'App\\Console\\Commands\\LicenseTestCommand' => Console\Commands\LicenseTestCommand::class,
        'App\\Console\\Commands\\MakePluginCommand' => Console\Commands\MakePluginCommand::class,
        'App\\Console\\Commands\\MakeTallCmsBlock' => Console\Commands\MakeTallCmsBlock::class,
        'App\\Console\\Commands\\MakeTheme' => Console\Commands\MakeTheme::class,
        'App\\Console\\Commands\\PluginCleanupBackupsCommand' => Console\Commands\PluginCleanupBackupsCommand::class,
        'App\\Console\\Commands\\PluginInstallCommand' => Console\Commands\PluginInstallCommand::class,
        'App\\Console\\Commands\\PluginListCommand' => Console\Commands\PluginListCommand::class,
        'App\\Console\\Commands\\PluginMigrateCommand' => Console\Commands\PluginMigrateCommand::class,
        'App\\Console\\Commands\\PluginUninstallCommand' => Console\Commands\PluginUninstallCommand::class,
        'App\\Console\\Commands\\TallCmsGenerateKeypair' => Console\Commands\TallCmsGenerateKeypair::class,
        'App\\Console\\Commands\\TallCmsGenerateManifest' => Console\Commands\TallCmsGenerateManifest::class,
        'App\\Console\\Commands\\TallCmsSetup' => Console\Commands\TallCmsSetup::class,
        'App\\Console\\Commands\\TallCmsSignRelease' => Console\Commands\TallCmsSignRelease::class,
        'App\\Console\\Commands\\TallCmsUpdate' => Console\Commands\TallCmsUpdate::class,
        'App\\Console\\Commands\\TallCmsVersion' => Console\Commands\TallCmsVersion::class,
        'App\\Console\\Commands\\ThemeActivate' => Console\Commands\ThemeActivate::class,
        'App\\Console\\Commands\\ThemeBuild' => Console\Commands\ThemeBuild::class,
        'App\\Console\\Commands\\ThemeCacheClear' => Console\Commands\ThemeCacheClear::class,
        'App\\Console\\Commands\\ThemeInstallCommand' => Console\Commands\ThemeInstallCommand::class,
        'App\\Console\\Commands\\ThemeList' => Console\Commands\ThemeList::class,

        // Filament Blocks
        'App\\Filament\\Blocks\\CallToActionBlock' => Filament\Blocks\CallToActionBlock::class,
        'App\\Filament\\Blocks\\ContactFormBlock' => Filament\Blocks\ContactFormBlock::class,
        'App\\Filament\\Blocks\\ContentBlockBlock' => Filament\Blocks\ContentBlockBlock::class,
        'App\\Filament\\Blocks\\DividerBlock' => Filament\Blocks\DividerBlock::class,
        'App\\Filament\\Blocks\\FaqBlock' => Filament\Blocks\FaqBlock::class,
        'App\\Filament\\Blocks\\FeaturesBlock' => Filament\Blocks\FeaturesBlock::class,
        'App\\Filament\\Blocks\\HeroBlock' => Filament\Blocks\HeroBlock::class,
        'App\\Filament\\Blocks\\ImageGalleryBlock' => Filament\Blocks\ImageGalleryBlock::class,
        'App\\Filament\\Blocks\\LogosBlock' => Filament\Blocks\LogosBlock::class,
        'App\\Filament\\Blocks\\ParallaxBlock' => Filament\Blocks\ParallaxBlock::class,
        'App\\Filament\\Blocks\\PostsBlock' => Filament\Blocks\PostsBlock::class,
        'App\\Filament\\Blocks\\PricingBlock' => Filament\Blocks\PricingBlock::class,
        'App\\Filament\\Blocks\\StatsBlock' => Filament\Blocks\StatsBlock::class,
        'App\\Filament\\Blocks\\TeamBlock' => Filament\Blocks\TeamBlock::class,
        'App\\Filament\\Blocks\\TestimonialsBlock' => Filament\Blocks\TestimonialsBlock::class,
        'App\\Filament\\Blocks\\TimelineBlock' => Filament\Blocks\TimelineBlock::class,

        // Filament Block Concerns
        'App\\Filament\\Blocks\\Concerns\\HasDaisyUIOptions' => Filament\Blocks\Concerns\HasDaisyUIOptions::class,

        // Filament Resources - CmsCategories
        'App\\Filament\\Resources\\CmsCategories\\CmsCategoryResource' => Filament\Resources\CmsCategories\CmsCategoryResource::class,
        'App\\Filament\\Resources\\CmsCategories\\Pages\\CreateCmsCategory' => Filament\Resources\CmsCategories\Pages\CreateCmsCategory::class,
        'App\\Filament\\Resources\\CmsCategories\\Pages\\EditCmsCategory' => Filament\Resources\CmsCategories\Pages\EditCmsCategory::class,
        'App\\Filament\\Resources\\CmsCategories\\Pages\\ListCmsCategories' => Filament\Resources\CmsCategories\Pages\ListCmsCategories::class,
        'App\\Filament\\Resources\\CmsCategories\\Schemas\\CmsCategoryForm' => Filament\Resources\CmsCategories\Schemas\CmsCategoryForm::class,
        'App\\Filament\\Resources\\CmsCategories\\Tables\\CmsCategoriesTable' => Filament\Resources\CmsCategories\Tables\CmsCategoriesTable::class,

        // Filament Resources - CmsPages
        'App\\Filament\\Resources\\CmsPages\\CmsPageResource' => Filament\Resources\CmsPages\CmsPageResource::class,
        'App\\Filament\\Resources\\CmsPages\\Pages\\CreateCmsPage' => Filament\Resources\CmsPages\Pages\CreateCmsPage::class,
        'App\\Filament\\Resources\\CmsPages\\Pages\\EditCmsPage' => Filament\Resources\CmsPages\Pages\EditCmsPage::class,
        'App\\Filament\\Resources\\CmsPages\\Pages\\ListCmsPages' => Filament\Resources\CmsPages\Pages\ListCmsPages::class,
        'App\\Filament\\Resources\\CmsPages\\Schemas\\CmsPageForm' => Filament\Resources\CmsPages\Schemas\CmsPageForm::class,
        'App\\Filament\\Resources\\CmsPages\\Tables\\CmsPagesTable' => Filament\Resources\CmsPages\Tables\CmsPagesTable::class,

        // Filament Resources - CmsPosts
        'App\\Filament\\Resources\\CmsPosts\\CmsPostResource' => Filament\Resources\CmsPosts\CmsPostResource::class,
        'App\\Filament\\Resources\\CmsPosts\\Pages\\CreateCmsPost' => Filament\Resources\CmsPosts\Pages\CreateCmsPost::class,
        'App\\Filament\\Resources\\CmsPosts\\Pages\\EditCmsPost' => Filament\Resources\CmsPosts\Pages\EditCmsPost::class,
        'App\\Filament\\Resources\\CmsPosts\\Pages\\ListCmsPosts' => Filament\Resources\CmsPosts\Pages\ListCmsPosts::class,
        'App\\Filament\\Resources\\CmsPosts\\Schemas\\CmsPostForm' => Filament\Resources\CmsPosts\Schemas\CmsPostForm::class,
        'App\\Filament\\Resources\\CmsPosts\\Tables\\CmsPostsTable' => Filament\Resources\CmsPosts\Tables\CmsPostsTable::class,

        // Filament Resources - TallcmsContactSubmissions
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\TallcmsContactSubmissionResource' => Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource::class,
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\Pages\\ListTallcmsContactSubmissions' => Filament\Resources\TallcmsContactSubmissions\Pages\ListTallcmsContactSubmissions::class,
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\Pages\\ViewTallcmsContactSubmission' => Filament\Resources\TallcmsContactSubmissions\Pages\ViewTallcmsContactSubmission::class,
        'App\\Filament\\Resources\\TallcmsContactSubmissions\\Tables\\TallcmsContactSubmissionsTable' => Filament\Resources\TallcmsContactSubmissions\Tables\TallcmsContactSubmissionsTable::class,

        // Filament Resources - TallcmsMedia
        'App\\Filament\\Resources\\TallcmsMedia\\TallcmsMediaResource' => Filament\Resources\TallcmsMedia\TallcmsMediaResource::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Pages\\CreateTallcmsMedia' => Filament\Resources\TallcmsMedia\Pages\CreateTallcmsMedia::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Pages\\EditTallcmsMedia' => Filament\Resources\TallcmsMedia\Pages\EditTallcmsMedia::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Pages\\ListTallcmsMedia' => Filament\Resources\TallcmsMedia\Pages\ListTallcmsMedia::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Schemas\\TallcmsMediaForm' => Filament\Resources\TallcmsMedia\Schemas\TallcmsMediaForm::class,
        'App\\Filament\\Resources\\TallcmsMedia\\Tables\\TallcmsMediaTable' => Filament\Resources\TallcmsMedia\Tables\TallcmsMediaTable::class,

        // Filament Resources - TallcmsMenus
        'App\\Filament\\Resources\\TallcmsMenus\\TallcmsMenuResource' => Filament\Resources\TallcmsMenus\TallcmsMenuResource::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Pages\\CreateTallcmsMenu' => Filament\Resources\TallcmsMenus\Pages\CreateTallcmsMenu::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Pages\\EditTallcmsMenu' => Filament\Resources\TallcmsMenus\Pages\EditTallcmsMenu::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Pages\\ListTallcmsMenus' => Filament\Resources\TallcmsMenus\Pages\ListTallcmsMenus::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Schemas\\TallcmsMenuForm' => Filament\Resources\TallcmsMenus\Schemas\TallcmsMenuForm::class,
        'App\\Filament\\Resources\\TallcmsMenus\\Tables\\TallcmsMenusTable' => Filament\Resources\TallcmsMenus\Tables\TallcmsMenusTable::class,

        // Livewire Components
        'App\\Livewire\\RevisionHistory' => Livewire\RevisionHistory::class,

        // Policies
        'App\\Policies\\CmsCategoryPolicy' => Policies\CmsCategoryPolicy::class,
        'App\\Policies\\CmsPagePolicy' => Policies\CmsPagePolicy::class,
        'App\\Policies\\CmsPostPolicy' => Policies\CmsPostPolicy::class,
        'App\\Policies\\TallcmsContactSubmissionPolicy' => Policies\TallcmsContactSubmissionPolicy::class,
        'App\\Policies\\TallcmsMediaPolicy' => Policies\TallcmsMediaPolicy::class,
        'App\\Policies\\TallcmsMenuPolicy' => Policies\TallcmsMenuPolicy::class,

        // Filament Pages
        'App\\Filament\\Pages\\MenuItemsManager' => Filament\Pages\MenuItemsManager::class,
        'App\\Filament\\Pages\\PluginLicenses' => Filament\Pages\PluginLicenses::class,
        'App\\Filament\\Pages\\PluginManager' => Filament\Pages\PluginManager::class,
        'App\\Filament\\Pages\\SiteSettings' => Filament\Pages\SiteSettings::class,
        'App\\Filament\\Pages\\SystemUpdates' => Filament\Pages\SystemUpdates::class,
        'App\\Filament\\Pages\\ThemeManager' => Filament\Pages\ThemeManager::class,
        'App\\Filament\\Pages\\UpdateManual' => Filament\Pages\UpdateManual::class,
        'App\\Filament\\Pages\\UpdateProgress' => Filament\Pages\UpdateProgress::class,

        // Filament Widgets
        'App\\Filament\\Widgets\\MenuOverviewWidget' => Filament\Widgets\MenuOverviewWidget::class,
        'App\\Filament\\Widgets\\PluginUpdatesWidget' => Filament\Widgets\PluginUpdatesWidget::class,

        // Jobs
        'App\\Jobs\\TallCmsUpdateJob' => Jobs\TallCmsUpdateJob::class,
    ];

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

        // Register class aliases for backwards compatibility
        $this->registerClassAliases();

        // Register the PluginServiceProvider and ThemeServiceProvider
        $this->app->register(PluginServiceProvider::class);
        $this->app->register(ThemeServiceProvider::class);

        // Only bind TallCmsUpdater service in standalone mode
        if ($this->isStandaloneMode()) {
            $this->app->singleton(Services\TallCmsUpdater::class);
        }
    }

    /**
     * Register class aliases for backwards compatibility.
     * This allows existing code using App\* namespaces to continue working.
     * Skips aliasing if the original class/interface/trait already exists
     * (e.g., in a monorepo or when app/ classes haven't been removed).
     */
    protected function registerClassAliases(): void
    {
        foreach ($this->classAliases as $alias => $class) {
            // Check if alias target already exists as class, interface, or trait
            // Allow autoloading (no false param) to properly detect existing types
            if (! class_exists($alias) && ! interface_exists($alias) && ! trait_exists($alias)) {
                class_alias($class, $alias);
            }
        }
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Register middleware aliases before loading routes
        $this->registerMiddlewareAliases();

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
     * Register middleware aliases used by package routes.
     */
    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('tallcms.maintenance', Http\Middleware\MaintenanceModeMiddleware::class);
        $router->aliasMiddleware('tallcms.theme-preview', Http\Middleware\ThemePreviewMiddleware::class);
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
        // Standalone mode: routes are defined in the app's routes/web.php
        // using App wrapper classes for full customization.
        // Package routes are NOT loaded to avoid duplication.
        // The app routes use App\Livewire\CmsPageRenderer which extends
        // the package class and can override render() for custom views.
    }

    /**
     * Boot features for plugin mode (installed in existing Filament app)
     */
    protected function bootPluginFeatures(): void
    {
        // Check if TallCmsPlugin is registered (deferred to after app boots)
        $this->app->booted(function () {
            $this->checkPluginRegistration();
        });

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
     * Check if TallCmsPlugin is registered with any Filament panel.
     * Logs a warning if not registered to help plugin mode users.
     */
    protected function checkPluginRegistration(): void
    {
        // Skip check if running in console (artisan commands)
        if ($this->app->runningInConsole()) {
            return;
        }

        // Skip if Filament isn't available
        if (! class_exists(\Filament\Facades\Filament::class)) {
            return;
        }

        try {
            $panels = \Filament\Facades\Filament::getPanels();
            $pluginRegistered = false;

            foreach ($panels as $panel) {
                if ($panel->hasPlugin('tallcms')) {
                    $pluginRegistered = true;
                    break;
                }
            }

            if (! $pluginRegistered) {
                \Illuminate\Support\Facades\Log::warning(
                    'TallCMS: Plugin not registered with any Filament panel. ' .
                    'Add TallCmsPlugin::make() to your panel provider. ' .
                    'Example: $panel->plugin(TallCmsPlugin::make())'
                );
            }
        } catch (\Throwable $e) {
            // Silently ignore - Filament may not be fully booted yet
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
            // Core commands available in all modes
            Console\Commands\CleanExpiredPreviewTokens::class,
            Console\Commands\MakeTallCmsBlock::class,

            // Theme commands
            Console\Commands\MakeTheme::class,
            Console\Commands\ThemeActivate::class,
            Console\Commands\ThemeBuild::class,
            Console\Commands\ThemeCacheClear::class,
            Console\Commands\ThemeInstallCommand::class,
            Console\Commands\ThemeList::class,

            // Plugin commands
            Console\Commands\MakePluginCommand::class,
            Console\Commands\PluginCleanupBackupsCommand::class,
            Console\Commands\PluginInstallCommand::class,
            Console\Commands\PluginListCommand::class,
            Console\Commands\PluginMigrateCommand::class,
            Console\Commands\PluginUninstallCommand::class,
        ];

        // Standalone-only commands (updater, setup, release signing)
        if ($this->isStandaloneMode()) {
            $commands = array_merge($commands, [
                Console\Commands\TallCmsSetup::class,
                Console\Commands\TallCmsUpdate::class,
                Console\Commands\TallCmsVersion::class,
                Console\Commands\TallCmsGenerateKeypair::class,
                Console\Commands\TallCmsGenerateManifest::class,
                Console\Commands\TallCmsSignRelease::class,
                Console\Commands\LicenseTestCommand::class,
            ]);
        }

        return $commands;
    }
}
