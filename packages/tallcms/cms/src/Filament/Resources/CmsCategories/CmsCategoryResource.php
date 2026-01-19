<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories;

use TallCms\Cms\Filament\Resources\CmsCategories\Pages\CreateCmsCategory;
use TallCms\Cms\Filament\Resources\CmsCategories\Pages\EditCmsCategory;
use TallCms\Cms\Filament\Resources\CmsCategories\Pages\ListCmsCategories;
use TallCms\Cms\Filament\Resources\CmsCategories\Schemas\CmsCategoryForm;
use TallCms\Cms\Filament\Resources\CmsCategories\Tables\CmsCategoriesTable;
use TallCms\Cms\Models\CmsCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CmsCategoryResource extends Resource
{
    protected static ?string $model = CmsCategory::class;

    protected static ?string $pluralModelLabel = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return CmsCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsCategoriesTable::configure($table);
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
            'index' => ListCmsCategories::route('/'),
            'create' => CreateCmsCategory::route('/create'),
            'edit' => EditCmsCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Content Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'Categories';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getModel()::count();
        } catch (\Throwable) {
            return null;
        }
    }
}
