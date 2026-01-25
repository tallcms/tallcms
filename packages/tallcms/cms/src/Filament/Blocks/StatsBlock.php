<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class StatsBlock extends RichContentCustomBlock
{
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'social-proof';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getDescription(): string
    {
        return 'Key metrics and statistics display';
    }

    public static function getKeywords(): array
    {
        return ['numbers', 'metrics', 'statistics', 'stats'];
    }

    public static function getSortPriority(): int
    {
        return 40;
    }

    public static function getId(): string
    {
        return 'stats';
    }

    public static function getLabel(): string
    {
        return 'Stats';
    }

    protected static function getStatsStyleOptions(): array
    {
        return [
            'stat' => 'Minimal',
            'stat bg-base-200 rounded-xl shadow-lg' => 'Cards with Shadow',
            'stat bg-base-100 rounded-xl border border-base-300' => 'Bordered',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Display key metrics and statistics with optional count-up animation')
            ->modalHeading('Configure Stats Block')
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('Stats Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('Our Impact')
                                    ->maxLength(255),

                                Repeater::make('stats')
                                    ->label('Statistics')
                                    ->schema([
                                        TextInput::make('value')
                                            ->label('Value')
                                            ->required()
                                            ->placeholder('10000')
                                            ->helperText('The numeric value (numbers only for animation)')
                                            ->maxLength(50),

                                        TextInput::make('label')
                                            ->label('Label')
                                            ->required()
                                            ->placeholder('Happy Customers')
                                            ->maxLength(100),

                                        TextInput::make('prefix')
                                            ->label('Prefix')
                                            ->placeholder('$')
                                            ->maxLength(10),

                                        TextInput::make('suffix')
                                            ->label('Suffix')
                                            ->placeholder('+')
                                            ->maxLength(10),

                                        Select::make('icon')
                                            ->label('Icon (Optional)')
                                            ->searchable()
                                            ->options([
                                                '' => 'No Icon',
                                                'heroicon-o-users' => 'Users',
                                                'heroicon-o-currency-dollar' => 'Currency',
                                                'heroicon-o-chart-bar' => 'Chart',
                                                'heroicon-o-globe-alt' => 'Globe',
                                                'heroicon-o-building-office' => 'Building',
                                                'heroicon-o-clock' => 'Clock',
                                                'heroicon-o-star' => 'Star',
                                                'heroicon-o-heart' => 'Heart',
                                                'heroicon-o-trophy' => 'Trophy',
                                                'heroicon-o-rocket-launch' => 'Rocket',
                                                'heroicon-o-check-badge' => 'Badge',
                                                'heroicon-o-document-text' => 'Document',
                                                'heroicon-o-shopping-cart' => 'Cart',
                                                'heroicon-o-calendar' => 'Calendar',
                                                'heroicon-o-map-pin' => 'Location',
                                            ]),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(4)
                                    ->minItems(2)
                                    ->maxItems(8)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'New Stat')
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
                                            ->default('4'),

                                        Select::make('stat_style')
                                            ->label('Stat Style')
                                            ->options(static::getStatsStyleOptions())
                                            ->default('stat'),

                                        Select::make('text_alignment')
                                            ->label('Text Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(3),

                                Section::make('Appearance')
                                    ->schema([
                                        Select::make('background')
                                            ->label('Background')
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('padding')
                                            ->label('Section Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(2),

                                Section::make('Animation & Spacing')
                                    ->schema([
                                        Toggle::make('animate')
                                            ->label('Count-Up Animation')
                                            ->helperText('Animate numbers when scrolling into view. Respects prefers-reduced-motion.')
                                            ->default(false),

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
        $stats = $config['stats'] ?? self::getSampleStats();

        return static::renderBlock(array_merge($config, ['stats' => $stats]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('tallcms::cms.blocks.stats', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'stats' => $config['stats'] ?? [],
            'columns' => $config['columns'] ?? '4',
            'stat_style' => $config['stat_style'] ?? 'stat',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'animate' => $config['animate'] ?? false,
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }

    private static function getSampleStats(): array
    {
        return [
            [
                'value' => '10000',
                'label' => 'Happy Customers',
                'suffix' => '+',
                'icon' => 'heroicon-o-users',
            ],
            [
                'value' => '50',
                'label' => 'Countries Served',
                'icon' => 'heroicon-o-globe-alt',
            ],
            [
                'value' => '99',
                'label' => 'Uptime',
                'suffix' => '%',
                'icon' => 'heroicon-o-chart-bar',
            ],
            [
                'value' => '24',
                'label' => 'Hour Support',
                'suffix' => '/7',
                'icon' => 'heroicon-o-clock',
            ],
        ];
    }
}
