<?php

namespace App\Filament\Widgets;

use App\Services\PluginLicenseService;
use Filament\Widgets\Widget;

class PluginUpdatesWidget extends Widget
{
    protected string $view = 'filament.widgets.plugin-updates';

    protected static ?int $sort = -1; // Show at top

    protected int|string|array $columnSpan = 'full';

    public array $updates = [];

    public function mount(): void
    {
        $this->updates = app(PluginLicenseService::class)->getAvailableUpdates();
    }

    public static function canView(): bool
    {
        // Only show if there are updates available
        return app(PluginLicenseService::class)->hasAvailableUpdates();
    }
}
