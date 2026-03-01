<?php

namespace TallCms\Cms\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use TallCms\Cms\Models\Plugin;
use TallCms\Cms\Models\PluginLicense;
use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\PluginManager as PluginManagerService;
use TallCms\Cms\Services\PluginMigrator;
use TallCms\Cms\Services\PluginValidator;

class PluginManager extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static ?string $title = 'Plugin Manager';

    protected string $view = 'tallcms::filament.pages.plugin-manager';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-puzzle-piece';
    }

    public static function getNavigationLabel(): string
    {
        return 'Plugins';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 60;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(PluginLicenseService::class)->getAvailableUpdatesCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = app(PluginLicenseService::class)->getAvailableUpdatesCount();

        return $count > 0 ? "{$count} update(s) available" : null;
    }

    public function mount(): void
    {
        // Trigger automatic update check (rate-limited internally)
        app(PluginLicenseService::class)->checkForUpdatesAutomatically();
    }

    #[Url]
    public string $search = '';

    public ?string $selectedPlugin = null;

    public ?array $pluginDetails = null;

    protected ?array $availableUpdates = null;

    /**
     * Get available updates (cached per request)
     */
    protected function getAvailableUpdates(): array
    {
        if ($this->availableUpdates === null) {
            $this->availableUpdates = app(PluginLicenseService::class)->getAvailableUpdates();
        }

        return $this->availableUpdates;
    }

    /**
     * Get the plugin manager service
     */
    protected function getPluginManager(): PluginManagerService
    {
        return app(PluginManagerService::class);
    }

    /**
     * Get the plugin validator service
     */
    protected function getValidator(): PluginValidator
    {
        return app(PluginValidator::class);
    }

    /**
     * Get the plugin migrator service
     */
    protected function getMigrator(): PluginMigrator
    {
        return app(PluginMigrator::class);
    }

    /**
     * Get all installed plugins with metadata
     */
    #[Computed]
    public function plugins(): Collection
    {
        $availableUpdates = $this->getAvailableUpdates();

        return $this->getPluginManager()->getInstalledPlugins()
            ->map(fn (Plugin $plugin) => [
                'vendor' => $plugin->vendor,
                'slug' => $plugin->slug,
                'fullSlug' => $plugin->getFullSlug(),
                'name' => $plugin->name,
                'description' => $plugin->description,
                'version' => $plugin->version,
                'author' => $plugin->author,
                'tags' => $plugin->tags,
                'hasFilamentPlugin' => $plugin->hasFilamentPlugin(),
                'hasPublicRoutes' => $plugin->hasPublicRoutes(),
                'hasPrefixedRoutes' => $plugin->hasPrefixedRoutes(),
                'hasMigrations' => $plugin->hasMigrations(),
                'meetsRequirements' => $plugin->meetsRequirements(),
                'unmetRequirements' => $plugin->getUnmetRequirements(),
                'hasPendingMigrations' => $this->getMigrator()->hasPendingMigrations($plugin),
                'hasUpdate' => isset($availableUpdates[$plugin->getLicenseSlug()]),
                'updateInfo' => $availableUpdates[$plugin->getLicenseSlug()] ?? null,
                'requiresLicense' => $plugin->requiresLicense(),
                'licenseSlug' => $plugin->getLicenseSlug(),
            ])
            ->values();
    }

    /**
     * Get filtered plugins based on search query
     */
    #[Computed]
    public function filteredPlugins(): Collection
    {
        if (empty($this->search)) {
            return $this->plugins;
        }

        $search = strtolower(trim($this->search));

        return $this->plugins->filter(function ($plugin) use ($search) {
            return str_contains(strtolower($plugin['name']), $search)
                || str_contains(strtolower($plugin['description']), $search)
                || str_contains(strtolower($plugin['fullSlug']), $search)
                || str_contains(strtolower($plugin['author']), $search)
                || collect($plugin['tags'])->contains(fn ($tag) => str_contains(strtolower($tag), $search));
        });
    }

    /**
     * Get available plugins from catalog (excluding installed ones)
     */
    #[Computed]
    public function availablePlugins(): Collection
    {
        $catalog = config('tallcms.plugins.catalog', []);
        $installedSlugs = $this->plugins->pluck('fullSlug')->toArray();

        return collect($catalog)
            ->filter(fn ($plugin, $slug) => ! in_array($slug, $installedSlugs))
            ->map(fn ($plugin, $slug) => array_merge($plugin, ['fullSlug' => $slug]))
            ->values();
    }

    /**
     * Check if plugin uploads/updates are allowed (for Blade access)
     */
    #[Computed]
    public function uploadsAllowed(): bool
    {
        return $this->getPluginManager()->uploadsAllowed();
    }

    /**
     * Show plugin details in modal
     */
    public function showPluginDetails(string $vendor, string $slug): void
    {
        $plugin = $this->getPluginManager()->find($vendor, $slug);

        if (! $plugin) {
            return;
        }

        $this->selectedPlugin = $plugin->getFullSlug();

        $migrationStatus = $this->getMigrator()->getMigrationStatus($plugin);
        $backups = $this->getPluginManager()->getAvailableBackups($vendor, $slug);

        $availableUpdates = $this->getAvailableUpdates();

        $this->pluginDetails = [
            'vendor' => $plugin->vendor,
            'slug' => $plugin->slug,
            'fullSlug' => $plugin->getFullSlug(),
            'name' => $plugin->name,
            'description' => $plugin->description,
            'version' => $plugin->version,
            'author' => $plugin->author,
            'authorUrl' => $plugin->getAuthorUrl(),
            'homepage' => $plugin->getHomepage(),
            'license' => $plugin->getLicense(),
            'namespace' => $plugin->namespace,
            'provider' => $plugin->provider,
            'filamentPlugin' => $plugin->filamentPlugin,
            'tags' => $plugin->tags,
            'compatibility' => $plugin->getCompatibility(),
            'hasFilamentPlugin' => $plugin->hasFilamentPlugin(),
            'hasPublicRoutes' => $plugin->hasPublicRoutes(),
            'publicRoutes' => $plugin->getPublicRoutes(),
            'hasPrefixedRoutes' => $plugin->hasPrefixedRoutes(),
            'hasMigrations' => $plugin->hasMigrations(),
            'migrations' => $migrationStatus,
            'meetsRequirements' => $plugin->meetsRequirements(),
            'unmetRequirements' => $plugin->getUnmetRequirements(),
            'path' => $plugin->path,
            'backups' => $backups,
            'requiresLicense' => $plugin->requiresLicense(),
            'licenseSlug' => $plugin->getLicenseSlug(),
            'hasUpdate' => isset($availableUpdates[$plugin->getLicenseSlug()]),
            'updateInfo' => $availableUpdates[$plugin->getLicenseSlug()] ?? null,
        ];

        $this->dispatch('open-modal', id: 'plugin-details-modal');
    }

    /**
     * Close plugin details modal
     */
    public function closePluginDetails(): void
    {
        $this->selectedPlugin = null;
        $this->pluginDetails = null;
    }

    /**
     * Refresh plugin list
     */
    public function refreshPlugins(): void
    {
        $this->getPluginManager()->refreshCache();

        Notification::make()
            ->title('Plugins refreshed')
            ->body('Plugin list has been refreshed.')
            ->success()
            ->send();

        unset($this->plugins);
    }

    /**
     * Run pending migrations for a plugin
     */
    public function runMigrations(string $vendor, string $slug): void
    {
        $plugin = $this->getPluginManager()->find($vendor, $slug);

        if (! $plugin) {
            Notification::make()
                ->title('Plugin not found')
                ->danger()
                ->send();

            return;
        }

        $result = $this->getMigrator()->migrate($plugin);

        if ($result->success) {
            $count = count($result->migrations);
            Notification::make()
                ->title('Migrations completed')
                ->body("Ran {$count} migration(s) for {$plugin->name}.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Migration failed')
                ->body(implode("\n", $result->errors))
                ->danger()
                ->send();
        }

        unset($this->plugins);
    }

    /**
     * One-click update for a plugin
     */
    public function oneClickUpdate(string $vendor, string $slug): void
    {
        // Guard: respect uploads config for one-click updates too
        if (! $this->getPluginManager()->uploadsAllowed()) {
            Notification::make()
                ->title('Updates disabled')
                ->body('Plugin updates are not enabled in configuration.')
                ->danger()
                ->send();

            return;
        }

        // 1. Find plugin (guard null)
        $plugin = $this->getPluginManager()->find($vendor, $slug);
        if (! $plugin) {
            Notification::make()
                ->title('Plugin not found')
                ->body("Could not find plugin {$vendor}/{$slug}.")
                ->danger()
                ->send();

            return;
        }

        $pluginSlug = $plugin->getLicenseSlug();
        $licenseService = app(PluginLicenseService::class);

        // 2. Call checkForUpdates() which BOTH validates license AND returns fresh download URL
        //    (Anystack always returns the latest version URL - don't use cached URLs)
        $updateCheck = $licenseService->checkForUpdates($pluginSlug);

        // 3. Check if update check succeeded
        if (! ($updateCheck['success'] ?? false)) {
            // Determine if this is a license issue or network error:
            // - purchase_url present = explicit license issue from server
            // - no local license exists = license issue (user never activated)
            // - otherwise = likely network/server error
            $hasLocalLicense = PluginLicense::findByPluginSlug($pluginSlug) !== null;
            $isLicenseIssue = ! empty($updateCheck['purchase_url']) || ! $hasLocalLicense;

            if ($isLicenseIssue) {
                Notification::make()
                    ->title('License Required')
                    ->body($updateCheck['message'] ?? 'A valid license is required to download updates.')
                    ->danger()
                    ->actions([
                        \Filament\Actions\Action::make('manage_license')
                            ->label('Manage License')
                            ->url(tallcms_panel_route('pages.plugin-licenses', ['plugin' => $pluginSlug])),
                    ])
                    ->send();
            } else {
                // Network/server error (has license but check failed without purchase_url signal)
                Notification::make()
                    ->title('Update check failed')
                    ->body($updateCheck['message'] ?? 'Could not check for updates. Please try again.')
                    ->danger()
                    ->send();
            }

            return;
        }

        // 4. Verify update is available and download URL exists
        if (! $updateCheck['update_available']) {
            Notification::make()->title('Already up to date')->success()->send();

            return;
        }

        if (empty($updateCheck['download_url'])) {
            Notification::make()
                ->title('Download unavailable')
                ->body('Could not get download URL. Please try again or download manually.')
                ->warning()
                ->send();

            return;
        }

        // 5. Download ZIP from fresh URL
        $downloadUrl = $updateCheck['download_url'];
        $tempPath = storage_path('app/plugin-downloads/'.$vendor.'-'.$slug.'-'.uniqid().'.zip');

        try {
            File::ensureDirectoryExists(dirname($tempPath));

            Log::info('One-click update: Downloading', [
                'plugin' => "{$vendor}/{$slug}",
                'url' => $downloadUrl,
            ]);

            $response = Http::timeout(60)
                ->withOptions(['allow_redirects' => true])
                ->get($downloadUrl);

            if (! $response->successful()) {
                Log::error('One-click update: Download HTTP error', [
                    'plugin' => "{$vendor}/{$slug}",
                    'status' => $response->status(),
                ]);
                Notification::make()
                    ->title('Download failed')
                    ->body('HTTP status: '.$response->status())
                    ->danger()
                    ->send();

                return;
            }

            $body = $response->body();
            $contentType = $response->header('Content-Type') ?? 'unknown';

            // Validate response is actually a ZIP file (magic bytes: PK)
            if (strlen($body) < 4 || substr($body, 0, 2) !== 'PK') {
                Log::error('One-click update: Invalid ZIP response', [
                    'plugin' => "{$vendor}/{$slug}",
                    'content_type' => $contentType,
                    'body_length' => strlen($body),
                    'body_preview' => substr($body, 0, 200),
                ]);
                Notification::make()
                    ->title('Download failed')
                    ->body('Server returned invalid response (not a ZIP file). Check logs for details.')
                    ->danger()
                    ->send();

                return;
            }

            File::put($tempPath, $body);

            // 6. Apply update
            $result = $this->getPluginManager()->update($tempPath);

            if ($result->success) {
                // Clear update cache so badges refresh
                $licenseService->clearUpdateCache();
                $this->availableUpdates = null;
                unset($this->plugins);
                unset($this->filteredPlugins);

                Notification::make()
                    ->title('Plugin updated!')
                    ->body($result->message)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Update failed')
                    ->body(implode("\n", $result->errors))
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Log::error('One-click update failed', ['plugin' => "{$vendor}/{$slug}", 'error' => $e->getMessage()]);
            Notification::make()
                ->title('Update failed')
                ->body('An error occurred: '.$e->getMessage())
                ->danger()
                ->send();
        } finally {
            // Clean up temp file
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }
        }
    }

    /**
     * Apply update action with confirmation modal
     */
    public function applyUpdateAction(): Action
    {
        return Action::make('applyUpdate')
            ->label('Update')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Update Plugin')
            ->modalDescription(fn (array $arguments) => "Update '{$arguments['name']}' to v{$arguments['latest_version']}? A backup will be created.")
            ->action(fn (array $arguments) => $this->oneClickUpdate($arguments['vendor'], $arguments['slug']));
    }

    /**
     * Uninstall action with Filament confirmation modal
     */
    public function uninstallAction(): Action
    {
        return Action::make('uninstall')
            ->label('Uninstall')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Uninstall Plugin')
            ->modalDescription(fn (array $arguments) => "Are you sure you want to uninstall '{$arguments['name']}'? This will rollback all migrations and remove the plugin files. This action cannot be undone.")
            ->modalSubmitActionLabel('Yes, Uninstall')
            ->action(function (array $arguments) {
                $vendor = $arguments['vendor'];
                $slug = $arguments['slug'];

                $result = $this->getPluginManager()->uninstall($vendor, $slug);

                if ($result->success) {
                    Notification::make()
                        ->title('Plugin uninstalled')
                        ->body("'{$arguments['name']}' has been removed.")
                        ->success()
                        ->send();

                    $this->closePluginDetails();
                    unset($this->plugins);
                } else {
                    Notification::make()
                        ->title('Uninstall failed')
                        ->body(implode("\n", $result->errors))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Rollback action with Filament confirmation modal
     */
    public function rollbackAction(): Action
    {
        return Action::make('rollback')
            ->label('Rollback')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Rollback Plugin')
            ->modalDescription(fn (array $arguments) => "Are you sure you want to rollback '{$arguments['name']}' to version {$arguments['version']}?")
            ->modalSubmitActionLabel('Yes, Rollback')
            ->action(function (array $arguments) {
                $vendor = $arguments['vendor'];
                $slug = $arguments['slug'];
                $version = $arguments['version'];

                $result = $this->getPluginManager()->rollback($vendor, $slug, $version);

                if ($result->success) {
                    Notification::make()
                        ->title('Rollback successful')
                        ->body($result->message)
                        ->success()
                        ->send();

                    $this->closePluginDetails();
                    unset($this->plugins);
                } else {
                    Notification::make()
                        ->title('Rollback failed')
                        ->body(implode("\n", $result->errors))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkUpdates')
                ->label('Check for Updates')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $licenseService = app(PluginLicenseService::class);
                    $licenseService->clearUpdateCache();

                    // Use checkAllForUpdates() to get detailed results including failures
                    $results = $licenseService->checkAllForUpdates();

                    // Build updates cache and count results
                    $updates = [];
                    $failedChecks = [];

                    foreach ($results as $pluginSlug => $result) {
                        if ($result['success'] ?? false) {
                            if ($result['update_available'] ?? false) {
                                $updates[$pluginSlug] = [
                                    'plugin_name' => $result['plugin_name'] ?? $pluginSlug,
                                    'current_version' => $result['current_version'] ?? '0.0.0',
                                    'latest_version' => $result['latest_version'],
                                    'download_url' => $result['download_url'] ?? null,
                                    'changelog_url' => $result['changelog_url'] ?? null,
                                ];
                            }
                        } else {
                            // Determine if this is a license issue or network error:
                            // - purchase_url present = explicit license issue from server/config
                            // - no local license exists = license issue (user never activated)
                            // - otherwise = likely network/server error
                            $hasLocalLicense = PluginLicense::findByPluginSlug($pluginSlug) !== null;
                            $isLicenseIssue = ! empty($result['purchase_url']) || ! $hasLocalLicense;

                            if (! $isLicenseIssue) {
                                $failedChecks[] = $result['plugin_name'] ?? $pluginSlug;
                            }
                        }
                    }

                    // Repopulate cache so badges update correctly and reset check interval
                    $checkInterval = config('tallcms.plugins.license.update_check_interval', 86400);
                    \Illuminate\Support\Facades\Cache::put('plugin_available_updates', $updates, $checkInterval);
                    \Illuminate\Support\Facades\Cache::put('plugin_updates_last_check', now(), $checkInterval);

                    // Reset all cached/computed properties to force refresh
                    $this->availableUpdates = null;
                    unset($this->plugins);
                    unset($this->filteredPlugins);

                    // Show appropriate notification
                    $updateCount = count($updates);
                    if (! empty($failedChecks)) {
                        Notification::make()
                            ->title('Update check completed with errors')
                            ->body($updateCount > 0
                                ? "{$updateCount} update(s) found. Could not check: ".implode(', ', $failedChecks)
                                : 'Could not check: '.implode(', ', $failedChecks))
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title($updateCount > 0 ? "{$updateCount} update(s) available" : 'All plugins up to date')
                            ->success()
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshPlugins()),

            // Combined Install/Update Plugin action
            Action::make('install')
                ->label('Install / Update Plugin')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => $this->getPluginManager()->uploadsAllowed())
                ->form([
                    FileUpload::make('plugin_zip')
                        ->label('Plugin Package (ZIP)')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(50 * 1024) // 50MB
                        ->required()
                        ->disk('local')
                        ->directory('plugin-uploads')
                        ->helperText('Upload a plugin package. Auto-detects new install vs update.'),
                ])
                ->action(function (array $data) {
                    // Server-side guard
                    if (! $this->getPluginManager()->uploadsAllowed()) {
                        Notification::make()
                            ->title('Uploads disabled')
                            ->body('Plugin uploads are not enabled in configuration.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $uploadedFile = $data['plugin_zip'];
                    $zipPath = Storage::disk('local')->path($uploadedFile);

                    try {
                        // Validate ZIP and extract plugin info
                        $validationResult = $this->getValidator()->validateZip($zipPath);

                        if (! $validationResult->isValid) {
                            Notification::make()
                                ->title('Invalid plugin package')
                                ->body(implode("\n", $validationResult->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        $pluginInfo = $validationResult->pluginData;
                        $isUpdate = $this->getPluginManager()->isInstalled($pluginInfo['vendor'], $pluginInfo['slug']);

                        if ($isUpdate) {
                            $result = $this->getPluginManager()->update($zipPath);
                            $actionVerb = 'updated';
                        } else {
                            $result = $this->getPluginManager()->installFromZip($zipPath);
                            $actionVerb = 'installed';
                        }

                        if ($result->success) {
                            // Show warnings if any
                            foreach ($result->warnings as $warning) {
                                Notification::make()
                                    ->title('Warning')
                                    ->body($warning)
                                    ->warning()
                                    ->send();
                            }

                            $migrationCount = count($result->migrations);
                            $migrationMsg = $migrationCount > 0 ? " ({$migrationCount} migration(s) ran)" : '';

                            Notification::make()
                                ->title("Plugin {$actionVerb}")
                                ->body("'{$result->plugin->name}' v{$result->plugin->version} has been {$actionVerb}.{$migrationMsg}")
                                ->success()
                                ->send();

                            // Clear update cache so badges refresh (especially for manual updates)
                            if ($isUpdate) {
                                app(PluginLicenseService::class)->clearUpdateCache();
                                $this->availableUpdates = null;
                            }

                            unset($this->plugins);
                            unset($this->filteredPlugins);
                        } else {
                            Notification::make()
                                ->title($isUpdate ? 'Update failed' : 'Installation failed')
                                ->body(implode("\n", $result->errors))
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Log::error('Plugin upload failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Upload failed')
                            ->body('An unexpected error occurred: '.$e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        Storage::disk('local')->delete($uploadedFile);
                    }
                }),
        ];
    }
}
