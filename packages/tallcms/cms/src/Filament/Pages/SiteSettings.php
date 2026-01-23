<?php

namespace TallCms\Cms\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;

class SiteSettings extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.site-settings';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    public ?array $data = [];

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-8-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 99;
    }

    public function mount(): void
    {
        $this->form->fill([
            // General settings
            'site_name' => SiteSetting::get('site_name'),
            'site_tagline' => SiteSetting::get('site_tagline'),
            'site_description' => SiteSetting::get('site_description'),
            'site_type' => SiteSetting::get('site_type', 'multi-page'),

            // Contact settings
            'contact_email' => SiteSetting::get('contact_email'),
            'contact_phone' => SiteSetting::get('contact_phone'),
            'company_name' => SiteSetting::get('company_name'),
            'company_address' => SiteSetting::get('company_address'),

            // Social media settings
            'social_facebook' => SiteSetting::get('social_facebook'),
            'social_twitter' => SiteSetting::get('social_twitter'),
            'social_linkedin' => SiteSetting::get('social_linkedin'),
            'social_instagram' => SiteSetting::get('social_instagram'),
            'social_youtube' => SiteSetting::get('social_youtube'),
            'social_tiktok' => SiteSetting::get('social_tiktok'),
            'newsletter_signup_url' => SiteSetting::get('newsletter_signup_url'),

            // Branding settings
            'logo' => SiteSetting::get('logo'),
            'favicon' => SiteSetting::get('favicon'),

            // System settings
            'maintenance_mode' => SiteSetting::get('maintenance_mode', false),
            'maintenance_message' => SiteSetting::get('maintenance_message', 'We\'re currently performing scheduled maintenance. Please check back soon!'),

            // i18n settings
            'i18n_enabled' => SiteSetting::get('i18n_enabled', config('tallcms.i18n.enabled', false)),
            'default_locale' => SiteSetting::get('default_locale', config('tallcms.i18n.default_locale', 'en')),
            'hide_default_locale' => SiteSetting::get('hide_default_locale', config('tallcms.i18n.hide_default_locale', true)),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General Settings')
                ->description('Basic site information and configuration')
                ->schema([
                    TextInput::make('site_name')
                        ->label('Site Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('My Amazing Site')
                        ->helperText('Appears in browser tabs and throughout the site'),

                    TextInput::make('site_tagline')
                        ->label('Site Tagline')
                        ->maxLength(255)
                        ->placeholder('A brief description of what you do')
                        ->helperText('Short phrase that describes your site'),

                    Textarea::make('site_description')
                        ->label('Site Description')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('A longer description for search engines and social media')
                        ->helperText('Used as fallback meta description'),

                    Select::make('site_type')
                        ->label('Site Type')
                        ->options([
                            'multi-page' => 'Multi-Page Website',
                            'single-page' => 'Single-Page Application (SPA)',
                        ])
                        ->default('multi-page')
                        ->required()
                        ->helperText('Multi-page: Traditional website with separate pages. SPA: One-page website with anchor navigation.'),
                ])
                ->columns(2),

            Section::make('Contact Information')
                ->description('Contact details used in merge tags and forms')
                ->schema([
                    TextInput::make('contact_email')
                        ->label('Contact Email')
                        ->email()
                        ->required()
                        ->placeholder('hello@example.com')
                        ->helperText('Default email for contact forms'),

                    TextInput::make('contact_phone')
                        ->label('Contact Phone')
                        ->tel()
                        ->placeholder('+1 (555) 123-4567')
                        ->helperText('Business phone number'),

                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->maxLength(255)
                        ->placeholder('Your Company Inc.')
                        ->helperText('Legal company name'),

                    Textarea::make('company_address')
                        ->label('Company Address')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('123 Main St, City, State 12345')
                        ->helperText('Complete business address'),
                ])
                ->columns(2),

            Section::make('Social Media')
                ->description('Social media links and newsletter signup')
                ->schema([
                    TextInput::make('social_facebook')
                        ->label('Facebook URL')
                        ->url()
                        ->placeholder('https://facebook.com/yourcompany')
                        ->helperText('Full Facebook page URL'),

                    TextInput::make('social_twitter')
                        ->label('Twitter URL')
                        ->url()
                        ->placeholder('https://twitter.com/yourcompany')
                        ->helperText('Full Twitter profile URL'),

                    TextInput::make('social_linkedin')
                        ->label('LinkedIn URL')
                        ->url()
                        ->placeholder('https://linkedin.com/company/yourcompany')
                        ->helperText('Full LinkedIn company page URL'),

                    TextInput::make('social_instagram')
                        ->label('Instagram URL')
                        ->url()
                        ->placeholder('https://instagram.com/yourcompany')
                        ->helperText('Full Instagram profile URL'),

                    TextInput::make('social_youtube')
                        ->label('YouTube URL')
                        ->url()
                        ->placeholder('https://youtube.com/c/yourcompany')
                        ->helperText('Full YouTube channel URL'),

                    TextInput::make('social_tiktok')
                        ->label('TikTok URL')
                        ->url()
                        ->placeholder('https://tiktok.com/@yourcompany')
                        ->helperText('Full TikTok profile URL'),

                    TextInput::make('newsletter_signup_url')
                        ->label('Newsletter Signup URL')
                        ->url()
                        ->placeholder('https://yoursite.com/newsletter')
                        ->helperText('Link to newsletter signup form'),
                ])
                ->columns(2),

            Section::make('Branding')
                ->description('Logo, favicon, and visual branding elements')
                ->schema([
                    FileUpload::make('logo')
                        ->label('Site Logo')
                        ->image()
                        ->directory('site-assets')
                        ->disk(\cms_media_disk())
                        ->visibility(\cms_media_visibility())
                        ->helperText('Upload your site logo (PNG, JPG, or SVG)')
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
                ])
                ->columns(2),

            Section::make('Maintenance Mode')
                ->description('Control site maintenance settings')
                ->schema([
                    Toggle::make('maintenance_mode')
                        ->label('Enable Maintenance Mode')
                        ->helperText('When enabled, all visitors will see a maintenance page. Administrators can still access the admin panel.')
                        ->live()
                        ->columnSpanFull(),

                    Textarea::make('maintenance_message')
                        ->label('Maintenance Message')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('We\'re currently performing scheduled maintenance. Please check back soon!')
                        ->helperText('Message shown to visitors during maintenance mode')
                        ->visible(fn ($get) => $get('maintenance_mode'))
                        ->columnSpanFull(),
                ]),

            Section::make('Languages (i18n)')
                ->description('Configure multilingual support for your content. Note: Run the migration and install the Spatie Translatable plugin to enable i18n.')
                ->schema([
                    Toggle::make('i18n_enabled')
                        ->label('Enable Multilingual Support')
                        ->helperText('When enabled, content can be translated into multiple languages. Requires filament/spatie-laravel-translatable-plugin.')
                        ->live()
                        ->columnSpanFull(),

                    Select::make('default_locale')
                        ->label('Default Language')
                        ->options(fn () => $this->getLocaleOptions())
                        ->searchable()
                        ->required()
                        ->helperText('The primary language for your site. Used when no translation exists for the requested locale.')
                        ->visible(fn ($get) => $get('i18n_enabled')),

                    Toggle::make('hide_default_locale')
                        ->label('Hide Default Language in URLs')
                        ->helperText('When enabled, the default language is accessed at / instead of /en/. Other languages use prefixes like /es/, /fr/.')
                        ->default(true)
                        ->visible(fn ($get) => $get('i18n_enabled')),
                ])
                ->columns(2),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    /**
     * Get available locale options for the select field.
     */
    protected function getLocaleOptions(): array
    {
        try {
            $registry = app(LocaleRegistry::class);

            return $registry->getLocaleOptions();
        } catch (\Throwable) {
            // Fallback if registry not available
            return ['en' => 'English'];
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $type = match ($key) {
                    'logo', 'favicon' => 'file',
                    'maintenance_mode', 'i18n_enabled', 'hide_default_locale' => 'boolean',
                    default => 'text',
                };

                $group = match ($key) {
                    'site_name', 'site_tagline', 'site_description', 'site_type' => 'general',
                    'contact_email', 'contact_phone', 'company_name', 'company_address' => 'contact',
                    'social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram',
                    'social_youtube', 'social_tiktok', 'newsletter_signup_url' => 'social',
                    'logo', 'favicon' => 'branding',
                    'maintenance_mode', 'maintenance_message' => 'maintenance',
                    'i18n_enabled', 'default_locale', 'hide_default_locale' => 'i18n',
                    default => 'general',
                };

                SiteSetting::set($key, $value, $type, $group);
            }
        }

        // Clear all settings cache
        SiteSetting::clearCache();

        // Clear locale registry cache if i18n settings changed
        if (isset($data['i18n_enabled']) || isset($data['default_locale']) || isset($data['hide_default_locale'])) {
            try {
                app(LocaleRegistry::class)->clearCache();
            } catch (\Throwable) {
                // Ignore if registry not available
            }
        }

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }
}
