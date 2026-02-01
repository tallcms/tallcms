<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use TallCms\Cms\Filament\Resources\MediaCollection\MediaCollectionResource;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;

class EditMediaCollection extends EditRecord
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewMedia')
                ->label('View Media')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->url(fn () => TallcmsMediaResource::getUrl('index', [
                    'tableFilters' => [
                        'collections' => ['value' => $this->record->id],
                    ],
                    'filters' => [
                        'collections' => ['value' => $this->record->id],
                        'recent' => ['isActive' => false],
                    ],
                ])),
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
