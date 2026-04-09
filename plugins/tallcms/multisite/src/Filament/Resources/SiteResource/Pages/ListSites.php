<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
