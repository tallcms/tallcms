<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use TallCms\Cms\Models\TallcmsMedia;

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
                    ->getStateUsing(fn ($record) => $record->is_image ? $record->url : null)
                    ->defaultImageUrl("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z' /%3E%3C/svg%3E")
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
                    ->color(fn ($state, $record) => self::getCollectionColor($record))
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
            ->filters(self::getFilters())
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Delete Media File')
                    ->modalDescription('Are you sure you want to delete this media file? This action cannot be undone and the file will be permanently removed from storage.')
                    ->modalSubmitActionLabel('Delete File'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('update_alt_text')
                        ->label('Update Alt Text')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            TextInput::make('alt_text')
                                ->label('Alt Text')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Will be applied to all selected images'),
                        ])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['alt_text' => $data['alt_text']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get the color for collection badges based on first collection's color.
     */
    protected static function getCollectionColor(TallcmsMedia $record): ?string
    {
        $firstCollection = $record->collections->first();

        if ($firstCollection && $firstCollection->color) {
            return $firstCollection->color;
        }

        return 'primary';
    }

    /**
     * Get table filters.
     */
    protected static function getFilters(): array
    {
        $filters = [];

        // Collection filter - always shown for filtering by collection
        $filters[] = SelectFilter::make('collections')
            ->label('Collection')
            ->relationship('collections', 'name')
            ->searchable()
            ->preload();

        $filters[] = SelectFilter::make('mime_type')
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
            });

        $filters[] = TernaryFilter::make('has_alt_text')
            ->label('Alt Text')
            ->placeholder('All')
            ->trueLabel('Has alt text')
            ->falseLabel('Missing alt text')
            ->queries(
                true: fn ($query) => $query->whereNotNull('alt_text')->where('alt_text', '!=', ''),
                false: fn ($query) => $query->where(fn ($q) => $q->whereNull('alt_text')->orWhere('alt_text', '')),
            );

        $filters[] = TernaryFilter::make('has_variants')
            ->label('Optimized')
            ->placeholder('All')
            ->trueLabel('Optimized')
            ->falseLabel('Unoptimized')
            ->queries(
                true: fn ($query) => $query->where('has_variants', true),
                false: fn ($query) => $query->where('has_variants', false)->where('mime_type', 'like', 'image/%'),
            );

        $filters[] = Filter::make('recent')
            ->label('Recently Uploaded')
            ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->toggle();

        return $filters;
    }
}
