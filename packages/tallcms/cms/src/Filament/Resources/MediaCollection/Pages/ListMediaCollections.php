<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;

class ListMediaCollections extends ListRecords
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
