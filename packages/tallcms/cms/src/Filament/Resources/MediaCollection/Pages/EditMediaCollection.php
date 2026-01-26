<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;

class EditMediaCollection extends EditRecord
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Delete Collection')
                ->modalDescription('Are you sure you want to delete this collection? Media files will not be deleted, only unassigned from this collection.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
