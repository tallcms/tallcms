<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
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
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'wide';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getDescription(): string
    {
        return 'Feature grid with icons and descriptions';
    }

    public static function getKeywords(): array
    {
        return ['features', 'benefits', 'list', 'grid'];
    }

    public static function getSortPriority(): int
    {
        return 25;
    }

    public static function getId(): string
    {
        return 'features';
    }

    public static function getLabel(): string
    {
        return 'Features';
    }

    protected static function getCardStyleOptions(): array
    {
        return [
            'card bg-base-100 shadow-md' => 'Cards with Shadow',
            'card bg-base-100 border border-base-300' => 'Bordered Cards',
            'bg-base-100' => 'Minimal (No Border)',
            'card bg-base-200' => 'Subtle Background',
            'card bg-primary text-primary-content' => 'Primary Cards',
        ];
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
                                            ->disk(\cms_media_disk())
                                            ->directory('features')
                                            ->visibility(\cms_media_visibility())
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

                                        Select::make('card_style')
                                            ->label('Card Style')
                                            ->options(static::getCardStyleOptions())
                                            ->default('card bg-base-100 shadow-md'),

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
                                                'text-left' => 'Left',
                                                'text-center' => 'Center',
                                            ])
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

                                Section::make('Appearance')
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('icon_size')
                                            ->label('Icon Size')
                                            ->options([
                                                'w-8 h-8' => 'Small',
                                                'w-10 h-10' => 'Medium',
                                                'w-12 h-12' => 'Large',
                                            ])
                                            ->default('w-10 h-10'),

                                        Select::make('padding')
                                            ->label('Section Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Spacing)')
                                            ->default(false),
                                    ])
                                    ->columns(4),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $features = $config['features'] ?? self::getSampleFeatures();

        return static::renderBlock(array_merge($config, ['features' => $features]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.features', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'features' => $config['features'] ?? [],
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'card bg-base-100 shadow-md',
            'icon_position' => $config['icon_position'] ?? 'top',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'icon_size' => $config['icon_size'] ?? 'w-10 h-10',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
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
