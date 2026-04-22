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
                    ->action(function () {
                        // Set admin context to this site before redirecting
                        $siteId = $this->getOwnerRecord()->id;
                        session(['multisite_admin_site_id' => $siteId]);

                        $this->redirect(TallcmsMenuResource::getUrl('create'));
                    }),
            ])
            ->recordActions([
                Action::make('manage_items')
                    ->label('Manage Items')
                    ->icon('heroicon-o-bars-3')
                    ->color('primary')
                    ->action(function ($record) {
                        // Switch admin context to this site before navigating so
                        // SiteScope doesn't filter out the menu or its pages.
                        session(['multisite_admin_site_id' => $this->getOwnerRecord()->id]);

                        $this->redirect(\TallCms\Cms\Filament\Pages\MenuItemsManager::getUrl(['activeTab' => $record->id]));
                    }),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->action(function ($record) {
                        // Switch admin context to this site before navigating so
                        // SiteScope doesn't 404 the menu during route-model-binding.
                        session(['multisite_admin_site_id' => $this->getOwnerRecord()->id]);

                        $this->redirect(TallcmsMenuResource::getUrl('edit', ['record' => $record]));
                    }),
            ]);
    }
}
