<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class FeaturesBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'features';
    }

    public static function getLabel(): string
    {
        return 'Features';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Showcase product or service features in a grid layout')
            ->modalHeading('Configure Features Block')
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Features Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('Our Features')
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label('Section Subheading')
                                    ->placeholder('Everything you need to succeed')
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('features')
                                    ->label('Features')
                                    ->schema([
                                        Select::make('icon_type')
                                            ->label('Icon Type')
                                            ->options([
                                                'heroicon' => 'Heroicon',
                                                'image' => 'Custom Image',
                                                'emoji' => 'Emoji',
                                            ])
                                            ->default('heroicon')
                                            ->live(),

                                        TextInput::make('icon')
                                            ->label('Heroicon Name')
                                            ->placeholder('heroicon-o-check-circle')
                                            ->helperText('e.g., heroicon-o-bolt, heroicon-o-shield-check')
                                            ->visible(fn (Get $get): bool => $get('icon_type') === 'heroicon'),

                                        FileUpload::make('icon_image')
                                            ->label('Icon Image')
                                            ->image()
                                            ->disk(cms_media_disk())
                                            ->directory('features')
                                            ->visibility(cms_media_visibility())
                                            ->visible(fn (Get $get): bool => $get('icon_type') === 'image'),

                                        TextInput::make('emoji')
                                            ->label('Emoji')
                                            ->placeholder('🚀')
                                            ->maxLength(10)
                                            ->visible(fn (Get $get): bool => $get('icon_type') === 'emoji'),

                                        TextInput::make('title')
                                            ->label('Feature Title')
                                            ->required()
                                            ->placeholder('Fast Performance')
                                            ->maxLength(100),

                                        Textarea::make('description')
                                            ->label('Feature Description')
                                            ->placeholder('Lightning fast load times and optimized performance.')
                                            ->maxLength(500)
                                            ->rows(2),

                                        TextInput::make('link')
                                            ->label('Link (Optional)')
                                            ->placeholder('https://example.com/features')
                                            ->helperText('Use full URLs (https://...) or relative paths (/page)')
                                            ->maxLength(255),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(12)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Feature')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make('Grid Layout')
                                    ->schema([
                                        Select::make('columns')
                                            ->label('Columns')
                                            ->options([
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                            ])
                                            ->default('3'),

                                        Select::make('style')
                                            ->label('Card Style')
                                            ->options([
                                                'cards' => 'Cards with Shadow',
                                                'bordered' => 'Bordered Cards',
                                                'minimal' => 'Minimal (No Border)',
                                            ])
                                            ->default('cards'),

                                        Select::make('icon_position')
                                            ->label('Icon Position')
                                            ->options([
                                                'top' => 'Top (Centered)',
                                                'left' => 'Left (Inline)',
                                            ])
                                            ->default('top'),

                                        Select::make('text_alignment')
                                            ->label('Text Alignment')
                                            ->options([
                                                'left' => 'Left',
                                                'center' => 'Center',
                                            ])
                                            ->default('center'),
                                    ])
                                    ->columns(2),

                                Section::make('Spacing')
                                    ->schema([
                                        Select::make('icon_size')
                                            ->label('Icon Size')
                                            ->options([
                                                'small' => 'Small',
                                                'medium' => 'Medium',
                                                'large' => 'Large',
                                            ])
                                            ->default('medium'),

                                        Select::make('padding')
                                            ->label('Card Padding')
                                            ->options([
                                                'small' => 'Small',
                                                'medium' => 'Medium',
                                                'large' => 'Large',
                                            ])
                                            ->default('medium'),

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Spacing)')
                                            ->default(false),
                                    ])
                                    ->columns(3),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $features = $config['features'] ?? self::getSampleFeatures();

        return view('cms.blocks.features', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? 'Our Features',
            'subheading' => $config['subheading'] ?? 'Everything you need to build amazing products',
            'features' => $features,
            'columns' => $config['columns'] ?? '3',
            'style' => $config['style'] ?? 'cards',
            'icon_position' => $config['icon_position'] ?? 'top',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'icon_size' => $config['icon_size'] ?? 'medium',
            'padding' => $config['padding'] ?? 'medium',
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.features', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'features' => $config['features'] ?? [],
            'columns' => $config['columns'] ?? '3',
            'style' => $config['style'] ?? 'cards',
            'icon_position' => $config['icon_position'] ?? 'top',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'icon_size' => $config['icon_size'] ?? 'medium',
            'padding' => $config['padding'] ?? 'medium',
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }

    private static function getSampleFeatures(): array
    {
        return [
            [
                'icon_type' => 'heroicon',
                'icon' => 'heroicon-o-bolt',
                'title' => 'Lightning Fast',
                'description' => 'Optimized for speed with sub-second load times.',
            ],
            [
                'icon_type' => 'heroicon',
                'icon' => 'heroicon-o-shield-check',
                'title' => 'Secure by Default',
                'description' => 'Enterprise-grade security built into every layer.',
            ],
            [
                'icon_type' => 'heroicon',
                'icon' => 'heroicon-o-cube-transparent',
                'title' => 'Fully Customizable',
                'description' => 'Tailor every aspect to match your brand perfectly.',
            ],
        ];
    }
}
