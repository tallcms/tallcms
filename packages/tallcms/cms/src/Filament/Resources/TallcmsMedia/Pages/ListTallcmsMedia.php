<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Models\MediaCollection;
use TallCms\Cms\Models\TallcmsMedia;

class ListTallcmsMedia extends ListRecords
{
    protected static string $resource = TallcmsMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Media')
                ->badge(TallcmsMedia::count())
                ->badgeColor('gray'),

            'unassigned' => Tab::make('Unassigned')
                ->badge(TallcmsMedia::doesntHave('collections')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('collections')),

            'images' => Tab::make('Images')
                ->icon('heroicon-o-photo')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('mime_type', 'like', 'image/%')),

            'videos' => Tab::make('Videos')
                ->icon('heroicon-o-video-camera')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('mime_type', 'like', 'video/%')),

            'documents' => Tab::make('Documents')
                ->icon('heroicon-o-document')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('mime_type', 'like', 'application/%')),
        ];
    }
}
