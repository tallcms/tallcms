<?php

namespace App\Filament\Resources\TallcmsMedia;

use App\Filament\Resources\TallcmsMedia\Pages\CreateTallcmsMedia;
use App\Filament\Resources\TallcmsMedia\Pages\EditTallcmsMedia;
use App\Filament\Resources\TallcmsMedia\Pages\ListTallcmsMedia;
use App\Filament\Resources\TallcmsMedia\Schemas\TallcmsMediaForm;
use App\Filament\Resources\TallcmsMedia\Tables\TallcmsMediaTable;
use App\Models\TallcmsMedia;
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
