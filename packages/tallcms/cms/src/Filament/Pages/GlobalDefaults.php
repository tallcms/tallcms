<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;

/**
 * Global defaults for all site-scoped settings.
 *
 * These values are inherited by every site unless overridden.
 * In standalone mode (single site), these serve as the base configuration.
 * In multisite mode, per-site overrides on the Site edit page take precedence.
 */
class GlobalDefaults extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.global-defaults';

    protected static ?string $navigationLabel = 'Global Defaults';

    protected static ?string $title = 'Global Defaults';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.configuration', 'Configuration');
    }

    public static function getNavigationSort(): ?int
    {
        return 39;
    }

    /**
     * Setting keys and their types (mirrors EditSite::$settingKeys).
     */
    protected array $settingKeys = [
        // General
        'site_tagline' => 'text',
        'site_description' => 'text',
        'site_type' => 'text',
        // Contact
        'contact_email' => 'text',
        'contact_phone' => 'text',
        'company_name' => 'text',
        'company_address' => 'text',
        // Social
        'social_facebook' => 'text',
        'social_twitter' => 'text',
        'social_linkedin' => 'text',
        'social_instagram' => 'text',
        'social_youtube' => 'text',
        'social_tiktok' => 'text',
        'newsletter_signup_url' => 'text',
        // Branding
        'logo' => 'file',
        'favicon' => 'file',
        'show_powered_by' => 'boolean',
        // Publishing
        'review_workflow_enabled' => 'boolean',
        // Maintenance
        'maintenance_mode' => 'boolean',
        'maintenance_message' => 'text',
    ];

    public function mount(): void
    {
        $formData = [];

        foreach ($this->settingKeys as $key => $type) {
            $default = match ($key) {
                'show_powered_by' => true,
                'site_type' => 'multi-page',
                'maintenance_message' => "We're currently performing scheduled maintenance. Please check back soon!",
                default => null,
            };

            $formData[$key] = SiteSetting::getGlobal($key, $default);
        }

        // i18n settings (installation-scoped)
        $formData['i18n_enabled'] = SiteSetting::getGlobal('i18n_enabled', config('tallcms.i18n.enabled', false));
        $formData['default_locale'] = SiteSetting::getGlobal('default_locale', config('tallcms.i18n.default_locale', 'en'));
        $formData['hide_default_locale'] = SiteSetting::getGlobal('hide_default_locale', config('tallcms.i18n.hide_default_locale', true));

        $this->form->fill($formData);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save site-scoped setting defaults
        foreach ($this->settingKeys as $key => $type) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            // File fields: null could mean "unchanged" or "deleted".
            if ($type === 'file' && $value === null) {
                $stored = SiteSetting::getGlobal($key);
                if (! $stored) {
                    continue;
                }
                $value = '';
            }

            $group = match ($key) {
                'site_tagline', 'site_description', 'site_type' => 'general',
                'contact_email', 'contact_phone', 'company_name', 'company_address' => 'contact',
                'social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram',
                'social_youtube', 'social_tiktok', 'newsletter_signup_url' => 'social',
                'logo', 'favicon', 'show_powered_by' => 'branding',
                'review_workflow_enabled' => 'publishing',
                'maintenance_mode', 'maintenance_message' => 'maintenance',
                default => 'general',
            };

            SiteSetting::setGlobal($key, $value, $type, $group);
        }

        // Save i18n settings
        if (array_key_exists('i18n_enabled', $data)) {
            SiteSetting::setGlobal('i18n_enabled', $data['i18n_enabled'], 'boolean', 'i18n');
        }
        if (array_key_exists('default_locale', $data)) {
            SiteSetting::setGlobal('default_locale', $data['default_locale'], 'text', 'i18n');
        }
        if (array_key_exists('hide_default_locale', $data)) {
            SiteSetting::setGlobal('hide_default_locale', $data['hide_default_locale'], 'boolean', 'i18n');
        }

        SiteSetting::clearCache();

        // Clear locale registry cache if i18n settings changed
        if (isset($data['i18n_enabled']) || isset($data['default_locale']) || isset($data['hide_default_locale'])) {
            try {
                app(LocaleRegistry::class)->clearCache();
            } catch (\Throwable) {
            }
        }

        Notification::make()
            ->title('Global defaults saved')
            ->body('All sites without overrides will inherit these values.')
            ->success()
            ->send();
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Inherited Defaults')
                ->description('These are the default values inherited by all sites. Per-site overrides on the Site Settings page take precedence.')
                ->schema([]),

            Tabs::make('Global Defaults')
                ->tabs([
                    $this->generalTab(),
                    $this->brandingTab(),
                    $this->contactTab(),
                    $this->socialTab(),
                    $this->publishingTab(),
                    $this->maintenanceTab(),
                    $this->i18nTab(),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ];
    }

    protected function generalTab(): Tabs\Tab
    {
        return Tabs\Tab::make('General')
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make('Site Identity Defaults')
                    ->description('Default identity values inherited by new sites')
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

    protected function brandingTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Branding')
            ->icon('heroicon-o-paint-brush')
            ->schema([
                Section::make('Visual Identity Defaults')
                    ->description('Default branding inherited by new sites')
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

    protected function contactTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Contact')
            ->icon('heroicon-o-envelope')
            ->schema([
                Section::make('Contact Information Defaults')
                    ->description('Default contact details inherited by new sites')
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

    protected function socialTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Social')
            ->icon('heroicon-o-share')
            ->schema([
                Section::make('Social Media Defaults')
                    ->description('Default social links inherited by new sites')
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

    protected function publishingTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Publishing')
            ->icon('heroicon-o-document-check')
            ->schema([
                Section::make('Publishing Workflow Default')
                    ->description('Default publishing behavior inherited by new sites')
                    ->schema([
                        Toggle::make('review_workflow_enabled')
                            ->label('Enable Review Workflow')
                            ->helperText('When enabled, authors must submit content for review before it can be published. When disabled, all users with create permission can publish directly.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function maintenanceTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Maintenance')
            ->icon('heroicon-o-wrench-screwdriver')
            ->schema([
                Section::make('Maintenance Mode Default')
                    ->description('Default maintenance settings inherited by new sites')
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

    protected function i18nTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Languages')
            ->icon('heroicon-o-language')
            ->schema([
                Section::make('Multilingual Support')
                    ->description('Configure multilingual support for your content. These settings are installation-wide and apply to all sites.')
                    ->schema([
                        Toggle::make('i18n_enabled')
                            ->label('Enable Multilingual Support')
                            ->helperText('When enabled, content can be translated into multiple languages.')
                            ->live()
                            ->columnSpanFull(),

                        Select::make('default_locale')
                            ->label('Default Language')
                            ->options(fn () => $this->getLocaleOptions())
                            ->searchable()
                            ->required()
                            ->helperText('The primary language for your site.')
                            ->visible(fn ($get) => $get('i18n_enabled')),

                        Toggle::make('hide_default_locale')
                            ->label('Hide Default Language in URLs')
                            ->helperText('Default language accessed at / instead of /en/.')
                            ->default(true)
                            ->visible(fn ($get) => $get('i18n_enabled')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getLocaleOptions(): array
    {
        try {
            return app(LocaleRegistry::class)->getLocaleOptions();
        } catch (\Throwable) {
            return ['en' => 'English'];
        }
    }
}
