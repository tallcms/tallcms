<?php

namespace TallCms\Cms\Filament\Resources\MediaCollection;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TallCms\Cms\Filament\Resources\MediaCollection\Pages\CreateMediaCollection;
use TallCms\Cms\Filament\Resources\MediaCollection\Pages\EditMediaCollection;
use TallCms\Cms\Filament\Resources\MediaCollection\Pages\ListMediaCollections;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Models\MediaCollection;

class MediaCollectionResource extends Resource
{
    protected static ?string $model = MediaCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Collections';

    protected static ?string $modelLabel = 'Collection';

    protected static ?string $pluralModelLabel = 'Collections';

    protected static ?string $navigationParentItem = 'Media Library';

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Content Management';
    }

    public static function getNavigationSort(): ?int
    {
        return (config('tallcms.filament.navigation_sort') ?? 4) + 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Collection Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->autofocus(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Leave empty to auto-generate from name'),

                ColorPicker::make('color')
                    ->label('Color')
                    ->helperText('Used for badges and visual identification'),

                Textarea::make('description')
                    ->label('Description')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width(40),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('media_count')
                    ->label('Media')
                    ->counts('media')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->placeholder('No description')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Delete Collection')
                    ->modalDescription('Are you sure you want to delete this collection? Media files will not be deleted, only unassigned from this collection.'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->recordUrl(fn (MediaCollection $record) => TallcmsMediaResource::getUrl('index', [
                'tableFilters' => [
                    'collections' => ['value' => $record->id],
                ],
            ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaCollections::route('/'),
            'create' => CreateMediaCollection::route('/create'),
            'edit' => EditMediaCollection::route('/{record}/edit'),
        ];
    }
}
