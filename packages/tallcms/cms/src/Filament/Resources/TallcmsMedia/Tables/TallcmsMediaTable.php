<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TallcmsMediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('url')
                    ->label('Preview')
                    ->height(50)
                    ->width(50)
                    ->defaultImageUrl('/images/file-placeholder.png')
                    ->visibleFrom('sm'),

                TextColumn::make('name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                TextColumn::make('collections.name')
                    ->label('Collections')
                    ->badge()
                    ->separator(', ')
                    ->limit(2)
                    ->searchable()
                    ->placeholder('No collections'),

                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'image/') => 'success',
                        str_starts_with($state, 'video/') => 'info',
                        str_starts_with($state, 'audio/') => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper(explode('/', $state)[0])),

                TextColumn::make('human_size')
                    ->label('Size')
                    ->sortable(['size'])
                    ->alignEnd(),

                TextColumn::make('dimensions')
                    ->label('Dimensions')
                    ->placeholder('N/A')
                    ->visibleFrom('lg'),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('collections')
                    ->label('Collection')
                    ->relationship('collections', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('mime_type')
                    ->label('File Type')
                    ->options([
                        'image/' => 'Images',
                        'video/' => 'Videos',
                        'audio/' => 'Audio',
                        'application/pdf' => 'PDFs',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            return $query->where('mime_type', 'like', $data['value'].'%');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Delete Media File')
                    ->modalDescription('Are you sure you want to delete this media file? This action cannot be undone and the file will be permanently removed from storage.')
                    ->modalSubmitActionLabel('Delete File'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
