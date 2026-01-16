<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMenus\Pages;

use TallCms\Cms\Filament\Pages\MenuItemsManager;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTallcmsMenu extends EditRecord
{
    protected static string $resource = TallcmsMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_items')
                ->label('Manage Menu Items')
                ->icon('heroicon-o-bars-3')
                ->color('primary')
                ->url(fn (): string => MenuItemsManager::getUrl(['activeTab' => $this->getRecord()->id]))
                ->openUrlInNewTab(false),

            DeleteAction::make(),
        ];
    }
}
