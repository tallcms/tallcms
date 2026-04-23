<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\CreateTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\EditTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Pages\ListTallcmsMedia;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Schemas\TallcmsMediaForm;
use TallCms\Cms\Filament\Resources\TallcmsMedia\Tables\TallcmsMediaTable;
use TallCms\Cms\Models\TallcmsMedia;

class TallcmsMediaResource extends Resource
{
    protected static ?string $model = TallcmsMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return config('tallcms.labels.media.navigation', 'Media Library');
    }

    public static function getModelLabel(): string
    {
        return config('tallcms.labels.media.singular', 'Media File');
    }

    public static function getPluralModelLabel(): string
    {
        return config('tallcms.labels.media.plural', 'Media Files');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.content', 'Content');
    }

    public static function getNavigationSort(): ?int
    {
        return 13;
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // User-owned: non-super-admins see only their own media
        if (auth()->check() && ! auth()->user()->hasRole('super_admin')
            && \Illuminate\Support\Facades\Schema::hasColumn('tallcms_media', 'user_id')) {
            $query->where('tallcms_media.user_id', auth()->id());
        }

        return $query;
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
