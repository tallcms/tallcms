<?php

namespace TallCms\Cms\Filament\Widgets;

use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\PluginManager;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class PluginUpdatesWidget extends Widget
{
    protected string $view = 'tallcms::filament.widgets.plugin-updates';

    protected static ?int $sort = -1; // Show at top

    protected int|string|array $columnSpan = 'full';

    public array $updates = [];

    public bool $isChecking = false;

    public function mount(): void
    {
        $this->updates = app(PluginLicenseService::class)->getAvailableUpdates();
    }

    public function refresh(): void
    {
        $this->isChecking = true;

        // Clear all caches and re-check
        $licenseService = app(PluginLicenseService::class);
        app(PluginManager::class)->refreshCache();
        $licenseService->clearUpdateCache();

        // Force fresh check
        $licenseService->checkForUpdatesAutomatically();

        // Reload updates
        $this->updates = $licenseService->getAvailableUpdates();

        $this->isChecking = false;

        if (empty($this->updates)) {
            Notification::make()
                ->title('All plugins are up to date')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Update check complete')
                ->body(count($this->updates) . ' update(s) available')
                ->info()
                ->send();
        }
    }

    public static function canView(): bool
    {
        // Only show if there are updates available
        return app(PluginLicenseService::class)->hasAvailableUpdates();
    }
}
