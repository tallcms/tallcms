<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPosts\Pages\CreateCmsPost;
use TallCms\Cms\Filament\Resources\CmsPosts\Pages\EditCmsPost;
use TallCms\Cms\Filament\Resources\CmsPosts\Pages\ListCmsPosts;
use TallCms\Cms\Filament\Resources\CmsPosts\Schemas\CmsPostForm;
use TallCms\Cms\Filament\Resources\CmsPosts\Tables\CmsPostsTable;
use TallCms\Cms\Models\CmsPost;

class CmsPostResource extends Resource
{
    use Translatable;
    protected static ?string $model = CmsPost::class;

    protected static ?string $pluralModelLabel = 'Posts';

    public static function form(Schema $schema): Schema
    {
        return CmsPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsPostsTable::configure($table);
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
            'index' => ListCmsPosts::route('/'),
            'create' => CreateCmsPost::route('/create'),
            'edit' => EditCmsPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Content Management';
    }

    public static function getNavigationLabel(): string
    {
        return 'Posts';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 2;
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
