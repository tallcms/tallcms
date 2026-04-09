<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusRelationManager extends RelationManager
{
    protected static string $relationship = 'menus';

    protected static ?string $title = 'Menus';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-bars-3';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('location')
                ->required()
                ->maxLength(255)
                ->helperText('e.g. header, footer, sidebar'),
            TextInput::make('description')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

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
                CreateAction::make(),
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
                    ->url(fn ($record) => \TallCms\Cms\Filament\Resources\TallcmsMenus\TallcmsMenuResource::getUrl('edit', ['record' => $record])),

                DeleteAction::make(),
            ]);
    }
}
