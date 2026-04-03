<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Tallcms\Multisite\Filament\Resources\SiteResource\Pages\CreateSite;
use Tallcms\Multisite\Filament\Resources\SiteResource\Pages\EditSite;
use Tallcms\Multisite\Filament\Resources\SiteResource\Pages\ListSites;
use Tallcms\Multisite\Models\Site;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Sites';

    protected static ?string $modelLabel = 'Site';

    protected static ?string $pluralModelLabel = 'Sites';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Multisite';
    }

    public static function form(Schema $schema): Schema
    {
        return SiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSites::route('/'),
            'create' => CreateSite::route('/create'),
            'edit' => EditSite::route('/{record}/edit'),
        ];
    }
}
