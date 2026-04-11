<?php

namespace TallCms\Cms\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;

class SiteSettings extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.site-settings';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    public ?array $data = [];

    /**
     * Cached set of overridden keys for the current site.
     * Instance property (not static) so it's cleared on Livewire re-render.
     */
    protected ?array $overriddenKeysCache = null;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-8-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.configuration', 'Configuration');
    }

    public static function getNavigationSort(): ?int
    {
        return 40;
    }

    /**
     * Get the current multisite admin context (session-direct, no resolver dependency).
     */
    protected function getMultisiteContext(): ?object
    {
        $sessionValue = session('multisite_admin_site_id');

        if (! $sessionValue || $sessionValue === '__all_sites__') {
            return null;
        }

        try {
            return DB::table('tallcms_sites')
                ->where('id', $sessionValue)
                ->where('is_active', true)
                ->first();
        } catch (QueryException) {
            return null;
        }
    }

    /**
     * Check if a setting key has a per-site override for the current site.
     */
    protected function hasOverride(string $key): bool
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return false;
        }

        try {
            return DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $context->id)
                ->where('key', $key)
                ->exists();
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * Get the set of keys that have per-site overrides.
     * Uses instance-level cache, cleared by clearOverrideCache().
     */
    protected function getOverriddenKeys(): array
    {
        if ($this->overriddenKeysCache !== null) {
            return $this->overriddenKeysCache;
        }

        $context = $this->getMultisiteContext();
        if (! $context) {
            return $this->overriddenKeysCache = [];
        }

        try {
            $this->overriddenKeysCache = DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $context->id)
                ->pluck('key')
                ->toArray();
        } catch (QueryException) {
            $this->overriddenKeysCache = [];
        }

        return $this->overriddenKeysCache;
    }

    /**
     * Clear the override keys cache (called after save/reset actions).
     */
    protected function clearOverrideCache(): void
    {
        $this->overriddenKeysCache = null;
    }

    public function getSubheading(): ?string
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return null;
        }

        return "Editing settings for: {$context->name} ({$context->domain})";
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
            'show_powered_by' => SiteSetting::get('show_powered_by', true),

            // System settings
            'maintenance_mode' => SiteSetting::get('maintenance_mode', false),
            'maintenance_message' => SiteSetting::get('maintenance_message', 'We\'re currently performing scheduled maintenance. Please check back soon!'),

            // i18n settings
            'i18n_enabled' => SiteSetting::get('i18n_enabled', config('tallcms.i18n.enabled', false)),
            'default_locale' => SiteSetting::get('default_locale', config('tallcms.i18n.default_locale', 'en')),
            'hide_default_locale' => SiteSetting::get('hide_default_locale', config('tallcms.i18n.hide_default_locale', true)),
        ]);
    }

    /**
     * Apply multisite hint to a field: shows override status and "Reset to global" action.
     */
    protected function withMultisiteHint(mixed $field, string $key): mixed
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return $field;
        }

        // Show global default as placeholder for text-like fields
        $globalValue = SiteSetting::getGlobal($key);
        if ($globalValue !== null && method_exists($field, 'placeholder')) {
            $display = is_bool($globalValue) ? ($globalValue ? 'Yes' : 'No')
                : (is_string($globalValue) && $globalValue !== '' ? $globalValue : null);
            if ($display) {
                $field->placeholder("Global: {$display}");
            }
        }

        $overridden = in_array($key, $this->getOverriddenKeys());

        $field->hint($overridden ? 'Site override' : 'Inherited from global');
        $field->hintColor($overridden ? 'primary' : 'gray');
        $field->hintIcon($overridden ? 'heroicon-m-pencil-square' : 'heroicon-m-globe-alt');

        if ($overridden && method_exists($field, 'suffixAction')) {
            $field->suffixAction(
                Action::make("reset_{$key}")
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->tooltip('Reset to global default')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Reset to Global Default')
                    ->modalDescription('This will remove the site-specific override. The setting will inherit the global default value.')
                    ->action(function () use ($key) {
                        SiteSetting::resetToGlobal($key);
                        SiteSetting::clearCache();
                        $this->clearOverrideCache();

                        Notification::make()
                            ->title('Reset to global')
                            ->body("'{$key}' will now inherit the global default.")
                            ->success()
                            ->send();

                        // Refresh form data
                        $this->mount();
                    })
            );
        }

        return $field;
    }

    protected function getFormSchema(): array
    {
        $isMultisite = $this->getMultisiteContext() !== null;

        return [
            Section::make('General Settings')
                ->description($isMultisite ? 'Basic site information for this site' : 'Basic site information and configuration')
                ->schema([
                    $this->withMultisiteHint(
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Appears in browser tabs and throughout the site'),
                        'site_name'
                    ),

                    $this->withMultisiteHint(
                        TextInput::make('site_tagline')
                            ->label('Site Tagline')
                            ->maxLength(255)
                            ->helperText('Short phrase that describes your site'),
                        'site_tagline'
                    ),

                    $this->withMultisiteHint(
                        Textarea::make('site_description')
                            ->label('Site Description')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Used as fallback meta description'),
                        'site_description'
                    ),

                    $this->withMultisiteHint(
                        Select::make('site_type')
                            ->label('Site Type')
                            ->options([
                                'multi-page' => 'Multi-Page Website',
                                'single-page' => 'Single-Page Application (SPA)',
                            ])
                            ->default('multi-page')
                            ->required()
                            ->helperText('Multi-page: Traditional website. SPA: One-page with anchor navigation.'),
                        'site_type'
                    ),
                ])
                ->columns(2),

            Section::make('Contact Information')
                ->description('Contact details used in merge tags and forms')
                ->schema([
                    $this->withMultisiteHint(
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->required()
                            ->helperText('Default email for contact forms'),
                        'contact_email'
                    ),

                    $this->withMultisiteHint(
                        TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->helperText('Business phone number'),
                        'contact_phone'
                    ),

                    $this->withMultisiteHint(
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255)
                            ->helperText('Legal company name'),
                        'company_name'
                    ),

                    $this->withMultisiteHint(
                        Textarea::make('company_address')
                            ->label('Company Address')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Complete business address'),
                        'company_address'
                    ),
                ])
                ->columns(2),

            Section::make('Social Media')
                ->description('Social media links and newsletter signup')
                ->schema([
                    $this->withMultisiteHint(TextInput::make('social_facebook')->label('Facebook URL')->url(), 'social_facebook'),
                    $this->withMultisiteHint(TextInput::make('social_twitter')->label('Twitter URL')->url(), 'social_twitter'),
                    $this->withMultisiteHint(TextInput::make('social_linkedin')->label('LinkedIn URL')->url(), 'social_linkedin'),
                    $this->withMultisiteHint(TextInput::make('social_instagram')->label('Instagram URL')->url(), 'social_instagram'),
                    $this->withMultisiteHint(TextInput::make('social_youtube')->label('YouTube URL')->url(), 'social_youtube'),
                    $this->withMultisiteHint(TextInput::make('social_tiktok')->label('TikTok URL')->url(), 'social_tiktok'),
                    $this->withMultisiteHint(TextInput::make('newsletter_signup_url')->label('Newsletter Signup URL')->url(), 'newsletter_signup_url'),
                ])
                ->columns(2),

            Section::make('Branding')
                ->description('Logo, favicon, and visual branding elements')
                ->schema([
                    $this->withMultisiteHint(
                        FileUpload::make('logo')
                            ->label('Site Logo')
                            ->image()
                            ->directory('site-assets')
                            ->disk(\cms_media_disk())
                            ->visibility(\cms_media_visibility())
                            ->helperText('Upload your site logo (PNG, JPG, or SVG)')
                            ->deletable()
                            ->nullable(),
                        'logo'
                    ),

                    $this->withMultisiteHint(
                        FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->directory('site-assets')
                            ->disk(\cms_media_disk())
                            ->visibility(\cms_media_visibility())
                            ->acceptedFileTypes(['image/x-icon', 'image/png'])
                            ->helperText('Upload favicon (.ico or .png, 16x16 or 32x32 pixels)')
                            ->nullable(),
                        'favicon'
                    ),

                    $this->withMultisiteHint(
                        Toggle::make('show_powered_by')
                            ->label('Show "Powered by TallCMS" Badge')
                            ->helperText('Displays a small badge in the site footer.')
                            ->default(true)
                            ->columnSpanFull(),
                        'show_powered_by'
                    ),
                ])
                ->columns(2),

            Section::make('Maintenance Mode')
                ->description('Control site maintenance settings')
                ->schema([
                    $this->withMultisiteHint(
                        Toggle::make('maintenance_mode')
                            ->label('Enable Maintenance Mode')
                            ->helperText('When enabled, visitors see a maintenance page. Admins can still access the panel.')
                            ->live()
                            ->columnSpanFull(),
                        'maintenance_mode'
                    ),

                    $this->withMultisiteHint(
                        Textarea::make('maintenance_message')
                            ->label('Maintenance Message')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Message shown to visitors during maintenance mode')
                            ->visible(fn ($get) => $get('maintenance_mode'))
                            ->columnSpanFull(),
                        'maintenance_message'
                    ),
                ]),

            Section::make('Languages (i18n)')
                ->description($isMultisite
                    ? 'Language settings are global (apply to all sites). Per-site locale is set in the site configuration.'
                    : 'Configure multilingual support for your content.')
                ->schema([
                    Toggle::make('i18n_enabled')
                        ->label('Enable Multilingual Support')
                        ->helperText('When enabled, content can be translated into multiple languages.')
                        ->live()
                        ->columnSpanFull()
                        ->disabled($isMultisite)
                        ->hint($isMultisite ? 'Global setting' : null)
                        ->hintColor($isMultisite ? 'warning' : null)
                        ->hintIcon($isMultisite ? 'heroicon-m-lock-closed' : null),

                    Select::make('default_locale')
                        ->label('Default Language')
                        ->options(fn () => $this->getLocaleOptions())
                        ->searchable()
                        ->required()
                        ->helperText('The primary language for your site.')
                        ->visible(fn ($get) => $get('i18n_enabled'))
                        ->disabled($isMultisite)
                        ->hint($isMultisite ? 'Global setting' : null)
                        ->hintColor($isMultisite ? 'warning' : null)
                        ->hintIcon($isMultisite ? 'heroicon-m-lock-closed' : null),

                    Toggle::make('hide_default_locale')
                        ->label('Hide Default Language in URLs')
                        ->helperText('Default language accessed at / instead of /en/.')
                        ->default(true)
                        ->visible(fn ($get) => $get('i18n_enabled'))
                        ->disabled($isMultisite)
                        ->hint($isMultisite ? 'Global setting' : null)
                        ->hintColor($isMultisite ? 'warning' : null)
                        ->hintIcon($isMultisite ? 'heroicon-m-lock-closed' : null),
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
            return ['en' => 'English'];
        }
    }

    /**
     * Compare a submitted value with a global value, accounting for type.
     */
    protected function valuesMatch(mixed $submitted, mixed $global, string $type): bool
    {
        if ($type === 'boolean') {
            return (bool) $submitted === (bool) $global;
        }

        return (string) ($submitted ?? '') === (string) ($global ?? '');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $isMultisite = $this->getMultisiteContext() !== null;
        $overriddenKeys = $isMultisite ? $this->getOverriddenKeys() : [];

        foreach ($data as $key => $value) {
            $type = match ($key) {
                'logo', 'favicon' => 'file',
                'maintenance_mode', 'i18n_enabled', 'hide_default_locale', 'show_powered_by' => 'boolean',
                default => 'text',
            };

            $group = match ($key) {
                'site_name', 'site_tagline', 'site_description', 'site_type' => 'general',
                'contact_email', 'contact_phone', 'company_name', 'company_address' => 'contact',
                'social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram',
                'social_youtube', 'social_tiktok', 'newsletter_signup_url' => 'social',
                'logo', 'favicon', 'show_powered_by' => 'branding',
                'maintenance_mode', 'maintenance_message' => 'maintenance',
                'i18n_enabled', 'default_locale', 'hide_default_locale' => 'i18n',
                default => 'general',
            };

            if ($isMultisite) {
                $isFileField = $type === 'file';

                // File fields: null means "unchanged", skip entirely
                if ($isFileField && $value === null) {
                    continue;
                }

                // Only create/update overrides when:
                // - Field already has an existing override (update it)
                // - OR submitted value differs from global (new override)
                // Untouched fields matching global are skipped (treated as inheritance).
                $hasExistingOverride = in_array($key, $overriddenKeys);
                if (! $hasExistingOverride) {
                    $globalValue = SiteSetting::getGlobal($key);
                    if ($this->valuesMatch($value, $globalValue, $type)) {
                        continue; // Matches global, no override needed
                    }
                }

                SiteSetting::set($key, $value ?? '', $type, $group);
            } else {
                // Global context: save non-null values, and allow file fields to be cleared
                if ($value !== null) {
                    SiteSetting::set($key, $value, $type, $group);
                } elseif ($type === 'file') {
                    SiteSetting::set($key, '', $type, $group);
                }
            }
        }

        // Clear all settings cache and override indicator cache
        SiteSetting::clearCache();
        $this->clearOverrideCache();

        // Clear locale registry cache if i18n settings changed
        if (isset($data['i18n_enabled']) || isset($data['default_locale']) || isset($data['hide_default_locale'])) {
            try {
                app(LocaleRegistry::class)->clearCache();
            } catch (\Throwable) {
                // Ignore if registry not available
            }
        }

        $context = $this->getMultisiteContext();
        $message = $context
            ? "Settings saved for {$context->name}."
            : 'Settings saved successfully!';

        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
