<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories\Pages;

use TallCms\Cms\Filament\Resources\CmsCategories\CmsCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsCategories extends ListRecords
{
    protected static string $resource = CmsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Category'),
        ];
    }
}
