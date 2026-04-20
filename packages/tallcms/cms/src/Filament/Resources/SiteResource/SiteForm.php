<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Services\LocaleRegistry;

class SiteForm
{
    /**
     * Configure a Schema instance (used by SiteResource::form()).
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::schema());
    }

    /**
     * Full form schema with tabs for the Site edit page.
     */
    public static function schema(?Site $site = null): array
    {
        return [
            Tabs::make('Site Settings')
                ->tabs([
                    static::generalTab($site),
                    static::brandingTab(),
                    static::contactTab(),
                    static::socialTab(),
                    static::publishingTab(),
                    static::maintenanceTab(),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ];
    }

    public static function generalTab(?Site $site = null): Tabs\Tab
    {
        return Tabs\Tab::make('General')
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make('Site Identity')
                    ->description('Basic site information visible to visitors')
                    ->schema([
                        TextInput::make('name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The public brand name shown in browser tabs and throughout the site'),

                        TextInput::make('site_tagline')
                            ->label('Tagline')
                            ->maxLength(255)
                            ->helperText('Short phrase that describes your site'),

                        Textarea::make('site_description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Used as fallback meta description'),

                        Select::make('site_type')
                            ->label('Site Type')
                            ->options([
                                'multi-page' => 'Multi-Page Website',
                                'single-page' => 'Single-Page Application (SPA)',
                            ])
                            ->default('multi-page')
                            ->required()
                            ->helperText('Multi-page: Traditional website. SPA: One-page with anchor navigation.'),
                    ])
                    ->columns(2),

                Section::make('Technical')
                    ->description('Domain, theme, and locale configuration')
                    ->schema([
                        TextInput::make('domain')
                            ->label('Domain')
                            ->disabled(! tallcms_multisite_active())
                            ->dehydrated()
                            ->helperText(tallcms_multisite_active()
                                ? 'The domain this site is served on'
                                : 'Domain is derived from your APP_URL in standalone mode'),

                        Select::make('theme')
                            ->label('Theme')
                            ->options(fn () => static::getThemeOptions())
                            ->helperText('Visual theme for this site'),

                        Select::make('locale')
                            ->label('Default Locale')
                            ->options(fn () => static::getLocaleOptions())
                            ->searchable()
                            ->helperText('Primary language for this site'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * General tab with only site-scoped settings (no model fields).
     * Used by multisite plugin where model fields live on the Site tab.
     */
    public static function settingsGeneralTab(): Tabs\Tab
    {
        return Tabs\Tab::make('General')
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make('Site Identity')
                    ->description('Basic site information visible to visitors')
                    ->schema([
                        TextInput::make('site_tagline')
                            ->label('Tagline')
                            ->maxLength(255)
                            ->helperText('Short phrase that describes your site'),

                        Textarea::make('site_description')
                            ->label('Description')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Used as fallback meta description'),

                        Select::make('site_type')
                            ->label('Site Type')
                            ->options([
                                'multi-page' => 'Multi-Page Website',
                                'single-page' => 'Single-Page Application (SPA)',
                            ])
                            ->default('multi-page')
                            ->required()
                            ->helperText('Multi-page: Traditional website. SPA: One-page with anchor navigation.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function brandingTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Branding')
            ->icon('heroicon-o-paint-brush')
            ->schema([
                Section::make('Visual Identity')
                    ->description('Logo, favicon, and visual branding elements')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Site Logo')
                            ->image()
                            ->directory('site-assets')
                            ->disk(\cms_media_disk())
                            ->visibility(\cms_media_visibility())
                            ->helperText('Upload your site logo (PNG, JPG, or SVG)')
                            ->deletable()
                            ->nullable(),

                        FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->directory('site-assets')
                            ->disk(\cms_media_disk())
                            ->visibility(\cms_media_visibility())
                            ->acceptedFileTypes(['image/x-icon', 'image/png'])
                            ->helperText('Upload favicon (.ico or .png, 16x16 or 32x32 pixels)')
                            ->nullable(),

                        Toggle::make('show_powered_by')
                            ->label('Show "Powered by TallCMS" Badge')
                            ->helperText('Displays a small badge in the site footer.')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function contactTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Contact')
            ->icon('heroicon-o-envelope')
            ->schema([
                Section::make('Contact Information')
                    ->description('Contact details used in merge tags and forms')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->helperText('Default email for contact forms'),

                        TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->helperText('Business phone number'),

                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255)
                            ->helperText('Legal company name'),

                        Textarea::make('company_address')
                            ->label('Company Address')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Complete business address'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function socialTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Social')
            ->icon('heroicon-o-share')
            ->schema([
                Section::make('Social Media')
                    ->description('Social media links and newsletter signup')
                    ->schema([
                        TextInput::make('social_facebook')->label('Facebook URL')->url(),
                        TextInput::make('social_twitter')->label('Twitter / X URL')->url(),
                        TextInput::make('social_linkedin')->label('LinkedIn URL')->url(),
                        TextInput::make('social_instagram')->label('Instagram URL')->url(),
                        TextInput::make('social_youtube')->label('YouTube URL')->url(),
                        TextInput::make('social_tiktok')->label('TikTok URL')->url(),
                        TextInput::make('newsletter_signup_url')->label('Newsletter Signup URL')->url(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function publishingTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Publishing')
            ->icon('heroicon-o-document-check')
            ->schema([
                Section::make('Publishing Workflow')
                    ->description('Control how content gets published on this site')
                    ->schema([
                        Toggle::make('review_workflow_enabled')
                            ->label('Enable Review Workflow')
                            ->helperText('When enabled, authors must submit content for review before it can be published. When disabled, all users with create permission can publish directly.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function maintenanceTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Maintenance')
            ->icon('heroicon-o-wrench-screwdriver')
            ->schema([
                Section::make('Maintenance Mode')
                    ->description('Control site availability for visitors')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Enable Maintenance Mode')
                            ->helperText('When enabled, visitors see a maintenance page. Admins can still access the panel.')
                            ->live()
                            ->columnSpanFull(),

                        Textarea::make('maintenance_message')
                            ->label('Maintenance Message')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Message shown to visitors during maintenance mode')
                            ->visible(fn ($get) => $get('maintenance_mode'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Get theme options for the select field.
     */
    protected static function getThemeOptions(): array
    {
        try {
            if (app()->bound('theme.manager')) {
                $themes = app('theme.manager')->getAvailableThemes();

                return $themes->mapWithKeys(fn ($theme) => [$theme->slug => $theme->name])->toArray();
            }
        } catch (\Throwable) {
        }

        return ['default' => 'Default'];
    }

    /**
     * Get locale options for the select field.
     */
    protected static function getLocaleOptions(): array
    {
        try {
            return app(LocaleRegistry::class)->getLocaleOptions();
        } catch (\Throwable) {
            return ['en' => 'English'];
        }
    }
}
