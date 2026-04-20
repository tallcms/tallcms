<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use TallCms\Cms\Filament\Resources\SiteResource\Pages\EditSite;
use TallCms\Cms\Models\Site;

/**
 * Core Site resource.
 *
 * In standalone mode: single record, direct edit, nav label "Site Settings".
 * In multisite mode: the multisite plugin extends this with list page,
 * ownership, domain verification, etc.
 */
class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $modelLabel = 'Site';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.configuration', 'Configuration');
    }

    public static function form(Schema $schema): Schema
    {
        return SiteForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PagesRelationManager::class,
            RelationManagers\MenusRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSite::route('/'),
        ];
    }
}
