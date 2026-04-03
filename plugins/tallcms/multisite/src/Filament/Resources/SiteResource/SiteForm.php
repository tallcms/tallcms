<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use TallCms\Cms\Models\Theme;
use TallCms\Cms\Services\LocaleRegistry;
use Tallcms\Multisite\Models\Site;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Site Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'tallcms_sites', column: 'domain', ignoreRecord: true)
                            ->helperText('e.g. example.com — lowercase, no protocol or port')
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Site::normalizeDomain($state) : $state),

                        Select::make('locale')
                            ->options(fn () => static::getLocaleOptions())
                            ->placeholder('Use global locale')
                            ->searchable()
                            ->nullable(),

                        Placeholder::make('theme_display')
                            ->label('Theme')
                            ->content(function (?Site $record) {
                                $themeSlug = $record?->theme;
                                $themeModel = $themeSlug ? Theme::find($themeSlug) : null;
                                $themeName = $themeModel?->name ?? ($themeSlug ? ucfirst($themeSlug) : 'Global default');
                                $manageUrl = url(config('tallcms.filament.panel_path', 'admin').'/theme-manager');

                                return new HtmlString(
                                    "{$themeName} &middot; <a href=\"{$manageUrl}\" class=\"text-primary-600 hover:underline dark:text-primary-400\">Manage in Theme Manager</a>"
                                );
                            }),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive sites return 404 on their domain'),

                        Toggle::make('is_default')
                            ->label('Default Site')
                            ->helperText('Fallback site for admin and local development. Only one site can be default.'),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function getLocaleOptions(): array
    {
        try {
            return app(LocaleRegistry::class)->getLocaleOptions();
        } catch (\Throwable) {
            return [];
        }
    }
}
