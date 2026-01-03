<?php

namespace App\Filament\Resources\CmsPosts;

use App\Filament\Resources\CmsPosts\Pages\CreateCmsPost;
use App\Filament\Resources\CmsPosts\Pages\EditCmsPost;
use App\Filament\Resources\CmsPosts\Pages\ListCmsPosts;
use App\Filament\Resources\CmsPosts\Schemas\CmsPostForm;
use App\Filament\Resources\CmsPosts\Tables\CmsPostsTable;
use App\Models\CmsPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CmsPostResource extends Resource
{
    protected static ?string $model = CmsPost::class;


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
        return 'Content Management';
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Posts';
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
        return static::getModel()::count();
    }
}
