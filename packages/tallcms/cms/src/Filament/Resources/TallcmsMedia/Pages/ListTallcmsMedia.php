<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Pages;

use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTallcmsMedia extends ListRecords
{
    protected static string $resource = TallcmsMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
