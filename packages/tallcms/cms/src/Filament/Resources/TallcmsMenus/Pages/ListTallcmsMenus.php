<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMenus\Pages;

use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTallcmsMenus extends ListRecords
{
    protected static string $resource = TallcmsMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
