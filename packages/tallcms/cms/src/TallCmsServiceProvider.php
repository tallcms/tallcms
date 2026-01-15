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
