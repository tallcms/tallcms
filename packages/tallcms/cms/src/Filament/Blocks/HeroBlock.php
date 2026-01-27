<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Services\BlockLinkResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class HeroBlock extends RichContentCustomBlock
{
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-home';
    }

    public static function getDescription(): string
    {
        return 'Full-width hero section with background image';
    }

    public static function getKeywords(): array
    {
        return ['banner', 'header', 'landing', 'hero'];
    }

    public static function getSortPriority(): int
    {
        return 5;
    }

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

    /**
     * Get layout variant options
     */
    protected static function getLayoutOptions(): array
    {
        return [
            'centered' => 'Centered',
            'figure-left' => 'With Figure (Left)',
            'figure-right' => 'With Figure (Right)',
            'with-form' => 'With Form',
        ];
    }

    /**
     * Get form card style options
     */
    protected static function getFormCardStyleOptions(): array
    {
        return [
            'bg-base-100 shadow-2xl' => 'White with Large Shadow',
            'bg-base-100 shadow-md' => 'White with Shadow',
            'bg-base-200' => 'Subtle Background',
        ];
    }

    /**
     * Get hero background options (including gradient)
     */
    protected static function getHeroBackgroundOptions(): array
    {
        return [
            'bg-gradient-to-br from-primary to-secondary' => 'Primary to Secondary Gradient',
            'bg-base-200' => 'Base Subtle',
            'bg-base-300' => 'Base Strong',
            'bg-primary' => 'Primary',
            'bg-secondary' => 'Secondary',
            'bg-accent' => 'Accent',
            'bg-neutral' => 'Neutral',
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
                                            ->live()
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

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('layout')
                                    ->label('Layout Variant')
                                    ->options(static::getLayoutOptions())
                                    ->default('centered')
                                    ->live()
                                    ->helperText('Choose how the hero content is arranged'),

                                Select::make('height')
                                    ->label('Section Height')
                                    ->options([
                                        'min-h-[50vh]' => 'Small (50vh)',
                                        'min-h-[70vh]' => 'Medium (70vh)',
                                        'min-h-[90vh]' => 'Large (90vh)',
                                        'min-h-screen' => 'Full screen',
                                    ])
                                    ->default('min-h-[70vh]'),

                                Select::make('text_alignment')
                                    ->label('Text Alignment')
                                    ->options(static::getTextAlignmentOptions())
                                    ->default('text-center'),

                                Section::make('Figure Image')
                                    ->description('Image displayed alongside content')
                                    ->schema([
                                        FileUpload::make('figure_image')
                                            ->image()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(5120)
                                            ->directory('cms/hero-blocks')
                                            ->disk(\cms_media_disk())
                                            ->visibility(\cms_media_visibility())
                                            ->nullable()
                                            ->helperText('Recommended: 800×600px. Max 5MB.'),

                                        TextInput::make('figure_alt')
                                            ->label('Alt Text')
                                            ->maxLength(255)
                                            ->placeholder('Describe the image for accessibility')
                                            ->required(fn (Get $get) => filled($get('figure_image'))),

                                        Toggle::make('figure_rounded')
                                            ->label('Rounded Corners')
                                            ->default(true),

                                        Toggle::make('figure_shadow')
                                            ->label('Drop Shadow')
                                            ->default(true),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (Get $get): bool => in_array($get('layout'), ['figure-left', 'figure-right'])),

                                Section::make('Form Settings')
                                    ->description('Configure the lead capture form')
                                    ->schema([
                                        TextInput::make('form_title')
                                            ->label('Form Card Title')
                                            ->maxLength(100)
                                            ->placeholder('e.g., Get Started Today'),

                                        Repeater::make('form_fields')
                                            ->label('Form Fields')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Field Type')
                                                    ->options([
                                                        'text' => 'Text',
                                                        'email' => 'Email',
                                                        'tel' => 'Phone',
                                                        'textarea' => 'Text Area',
                                                        'select' => 'Dropdown',
                                                    ])
                                                    ->required()
                                                    ->live(),

                                                TextInput::make('name')
                                                    ->label('Field Name')
                                                    ->required()
                                                    ->alphaNum()
                                                    ->maxLength(50)
                                                    ->distinct()
                                                    ->helperText('Unique identifier (no spaces)'),

                                                TextInput::make('label')
                                                    ->label('Display Label')
                                                    ->required()
                                                    ->maxLength(255),

                                                Toggle::make('required')
                                                    ->label('Required')
                                                    ->default(false)
                                                    ->inline(false),

                                                TagsInput::make('options')
                                                    ->label('Dropdown Options')
                                                    ->visible(fn (Get $get): bool => $get('type') === 'select')
                                                    ->helperText('Press Enter after each option')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->default(ContactFormBlock::getDefaultFields())
                                            ->minItems(1)
                                            ->maxItems(10)
                                            ->reorderable()
                                            ->reorderableWithDragAndDrop()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? 'Field').' ('.($state['type'] ?? 'text').')')
                                            ->addActionLabel('Add Field'),

                                        TextInput::make('form_submit_text')
                                            ->label('Submit Button Text')
                                            ->default('Get Started')
                                            ->maxLength(50),

                                        Textarea::make('form_success_message')
                                            ->label('Success Message')
                                            ->default("Thanks! We'll be in touch.")
                                            ->maxLength(500),

                                        Select::make('form_button_style')
                                            ->label('Submit Button Style')
                                            ->options(static::getButtonVariantOptions())
                                            ->default('btn-primary'),

                                        Select::make('form_card_style')
                                            ->label('Card Style')
                                            ->options(static::getFormCardStyleOptions())
                                            ->default('bg-base-100 shadow-2xl'),
                                    ])
                                    ->visible(fn (Get $get): bool => $get('layout') === 'with-form'),
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
                                    ->helperText('Recommended: 2560×1440px (16:9). Keep focal point centered. Max 5MB.')
                                    ->live(),

                                Select::make('background_color')
                                    ->label('Background Color')
                                    ->options(static::getHeroBackgroundOptions())
                                    ->default('bg-gradient-to-br from-primary to-secondary')
                                    ->visible(fn (Get $get): bool => empty($get('background_image'))),

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
                            ]),
                    ]),

                static::getIdentifiersSection(),
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

        // Normalize form fields - use ContactFormBlock defaults if empty
        // Use array_values to ensure numeric keys (Repeater may use associative keys)
        // Also check count() because empty array [] is "set" but has no items
        $formFields = isset($config['form_fields']) && is_array($config['form_fields']) && count($config['form_fields']) > 0
            ? array_values($config['form_fields'])
            : ContactFormBlock::getDefaultFields();

        return view('tallcms::cms.blocks.hero', [
            'id' => static::getId(),
            'isPreview' => $isPreview,
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $buttonUrl,
            'button_classes' => $buttonClasses,
            'button_variant' => $buttonVariant,
            'button_size' => $buttonSize,
            'button_microcopy' => $config['button_microcopy'] ?? null,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'secondary_button_classes' => $secondaryClasses,
            'secondary_button_variant' => $secondaryVariant,
            'secondary_button_microcopy' => $config['secondary_button_microcopy'] ?? null,
            'background_image' => $config['background_image'] ?? null,
            'background_color' => $config['background_color'] ?? 'bg-gradient-to-br from-primary to-secondary',
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => ($config['overlay_opacity'] ?? 40) / 100,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'height' => $config['height'] ?? 'min-h-[70vh]',
            'layout' => $config['layout'] ?? 'centered',
            'figure_image' => $config['figure_image'] ?? null,
            'figure_alt' => $config['figure_alt'] ?? '',
            'figure_rounded' => $config['figure_rounded'] ?? true,
            'figure_shadow' => $config['figure_shadow'] ?? true,
            'form_title' => $config['form_title'] ?? null,
            'form_fields' => $formFields,
            'form_submit_text' => $config['form_submit_text'] ?? 'Get Started',
            'form_success_message' => $config['form_success_message'] ?? "Thanks! We'll be in touch.",
            'form_button_style' => $config['form_button_style'] ?? 'btn-primary',
            'form_card_style' => $config['form_card_style'] ?? 'bg-base-100 shadow-2xl',
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }
}
