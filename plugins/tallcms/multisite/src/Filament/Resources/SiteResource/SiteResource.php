<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
        return config('tallcms.navigation.groups.platform', 'Platform');
    }

    public static function form(Schema $schema): Schema
    {
        return SiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Non-super-admins see only their owned sites
        if (auth()->check() && ! auth()->user()->hasRole('super_admin')) {
            $query->where('user_id', auth()->id());
        }

        return $query
            ->addSelect([
                'last_page_activity' => DB::table('tallcms_pages')
                    ->whereColumn('tallcms_pages.site_id', 'tallcms_sites.id')
                    ->whereNull('tallcms_pages.deleted_at')
                    ->selectRaw('MAX(tallcms_pages.updated_at)')
                    ->limit(1),
                'last_menu_activity' => DB::table('tallcms_menus')
                    ->whereColumn('tallcms_menus.site_id', 'tallcms_sites.id')
                    ->selectRaw('MAX(tallcms_menus.updated_at)')
                    ->limit(1),
                'last_menu_item_activity' => DB::table('tallcms_menu_items')
                    ->join('tallcms_menus', 'tallcms_menu_items.menu_id', '=', 'tallcms_menus.id')
                    ->whereColumn('tallcms_menus.site_id', 'tallcms_sites.id')
                    ->selectRaw('MAX(tallcms_menu_items.updated_at)')
                    ->limit(1),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PagesRelationManager::class,
            RelationManagers\MenusRelationManager::class,
            RelationManagers\SettingOverridesRelationManager::class,
        ];
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
