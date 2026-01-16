<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMenus;

use TallCms\Cms\Filament\Resources\TallcmsMenus\Pages\CreateTallcmsMenu;
use TallCms\Cms\Filament\Resources\TallcmsMenus\Pages\EditTallcmsMenu;
use TallCms\Cms\Filament\Resources\TallcmsMenus\Pages\ListTallcmsMenus;
use TallCms\Cms\Filament\Resources\TallcmsMenus\Schemas\TallcmsMenuForm;
use TallCms\Cms\Filament\Resources\TallcmsMenus\Tables\TallcmsMenusTable;
use TallCms\Cms\Models\TallcmsMenu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TallcmsMenuResource extends Resource
{
    protected static ?string $model = TallcmsMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $pluralModelLabel = 'Menus';

    public static function form(Schema $schema): Schema
    {
        return TallcmsMenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TallcmsMenusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Removed MenuItemsRelationManager for cleaner UX
            // Menu items are managed via integrated actions
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTallcmsMenus::route('/'),
            'create' => CreateTallcmsMenu::route('/create'),
            'edit' => EditTallcmsMenu::route('/{record}/edit'),
        ];
    }
}
