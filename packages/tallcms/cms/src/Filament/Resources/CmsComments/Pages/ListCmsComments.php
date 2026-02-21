<?php

namespace TallCms\Cms\Filament\Resources\CmsComments\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Filament\Resources\CmsComments\CmsCommentResource;

class ListCmsComments extends ListRecords
{
    protected static string $resource = CmsCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - comments come from the frontend
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getBadgeCount('pending'))
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            'spam' => Tab::make('Spam')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'spam')),
        ];
    }

    protected function getBadgeCount(string $status): ?int
    {
        try {
            $count = $this->getModel()::where('status', $status)->count();

            return $count > 0 ? $count : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
