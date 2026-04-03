<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
