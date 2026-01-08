<?php

namespace Tallcms\Pro\Filament\Widgets;

use Filament\Widgets\Widget;
use Tallcms\Pro\Services\LicenseService;

class LicenseStatusWidget extends Widget
{
    protected string $view = 'tallcms-pro::filament.widgets.license-status';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public function getStatus(): array
    {
        return app(LicenseService::class)->getStatus();
    }

    public static function canView(): bool
    {
        // Always show on dashboard
        return true;
    }
}
