<?php

namespace Tallcms\Pro\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Tallcms\Pro\Models\ProSetting;
use Tallcms\Pro\Services\Analytics\AnalyticsManager;

class ProSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pro Settings';

    protected static ?string $title = 'TallCMS Pro Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 101;

    protected string $view = 'tallcms-pro::filament.pages.pro-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Analytics
            'analytics_provider' => ProSetting::get('analytics_provider', 'google'),
            'google_analytics_id' => ProSetting::get('google_analytics_id'),
            'ga4_property_id' => ProSetting::get('ga4_property_id'),
            'ga4_credentials_json' => ProSetting::get('ga4_credentials_json'),
            'plausible_domain' => ProSetting::get('plausible_domain'),
            'plausible_api_key' => ProSetting::get('plausible_api_key'),
            'fathom_site_id' => ProSetting::get('fathom_site_id'),
            'fathom_api_key' => ProSetting::get('fathom_api_key'),

            // Email Marketing
            'email_provider' => ProSetting::get('email_provider'),
            'mailchimp_api_key' => ProSetting::get('mailchimp_api_key'),
            'mailchimp_list_id' => ProSetting::get('mailchimp_list_id'),
            'convertkit_api_key' => ProSetting::get('convertkit_api_key'),
            'convertkit_form_id' => ProSetting::get('convertkit_form_id'),
            'sendinblue_api_key' => ProSetting::get('sendinblue_api_key'),
            'sendinblue_list_id' => ProSetting::get('sendinblue_list_id'),

            // Maps
            'maps_provider' => ProSetting::get('maps_provider', 'openstreetmap'),
            'google_maps_api_key' => ProSetting::get('google_maps_api_key'),
            'mapbox_access_token' => ProSetting::get('mapbox_access_token'),
        ]);
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Settings')
                ->tabs([
                    Tabs\Tab::make('Analytics')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Select::make('analytics_provider')
                                ->label('Analytics Provider')
                                ->options([
                                    '' => 'None',
                                    'google' => 'Google Analytics 4',
                                    'plausible' => 'Plausible',
                                    'fathom' => 'Fathom',
                                ])
                                ->default('google')
                                ->live()
                                ->helperText('Choose your analytics provider for the dashboard widget'),

                            // Google Analytics 4 settings
                            Group::make([
                                TextInput::make('google_analytics_id')
                                    ->label('Measurement ID (for tracking)')
                                    ->placeholder('G-XXXXXXXXXX')
                                    ->helperText('Optional: Add to your site for visitor tracking'),

                                TextInput::make('ga4_property_id')
                                    ->label('Property ID (for dashboard)')
                                    ->placeholder('123456789')
                                    ->helperText('Numeric property ID from GA4 Admin > Property Settings'),

                                Textarea::make('ga4_credentials_json')
                                    ->label('Service Account Credentials (JSON)')
                                    ->rows(6)
                                    ->placeholder('Paste your service account JSON key here...')
                                    ->helperText('Create a service account in Google Cloud Console with Analytics Viewer role'),
                            ])
                                ->visible(fn ($get) => $get('analytics_provider') === 'google'),

                            TextInput::make('plausible_domain')
                                ->label('Plausible Domain')
                                ->placeholder('yourdomain.com')
                                ->visible(fn ($get) => $get('analytics_provider') === 'plausible'),

                            TextInput::make('plausible_api_key')
                                ->label('Plausible API Key')
                                ->password()
                                ->visible(fn ($get) => $get('analytics_provider') === 'plausible')
                                ->helperText('Required for dashboard stats'),

                            TextInput::make('fathom_site_id')
                                ->label('Fathom Site ID')
                                ->placeholder('ABCDEFGH')
                                ->visible(fn ($get) => $get('analytics_provider') === 'fathom'),

                            TextInput::make('fathom_api_key')
                                ->label('Fathom API Key')
                                ->password()
                                ->visible(fn ($get) => $get('analytics_provider') === 'fathom')
                                ->helperText('Required for dashboard stats'),
                        ]),

                    Tabs\Tab::make('Email Marketing')
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            Select::make('email_provider')
                                ->label('Email Marketing Provider')
                                ->options([
                                    '' => 'None',
                                    'mailchimp' => 'Mailchimp',
                                    'convertkit' => 'ConvertKit',
                                    'sendinblue' => 'Sendinblue (Brevo)',
                                ])
                                ->live()
                                ->helperText('Choose your email marketing provider'),

                            TextInput::make('mailchimp_api_key')
                                ->label('Mailchimp API Key')
                                ->password()
                                ->visible(fn ($get) => $get('email_provider') === 'mailchimp'),

                            TextInput::make('mailchimp_list_id')
                                ->label('Mailchimp Audience ID')
                                ->visible(fn ($get) => $get('email_provider') === 'mailchimp')
                                ->helperText('The ID of the list to subscribe users to'),

                            TextInput::make('convertkit_api_key')
                                ->label('ConvertKit API Key')
                                ->password()
                                ->visible(fn ($get) => $get('email_provider') === 'convertkit'),

                            TextInput::make('convertkit_form_id')
                                ->label('ConvertKit Form ID')
                                ->visible(fn ($get) => $get('email_provider') === 'convertkit')
                                ->helperText('The ID of the form to subscribe users to'),

                            TextInput::make('sendinblue_api_key')
                                ->label('Sendinblue API Key')
                                ->password()
                                ->visible(fn ($get) => $get('email_provider') === 'sendinblue'),

                            TextInput::make('sendinblue_list_id')
                                ->label('Sendinblue List ID')
                                ->visible(fn ($get) => $get('email_provider') === 'sendinblue')
                                ->helperText('The ID of the list to subscribe users to'),
                        ]),

                    Tabs\Tab::make('Maps')
                        ->icon('heroicon-o-map')
                        ->schema([
                            Select::make('maps_provider')
                                ->label('Default Maps Provider')
                                ->options([
                                    'openstreetmap' => 'OpenStreetMap (Free, no API key required)',
                                    'google' => 'Google Maps',
                                    'mapbox' => 'Mapbox',
                                ])
                                ->default('openstreetmap')
                                ->helperText('Default provider for new Map blocks. Can be overridden per block.'),

                            TextInput::make('google_maps_api_key')
                                ->label('Google Maps API Key')
                                ->password()
                                ->revealable()
                                ->helperText('Get your API key from Google Cloud Console. Required for Google Maps.'),

                            TextInput::make('mapbox_access_token')
                                ->label('Mapbox Access Token')
                                ->password()
                                ->revealable()
                                ->helperText('Get your access token from Mapbox. Required for Mapbox maps.'),
                        ]),
                ])
                ->persistTabInQueryString(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Analytics settings
        ProSetting::set('analytics_provider', $data['analytics_provider'] ?? 'google');
        ProSetting::set('google_analytics_id', $data['google_analytics_id'] ?? null);
        ProSetting::set('ga4_property_id', $data['ga4_property_id'] ?? null);
        ProSetting::set('ga4_credentials_json', $data['ga4_credentials_json'] ?? null, 'text', 'analytics', true);
        ProSetting::set('plausible_domain', $data['plausible_domain'] ?? null);
        ProSetting::set('plausible_api_key', $data['plausible_api_key'] ?? null, 'text', 'analytics', true);
        ProSetting::set('fathom_site_id', $data['fathom_site_id'] ?? null);
        ProSetting::set('fathom_api_key', $data['fathom_api_key'] ?? null, 'text', 'analytics', true);

        // Clear analytics cache when settings change
        try {
            app(AnalyticsManager::class)->clearCache();
        } catch (\Throwable $e) {
            // Ignore if manager not available
        }

        // Email settings
        ProSetting::set('email_provider', $data['email_provider'] ?? null);
        ProSetting::set('mailchimp_api_key', $data['mailchimp_api_key'] ?? null, 'text', 'email', true);
        ProSetting::set('mailchimp_list_id', $data['mailchimp_list_id'] ?? null);
        ProSetting::set('convertkit_api_key', $data['convertkit_api_key'] ?? null, 'text', 'email', true);
        ProSetting::set('convertkit_form_id', $data['convertkit_form_id'] ?? null);
        ProSetting::set('sendinblue_api_key', $data['sendinblue_api_key'] ?? null, 'text', 'email', true);
        ProSetting::set('sendinblue_list_id', $data['sendinblue_list_id'] ?? null);

        // Maps settings
        ProSetting::set('maps_provider', $data['maps_provider'] ?? 'openstreetmap');
        ProSetting::set('google_maps_api_key', $data['google_maps_api_key'] ?? null, 'text', 'maps', true);
        ProSetting::set('mapbox_access_token', $data['mapbox_access_token'] ?? null, 'text', 'maps', true);

        // Clear all settings cache to ensure fresh values
        ProSetting::clearAllCache();

        Notification::make()
            ->title('Settings Saved')
            ->body('Your TallCMS Pro settings have been saved.')
            ->success()
            ->send();
    }
}
