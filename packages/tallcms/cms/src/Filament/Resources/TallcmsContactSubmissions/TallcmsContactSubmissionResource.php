<?php

namespace TallCms\Cms\Filament\Resources\TallcmsContactSubmissions;

use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Pages\ListTallcmsContactSubmissions;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Pages\ViewTallcmsContactSubmission;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Tables\TallcmsContactSubmissionsTable;
use TallCms\Cms\Models\TallcmsContactSubmission;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class TallcmsContactSubmissionResource extends Resource
{
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
        return config('tallcms.filament.navigation_group') ?? 'Content Management';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 50;
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

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::unread()->count();

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
