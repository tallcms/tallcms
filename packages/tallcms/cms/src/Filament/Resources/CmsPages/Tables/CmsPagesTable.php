<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Tables;

use TallCms\Cms\Enums\ContentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CmsPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->square()
                    ->size(50),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->limit(30),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::from($state)->getLabel())
                    ->color(fn (string $state): string => ContentStatus::from($state)->getColor())
                    ->icon(fn (string $state): string => ContentStatus::from($state)->getIcon()),

                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->limit(20),

                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ContentStatus::Draft->value => ContentStatus::Draft->getLabel(),
                        ContentStatus::Pending->value => ContentStatus::Pending->getLabel(),
                        ContentStatus::Published->value => ContentStatus::Published->getLabel(),
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
