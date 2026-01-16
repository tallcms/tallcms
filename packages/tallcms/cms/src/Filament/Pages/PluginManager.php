<?php

namespace TallCms\Cms\Filament\Pages;

use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Models\Plugin;
use TallCms\Cms\Services\PluginManager as PluginManagerService;
use TallCms\Cms\Services\PluginMigrator;
use TallCms\Cms\Services\PluginValidator;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

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
        return 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return 60;
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

    public ?string $selectedPlugin = null;

    public ?array $pluginDetails = null;

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
            ])
            ->values();
    }

    /**
     * Get available plugins from catalog (excluding installed ones)
     */
    #[Computed]
    public function availablePlugins(): Collection
    {
        $catalog = config('plugin.catalog', []);
        $installedSlugs = $this->plugins->pluck('fullSlug')->toArray();

        return collect($catalog)
            ->filter(fn ($plugin, $slug) => ! in_array($slug, $installedSlugs))
            ->map(fn ($plugin, $slug) => array_merge($plugin, ['fullSlug' => $slug]))
            ->values();
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
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshPlugins()),

            // Plugin Upload action
            Action::make('upload')
                ->label('Upload Plugin')
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
                        ->helperText('Upload a plugin package (.zip file). Maximum size: 50MB.'),
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
                        $result = $this->getPluginManager()->installFromZip($zipPath);

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
                                ->title('Plugin installed')
                                ->body("'{$result->plugin->name}' v{$result->plugin->version} has been installed.{$migrationMsg}")
                                ->success()
                                ->send();

                            unset($this->plugins);
                        } else {
                            Notification::make()
                                ->title('Installation failed')
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

            // Plugin Update action
            Action::make('update')
                ->label('Update Plugin')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('info')
                ->visible(fn () => $this->getPluginManager()->uploadsAllowed())
                ->form([
                    FileUpload::make('plugin_zip')
                        ->label('Plugin Package (ZIP)')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(50 * 1024) // 50MB
                        ->required()
                        ->disk('local')
                        ->directory('plugin-uploads')
                        ->helperText('Upload a newer version of an existing plugin.'),
                ])
                ->action(function (array $data) {
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
                        $result = $this->getPluginManager()->update($zipPath);

                        if ($result->success) {
                            foreach ($result->warnings as $warning) {
                                Notification::make()
                                    ->title('Warning')
                                    ->body($warning)
                                    ->warning()
                                    ->send();
                            }

                            Notification::make()
                                ->title('Plugin updated')
                                ->body($result->message)
                                ->success()
                                ->send();

                            unset($this->plugins);
                        } else {
                            Notification::make()
                                ->title('Update failed')
                                ->body(implode("\n", $result->errors))
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Log::error('Plugin update failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Update failed')
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
