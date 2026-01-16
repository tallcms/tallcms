<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Services\BlockLinkResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class HeroBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;

    public static function getId(): string
    {
        return 'hero';
    }

    public static function getLabel(): string
    {
        return 'Hero';
    }

    /**
     * Get hero-specific button variants (designed for dark backgrounds)
     */
    protected static function getHeroButtonVariantOptions(): array
    {
        return [
            'btn-primary' => 'Primary',
            'btn-secondary' => 'Secondary',
            'btn-accent' => 'Accent',
            'btn-neutral' => 'Neutral',
            'btn-ghost text-white hover:bg-white/20' => 'Ghost (Light)',
            'btn-outline btn-primary' => 'Primary Outline',
        ];
    }

    /**
     * Get hero secondary button variants
     */
    protected static function getHeroSecondaryButtonVariantOptions(): array
    {
        return [
            'btn-ghost text-white hover:bg-white/20' => 'Ghost (Light)',
            'btn-outline border-white text-white hover:bg-white hover:text-base-content' => 'White Outline',
            'btn-link text-white' => 'Link (Light)',
            'btn-outline btn-primary' => 'Primary Outline',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the hero section with heading, subheading, and background image')
            ->schema([
                Tabs::make('Hero Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                RichEditor::make('heading')
                                    ->maxLength(255)
                                    ->placeholder('Enter hero heading')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'textColor',
                                    ])
                                    ->textColors([
                                        'text-primary' => 'Primary',
                                        'text-secondary' => 'Secondary',
                                        'text-accent' => 'Accent',
                                        'text-info' => 'Info',
                                        'text-success' => 'Success',
                                        'text-warning' => 'Warning',
                                        'text-error' => 'Error',
                                        'text-white' => 'White',
                                    ]),
                                RichEditor::make('subheading')
                                    ->maxLength(500)
                                    ->placeholder('Enter hero subheading or description')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'textColor',
                                    ])
                                    ->textColors([
                                        'text-primary' => 'Primary',
                                        'text-secondary' => 'Secondary',
                                        'text-accent' => 'Accent',
                                        'text-info' => 'Info',
                                        'text-success' => 'Success',
                                        'text-warning' => 'Warning',
                                        'text-error' => 'Error',
                                        'text-white' => 'White',
                                    ]),

                                Section::make('Primary Button')
                                    ->schema([
                                        TextInput::make('button_text')
                                            ->maxLength(100)
                                            ->placeholder('Call to action button text')
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('button_link_type')
                                            ->label('Link Type')
                                            ->options([
                                                'page' => 'Page',
                                                'external' => 'External URL',
                                                'custom' => 'Custom URL',
                                            ])
                                            ->default('page')
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('button_page_id')
                                            ->label('Select Page')
                                            ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                                            ->searchable()
                                            ->visible(fn (Get $get): bool => $get('button_link_type') === 'page')
                                            ->columnSpanFull(),

                                        TextInput::make('button_url')
                                            ->label('URL')
                                            ->placeholder('https://example.com or /contact')
                                            ->visible(fn (Get $get): bool => in_array($get('button_link_type'), ['external', 'custom']))
                                            ->columnSpanFull(),

                                        TextInput::make('button_microcopy')
                                            ->label('Microcopy')
                                            ->maxLength(50)
                                            ->placeholder('e.g., No terminal required')
                                            ->helperText('Small supporting text below button to reduce hesitation')
                                            ->visible(fn (Get $get): bool => filled($get('button_text')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->compact(),

                                Section::make('Secondary Button (Optional)')
                                    ->schema([
                                        TextInput::make('secondary_button_text')
                                            ->label('Button Text')
                                            ->maxLength(100)
                                            ->placeholder('Learn More')
                                            ->live()
                                            ->columnSpan(1),

                                        Select::make('secondary_button_link_type')
                                            ->label('Link Type')
                                            ->options([
                                                'page' => 'Page',
                                                'external' => 'External URL',
                                                'custom' => 'Custom URL',
                                            ])
                                            ->default('page')
                                            ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                            ->columnSpan(1),

                                        Select::make('secondary_button_page_id')
                                            ->label('Select Page')
                                            ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                                            ->searchable()
                                            ->visible(fn (Get $get): bool => $get('secondary_button_link_type') === 'page' && filled($get('secondary_button_text')))
                                            ->columnSpanFull(),

                                        TextInput::make('secondary_button_url')
                                            ->label('URL')
                                            ->placeholder('https://example.com')
                                            ->visible(fn (Get $get): bool => in_array($get('secondary_button_link_type'), ['external', 'custom']) && filled($get('secondary_button_text')))
                                            ->columnSpanFull(),

                                        TextInput::make('secondary_button_microcopy')
                                            ->label('Microcopy')
                                            ->maxLength(50)
                                            ->placeholder('e.g., Open source on GitHub')
                                            ->helperText('Small supporting text below button')
                                            ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->compact()
                                    ->collapsible(),
                            ]),

                        Tab::make('Background')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('background_image')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->directory('cms/hero-blocks')
                                    ->disk(\cms_media_disk())
                                    ->visibility(\cms_media_visibility())
                                    ->nullable()
                                    ->helperText('Recommended: 2560×1440px (16:9). Keep focal point centered. Max 5MB.'),

                                Select::make('height')
                                    ->label('Section Height')
                                    ->options([
                                        'min-h-[50vh]' => 'Small (50vh)',
                                        'min-h-[70vh]' => 'Medium (70vh)',
                                        'min-h-[90vh]' => 'Large (90vh)',
                                        'min-h-screen' => 'Full screen',
                                    ])
                                    ->default('min-h-[70vh]'),

                                Toggle::make('parallax_effect')
                                    ->label('Enable Parallax Effect')
                                    ->default(true)
                                    ->helperText('Background moves slower than content when scrolling'),

                                Slider::make('overlay_opacity')
                                    ->label('Overlay Darkness')
                                    ->range(minValue: 0, maxValue: 100)
                                    ->step(5)
                                    ->tooltips(true)
                                    ->pips(PipsMode::Positions)
                                    ->pipsValues([0, 25, 50, 75, 100])
                                    ->fillTrack()
                                    ->helperText('Controls dark overlay on background (0% = none, 100% = full dark)'),
                            ]),

                        Tab::make('Styling')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make('Button Styles')
                                    ->description('Choose button styles for the hero section')
                                    ->schema([
                                        Select::make('button_variant')
                                            ->label('Primary Button')
                                            ->options(static::getHeroButtonVariantOptions())
                                            ->default('btn-primary'),

                                        Select::make('secondary_button_variant')
                                            ->label('Secondary Button')
                                            ->options(static::getHeroSecondaryButtonVariantOptions())
                                            ->default('btn-ghost text-white hover:bg-white/20'),

                                        Select::make('button_size')
                                            ->label('Button Size')
                                            ->options(static::getButtonSizeOptions())
                                            ->default('btn-lg'),
                                    ])
                                    ->columns(3),

                                Section::make('Text Alignment')
                                    ->schema([
                                        Select::make('text_alignment')
                                            ->label('Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ]),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock($config, true);
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config, false);
    }

    protected static function renderBlock(array $config, bool $isPreview = false): string
    {
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        $secondaryButtonUrl = BlockLinkResolver::resolveButtonUrl($config, 'secondary_button');

        // Build button classes
        $buttonVariant = $config['button_variant'] ?? 'btn-primary';
        $buttonSize = $config['button_size'] ?? 'btn-lg';
        $buttonClasses = "btn {$buttonVariant} {$buttonSize}";

        $secondaryVariant = $config['secondary_button_variant'] ?? 'btn-ghost text-white hover:bg-white/20';
        $secondaryClasses = "btn {$secondaryVariant} {$buttonSize}";

        return view('tallcms::cms.blocks.hero', [
            'id' => static::getId(),
            'isPreview' => $isPreview,
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $buttonUrl,
            'button_classes' => $buttonClasses,
            'button_microcopy' => $config['button_microcopy'] ?? null,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'secondary_button_classes' => $secondaryClasses,
            'secondary_button_microcopy' => $config['secondary_button_microcopy'] ?? null,
            'background_image' => $config['background_image'] ?? null,
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => ($config['overlay_opacity'] ?? 40) / 100,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'height' => $config['height'] ?? 'min-h-[70vh]',
        ])->render();
    }
}
