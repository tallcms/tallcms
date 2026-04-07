<?php

namespace TallCms\Cms\Filament\Blocks;

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
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class HowToBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'dynamic';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-numbered-list';
    }

    public static function getDescription(): string
    {
        return 'Step-by-step instructions with HowTo schema';
    }

    public static function getKeywords(): array
    {
        return ['howto', 'steps', 'instructions', 'tutorial', 'guide'];
    }

    public static function getSortPriority(): int
    {
        return 21;
    }

    public static function getId(): string
    {
        return 'how-to';
    }

    public static function getLabel(): string
    {
        return 'How To';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create step-by-step instructions with HowTo schema markup')
            ->modalHeading('Configure How To Block')
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('How To Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->placeholder('How to...')
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->placeholder('A brief overview of what this guide covers')
                                    ->maxLength(500)
                                    ->rows(2),

                                TextInput::make('total_time')
                                    ->label('Total Time')
                                    ->placeholder('e.g., PT30M or 30 minutes')
                                    ->maxLength(100),

                                TextInput::make('estimated_cost')
                                    ->label('Estimated Cost')
                                    ->placeholder('e.g., 50')
                                    ->maxLength(50),

                                TextInput::make('currency')
                                    ->label('Currency')
                                    ->default('USD')
                                    ->maxLength(10),

                                Repeater::make('steps')
                                    ->label('Steps')
                                    ->schema([
                                        TextInput::make('step_name')
                                            ->label('Step Name')
                                            ->required()
                                            ->placeholder('Step title')
                                            ->maxLength(500),

                                        Textarea::make('step_text')
                                            ->label('Step Description')
                                            ->required()
                                            ->placeholder('Detailed instructions for this step...')
                                            ->rows(3),

                                        FileUpload::make('step_image')
                                            ->label('Step Image')
                                            ->image()
                                            ->directory('cms/blocks/howto')
                                            ->disk(\cms_media_disk())
                                            ->visibility(\cms_media_visibility())
                                            ->nullable(),

                                        TextInput::make('step_url')
                                            ->label('Step URL')
                                            ->url()
                                            ->nullable()
                                            ->placeholder('https://...')
                                            ->maxLength(500),
                                    ])
                                    ->defaultItems(2)
                                    ->minItems(2)
                                    ->maxItems(30)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['step_name'] ?? 'New Step')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make('Settings')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Section::make('Display Options')
                                    ->schema([
                                        Select::make('text_alignment')
                                            ->label('Header Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

                                Section::make('Appearance')
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('background')
                                            ->label('Background')
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('padding')
                                            ->label('Section Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),

                                Section::make('SEO')
                                    ->schema([
                                        Toggle::make('show_schema')
                                            ->label('Add HowTo Schema Markup')
                                            ->helperText('Adds schema.org HowTo structured data for SEO')
                                            ->default(true),
                                    ]),

                                Section::make('Spacing')
                                    ->schema([
                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Padding)')
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ]),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $steps = $config['steps'] ?? self::getSampleSteps();

        return static::renderBlock(array_merge($config, [
            'steps' => $steps,
            'title' => $config['title'] ?? 'How to Get Started',
            'description' => $config['description'] ?? 'Follow these simple steps to get up and running quickly',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.how-to', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'total_time' => $config['total_time'] ?? '',
            'estimated_cost' => $config['estimated_cost'] ?? '',
            'currency' => $config['currency'] ?? 'USD',
            'steps' => $config['steps'] ?? [],
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'show_schema' => $config['show_schema'] ?? true,
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSampleSteps(): array
    {
        return [
            [
                'step_name' => 'Create an account',
                'step_text' => 'Sign up for a free account by clicking the registration button and filling in your details.',
                'step_image' => null,
                'step_url' => null,
            ],
            [
                'step_name' => 'Configure your settings',
                'step_text' => 'Navigate to the settings page and customize your preferences to match your needs.',
                'step_image' => null,
                'step_url' => null,
            ],
            [
                'step_name' => 'Start building',
                'step_text' => 'Use the intuitive editor to create your first page with drag-and-drop blocks.',
                'step_image' => null,
                'step_url' => null,
            ],
        ];
    }
}
