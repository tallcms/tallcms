<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource;

class MenusRelationManager extends RelationManager
{
    protected static string $relationship = 'menus';

    protected static ?string $title = 'Menus';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-bars-3';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->state(fn ($record) => $record->allItems()->count())
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('create_menu')
                    ->label('Create Menu')
                    ->icon('heroicon-m-plus')
                    // The ?site=<id> query param tells CreateTallcmsMenu which site
                    // the new menu belongs to, so site_id is set explicitly on save.
                    ->url(fn () => TallcmsMenuResource::getUrl('create', [
                        'site' => $this->getOwnerRecord()->id,
                    ])),
            ])
            ->recordActions([
                Action::make('manage_items')
                    ->label('Manage Items')
                    ->icon('heroicon-o-bars-3')
                    ->color('primary')
                    ->url(fn ($record) => \TallCms\Cms\Filament\Pages\MenuItemsManager::getUrl(['activeTab' => $record->id])),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => TallcmsMenuResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
