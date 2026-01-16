<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Pages;

use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsPages extends ListRecords
{
    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Page'),
        ];
    }
}
