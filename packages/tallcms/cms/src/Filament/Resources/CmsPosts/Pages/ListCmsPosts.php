<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Pages;

use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsPosts extends ListRecords
{
    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Post'),
        ];
    }
}
