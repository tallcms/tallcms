<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection\Pages;

use Filament\Resources\Pages\CreateRecord;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;

class CreateMediaCollection extends CreateRecord
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
