<?php

namespace App\Filament\Resources\CmsPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CmsPostsTable
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
                    ->sortable()
                    ->limit(50),
                    
                TextColumn::make('excerpt')
                    ->searchable()
                    ->limit(60)
                    ->toggleable()
                    ->color('gray'),
                    
                SelectColumn::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->selectablePlaceholder(false),
                    
                ToggleColumn::make('is_featured')
                    ->label('Featured'),
                    
                TagsColumn::make('categories.name')
                    ->label('Categories')
                    ->limit(3),
                    
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
                    
                SelectFilter::make('is_featured')
                    ->label('Featured')
                    ->options([
                        '1' => 'Featured',
                        '0' => 'Not Featured',
                    ]),
                    
                SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple(),
                    
                SelectFilter::make('author')
                    ->relationship('author', 'name'),
                    
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