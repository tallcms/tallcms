<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia;

use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\CreateTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\EditTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\ListTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Schemas\TallcmsMediaForm;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Tables\TallcmsMediaTable;
use TallCms\Cms\Models\TallcmsMedia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TallcmsMediaResource extends Resource
{
    protected static ?string $model = TallcmsMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $modelLabel = 'Media File';

    protected static ?string $pluralModelLabel = 'Media Files';

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Content Management';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 4;
    }

    public static function form(Schema $schema): Schema
    {
        return TallcmsMediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TallcmsMediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTallcmsMedia::route('/'),
            'create' => CreateTallcmsMedia::route('/create'),
            'edit' => EditTallcmsMedia::route('/{record}/edit'),
        ];
    }
}
