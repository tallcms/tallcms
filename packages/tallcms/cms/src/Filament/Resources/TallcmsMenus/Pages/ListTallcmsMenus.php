<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMenus\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;

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
