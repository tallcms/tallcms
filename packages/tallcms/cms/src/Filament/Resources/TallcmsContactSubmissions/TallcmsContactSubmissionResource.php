<?php

namespace TallCms\Cms\Filament\Resources\TallcmsContactSubmissions;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Filament\Resources\Concerns\ScopesQueryToOwnedSites;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Pages\ListTallcmsContactSubmissions;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Pages\ViewTallcmsContactSubmission;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Tables\TallcmsContactSubmissionsTable;
use TallCms\Cms\Models\TallcmsContactSubmission;

class TallcmsContactSubmissionResource extends Resource
{
    use ScopesQueryToOwnedSites;

    protected static ?string $model = TallcmsContactSubmission::class;

    protected static ?string $modelLabel = 'Contact Submission';

    protected static ?string $pluralModelLabel = 'Contact Submissions';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public static function getNavigationLabel(): string
    {
        return 'Contact Submissions';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.content', 'Content');
    }

    public static function getNavigationSort(): ?int
    {
        return 17;
    }

    public static function table(Table $table): Table
    {
        return TallcmsContactSubmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTallcmsContactSubmissions::route('/'),
            'view' => ViewTallcmsContactSubmission::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeQueryToOwnedSites(parent::getEloquentQuery());
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::scopeQueryToOwnedSites(static::getModel()::unread())->count();

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
