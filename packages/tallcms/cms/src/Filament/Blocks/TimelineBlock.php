<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
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

class TimelineBlock extends RichContentCustomBlock
{
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'dynamic';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public static function getDescription(): string
    {
        return 'Chronological events or milestones';
    }

    public static function getKeywords(): array
    {
        return ['history', 'events', 'timeline', 'chronology'];
    }

    public static function getSortPriority(): int
    {
        return 30;
    }

    public static function getId(): string
    {
        return 'timeline';
    }

    public static function getLabel(): string
    {
        return 'Timeline';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Display chronological events, process steps, or milestones')
            ->modalHeading('Configure Timeline Block')
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Timeline Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('Our Journey')
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label('Section Subheading')
                                    ->placeholder('Key milestones in our history')
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('items')
                                    ->label('Timeline Items')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Title')
                                            ->required()
                                            ->placeholder('Company Founded')
                                            ->maxLength(200),

                                        Textarea::make('description')
                                            ->label('Description')
                                            ->placeholder('Describe this milestone or step...')
                                            ->maxLength(1000)
                                            ->rows(3),

                                        TextInput::make('date')
                                            ->label('Date / Step Label')
                                            ->placeholder('2020 or Step 1')
                                            ->maxLength(50),

                                        Select::make('icon')
                                            ->label('Icon (Optional)')
                                            ->searchable()
                                            ->options([
                                                '' => 'No Icon',
                                                'heroicon-o-flag' => 'Flag',
                                                'heroicon-o-rocket-launch' => 'Rocket',
                                                'heroicon-o-star' => 'Star',
                                                'heroicon-o-check-circle' => 'Check Circle',
                                                'heroicon-o-light-bulb' => 'Light Bulb',
                                                'heroicon-o-trophy' => 'Trophy',
                                                'heroicon-o-academic-cap' => 'Academic Cap',
                                                'heroicon-o-building-office' => 'Building',
                                                'heroicon-o-users' => 'Users',
                                                'heroicon-o-chart-bar' => 'Chart',
                                                'heroicon-o-globe-alt' => 'Globe',
                                                'heroicon-o-heart' => 'Heart',
                                                'heroicon-o-sparkles' => 'Sparkles',
                                                'heroicon-o-calendar' => 'Calendar',
                                                'heroicon-o-map-pin' => 'Location',
                                            ]),

                                        FileUpload::make('image')
                                            ->label('Image (Optional)')
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('timeline')
                                            ->visibility(\cms_media_visibility())
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('16:9')
                                            ->imageResizeTargetWidth('800')
                                            ->imageResizeTargetHeight('450'),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(2)
                                    ->maxItems(20)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Item')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make('Display Options')
                                    ->schema([
                                        Select::make('style')
                                            ->label('Layout Style')
                                            ->options([
                                                'vertical' => 'Vertical',
                                                'horizontal' => 'Horizontal',
                                            ])
                                            ->default('vertical')
                                            ->live(),

                                        Toggle::make('alternating')
                                            ->label('Alternating Layout')
                                            ->helperText('Alternate items left and right (vertical only)')
                                            ->default(true),

                                        Toggle::make('show_connector')
                                            ->label('Show Connecting Line')
                                            ->default(true),

                                        Toggle::make('numbered')
                                            ->label('Show Step Numbers')
                                            ->helperText('Display numbers instead of icons')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Section::make('Appearance')
                                    ->schema([
                                        Select::make('text_alignment')
                                            ->label('Header Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),

                                        Select::make('background')
                                            ->label('Background')
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('padding')
                                            ->label('Section Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Padding)')
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $items = $config['items'] ?? self::getSampleItems();

        return static::renderBlock(array_merge($config, [
            'items' => $items,
            'heading' => $config['heading'] ?? 'Our Journey',
            'subheading' => $config['subheading'] ?? 'Key milestones that shaped who we are today',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('tallcms::cms.blocks.timeline', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'items' => $config['items'] ?? [],
            'style' => $config['style'] ?? 'vertical',
            'alternating' => $config['alternating'] ?? true,
            'show_connector' => $config['show_connector'] ?? true,
            'numbered' => $config['numbered'] ?? false,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }

    private static function getSampleItems(): array
    {
        return [
            [
                'title' => 'Company Founded',
                'description' => 'Started with a vision to transform the industry and create meaningful impact.',
                'date' => '2018',
                'icon' => 'heroicon-o-flag',
            ],
            [
                'title' => 'First Major Milestone',
                'description' => 'Reached 10,000 customers and expanded our team to 50 employees.',
                'date' => '2020',
                'icon' => 'heroicon-o-rocket-launch',
            ],
            [
                'title' => 'Global Expansion',
                'description' => 'Opened offices in 5 countries and launched in 20 new markets.',
                'date' => '2022',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'title' => 'Industry Recognition',
                'description' => 'Won multiple awards and became a leader in our space.',
                'date' => '2024',
                'icon' => 'heroicon-o-trophy',
            ],
        ];
    }
}
