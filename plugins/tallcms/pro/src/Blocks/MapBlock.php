<?php

namespace Tallcms\Pro\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Tallcms\Pro\Traits\RequiresLicense;

class MapBlock extends RichContentCustomBlock
{
    use RequiresLicense;

    public static function getId(): string
    {
        return 'pro-map';
    }

    public static function getLabel(): string
    {
        return 'Map (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Embed interactive maps from various providers')
            ->modalHeading('Configure Map Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Find Us'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Visit our office or get directions')
                            ->rows(2),
                    ]),

                Section::make('Location')
                    ->schema([
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->placeholder('40.7128')
                            ->required()
                            ->helperText('Decimal degrees (e.g., 40.7128)'),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->placeholder('-74.0060')
                            ->required()
                            ->helperText('Decimal degrees (e.g., -74.0060)'),

                        TextInput::make('address')
                            ->label('Address Label')
                            ->placeholder('123 Main Street, New York, NY')
                            ->helperText('Shown in info popup'),

                        TextInput::make('marker_title')
                            ->label('Marker Title')
                            ->placeholder('Our Office')
                            ->helperText('Title shown on marker hover'),
                    ])
                    ->columns(2),

                Section::make('Map Provider')
                    ->schema([
                        Select::make('provider')
                            ->label('Map Provider')
                            ->options([
                                'openstreetmap' => 'OpenStreetMap (Free, no API key)',
                                'google' => 'Google Maps (requires API key)',
                                'mapbox' => 'Mapbox (requires API key)',
                            ])
                            ->default('openstreetmap')
                            ->live()
                            ->helperText('OpenStreetMap is free and requires no API key'),

                        TextInput::make('api_key')
                            ->label('API Key')
                            ->placeholder('Your API key')
                            ->password()
                            ->revealable()
                            ->visible(fn($get) => in_array($get('provider'), ['google', 'mapbox']))
                            ->helperText('Configure API keys in Pro Settings for better security'),
                    ]),

                Section::make('Options')
                    ->schema([
                        TextInput::make('zoom')
                            ->label('Zoom Level')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->default(14)
                            ->helperText('1 = world view, 20 = street level'),

                        Select::make('height')
                            ->label('Map Height')
                            ->options([
                                'sm' => 'Small (300px)',
                                'md' => 'Medium (400px)',
                                'lg' => 'Large (500px)',
                                'xl' => 'Extra Large (600px)',
                            ])
                            ->default('md'),

                        Select::make('style')
                            ->label('Map Style')
                            ->options([
                                'streets' => 'Streets',
                                'satellite' => 'Satellite',
                                'hybrid' => 'Hybrid',
                                'terrain' => 'Terrain',
                            ])
                            ->default('streets')
                            ->visible(fn($get) => $get('provider') !== 'openstreetmap'),

                        Toggle::make('show_marker')
                            ->label('Show Marker')
                            ->default(true),

                        Toggle::make('scrollwheel_zoom')
                            ->label('Scroll Wheel Zoom')
                            ->default(false)
                            ->helperText('Allow zooming with mouse wheel'),

                        Toggle::make('rounded')
                            ->label('Rounded Corners')
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make('Contact Info')
                    ->schema([
                        Textarea::make('contact_info')
                            ->label('Contact Information')
                            ->placeholder("Hours: Mon-Fri 9am-5pm\nPhone: (555) 123-4567\nEmail: hello@example.com")
                            ->rows(4)
                            ->helperText('Shown below the map'),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.map', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'latitude' => $config['latitude'] ?? null,
            'longitude' => $config['longitude'] ?? null,
            'address' => $config['address'] ?? '',
            'marker_title' => $config['marker_title'] ?? '',
            'provider' => $config['provider'] ?? 'openstreetmap',
            'api_key' => $config['api_key'] ?? '',
            'zoom' => $config['zoom'] ?? 14,
            'height' => $config['height'] ?? 'md',
            'style' => $config['style'] ?? 'streets',
            'show_marker' => $config['show_marker'] ?? true,
            'scrollwheel_zoom' => $config['scrollwheel_zoom'] ?? false,
            'rounded' => $config['rounded'] ?? true,
            'contact_info' => $config['contact_info'] ?? '',
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.map', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'latitude' => $config['latitude'] ?? null,
            'longitude' => $config['longitude'] ?? null,
            'address' => $config['address'] ?? '',
            'marker_title' => $config['marker_title'] ?? '',
            'provider' => $config['provider'] ?? 'openstreetmap',
            'api_key' => $config['api_key'] ?? '',
            'zoom' => $config['zoom'] ?? 14,
            'height' => $config['height'] ?? 'md',
            'style' => $config['style'] ?? 'streets',
            'show_marker' => $config['show_marker'] ?? true,
            'scrollwheel_zoom' => $config['scrollwheel_zoom'] ?? false,
            'rounded' => $config['rounded'] ?? true,
            'contact_info' => $config['contact_info'] ?? '',
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
