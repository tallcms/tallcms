<?php

namespace TallCms\Cms\Filament\Resources\CmsComments;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TallCms\Cms\Filament\Resources\CmsComments\Pages\ListCmsComments;
use TallCms\Cms\Filament\Resources\CmsComments\Pages\ViewCmsComment;
use TallCms\Cms\Filament\Resources\CmsComments\Tables\CmsCommentsTable;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Models\CmsComment;

class CmsCommentResource extends Resource
{
    use ScopesQueryToOwnedSites;

    protected static ?string $model = CmsComment::class;

    protected static ?string $modelLabel = 'Comment';

    protected static ?string $pluralModelLabel = 'Comments';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationLabel(): string
    {
        return 'Comments';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.content', 'Content');
    }

    public static function getNavigationSort(): ?int
    {
        return 16;
    }

    public static function table(Table $table): Table
    {
        return CmsCommentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsComments::route('/'),
            'view' => ViewCmsComment::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return static::scopeQueryToOwnedSites($query);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::scopeQueryToOwnedSites(static::getModel()::pending())->count();

            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
