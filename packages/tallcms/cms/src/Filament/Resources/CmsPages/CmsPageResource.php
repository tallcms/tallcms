<?php

namespace TallCms\Cms\Filament\Resources\CmsPages;

use TallCms\Cms\Filament\Resources\CmsPages\Pages\CreateCmsPage;
use TallCms\Cms\Filament\Resources\CmsPages\Pages\EditCmsPage;
use TallCms\Cms\Filament\Resources\CmsPages\Pages\ListCmsPages;
use TallCms\Cms\Filament\Resources\CmsPages\Schemas\CmsPageForm;
use TallCms\Cms\Filament\Resources\CmsPages\Tables\CmsPagesTable;
use TallCms\Cms\Models\CmsPage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static ?string $pluralModelLabel = 'Pages';

    public static function form(Schema $schema): Schema
    {
        return CmsPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsPagesTable::configure($table);
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
            'index' => ListCmsPages::route('/'),
            'create' => CreateCmsPage::route('/create'),
            'edit' => EditCmsPage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Content Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pages';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
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
