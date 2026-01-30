<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
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

class PricingBlock extends RichContentCustomBlock
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
        return 'heroicon-o-currency-dollar';
    }

    public static function getDescription(): string
    {
        return 'Pricing table with plans and features';
    }

    public static function getKeywords(): array
    {
        return ['plans', 'pricing', 'tiers', 'subscription'];
    }

    public static function getSortPriority(): int
    {
        return 30;
    }

    public static function getId(): string
    {
        return 'pricing';
    }

    public static function getLabel(): string
    {
        return 'Pricing Block';
    }

    protected static function getPricingCardStyleOptions(): array
    {
        return [
            'shadow' => 'Shadow',
            'bordered' => 'Bordered',
            'elevated' => 'Elevated',
        ];
    }

    protected static function getPlanButtonStyleOptions(): array
    {
        return [
            'btn-primary' => 'Primary',
            'btn-secondary' => 'Secondary',
            'btn-outline btn-primary' => 'Outline Primary',
            'btn-accent' => 'Accent',
            'btn-neutral' => 'Neutral',
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create a comprehensive pricing table with plans, features, and call-to-action buttons')
            ->modalHeading('Configure Pricing Block')
            ->modalWidth('7xl')
            ->schema([
                Tabs::make('Pricing Configuration')
                    ->tabs([
                        Tab::make('Header')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('section_title')
                                    ->label('Section Title')
                                    ->placeholder('Choose Your Plan')
                                    ->maxLength(255),

                                Textarea::make('section_subtitle')
                                    ->label('Section Subtitle')
                                    ->placeholder('Select the perfect plan for your needs')
                                    ->maxLength(500)
                                    ->rows(2),

                                Select::make('text_alignment')
                                    ->label('Text Alignment')
                                    ->options(static::getTextAlignmentOptions())
                                    ->default('text-center'),
                            ]),

                        Tab::make('Plans')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Repeater::make('plans')
                                    ->label('Pricing Plans')
                                    ->schema([
                                        Section::make('Plan Details')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Plan Name')
                                                    ->required()
                                                    ->placeholder('Professional')
                                                    ->maxLength(100),

                                                TextInput::make('description')
                                                    ->label('Plan Description')
                                                    ->placeholder('Perfect for growing teams')
                                                    ->maxLength(200),

                                                Toggle::make('is_popular')
                                                    ->label('Mark as Popular/Recommended')
                                                    ->default(false)
                                                    ->live(),

                                                TextInput::make('popular_badge_text')
                                                    ->label('Popular Badge Text')
                                                    ->placeholder('Most Popular')
                                                    ->maxLength(50)
                                                    ->visible(fn (Get $get): bool => $get('is_popular')),
                                            ])->columns(2),

                                        Section::make('Pricing')
                                            ->schema([
                                                TextInput::make('currency_symbol')
                                                    ->label('Currency Symbol')
                                                    ->default('$')
                                                    ->maxLength(5),

                                                TextInput::make('price')
                                                    ->label('Price')
                                                    ->required()
                                                    ->placeholder('29')
                                                    ->numeric(),

                                                Select::make('billing_period')
                                                    ->label('Billing Period')
                                                    ->options([
                                                        'month' => 'per month',
                                                        'year' => 'per year',
                                                        'week' => 'per week',
                                                        'day' => 'per day',
                                                        'one-time' => 'one-time payment',
                                                        'free' => 'free',
                                                    ])
                                                    ->default('month'),

                                                TextInput::make('discount_text')
                                                    ->label('Discount Text (Optional)')
                                                    ->placeholder('Save 20%')
                                                    ->maxLength(50),
                                            ])->columns(4),

                                        Section::make('Features')
                                            ->schema([
                                                Repeater::make('features')
                                                    ->label('Plan Features')
                                                    ->schema([
                                                        TextInput::make('text')
                                                            ->label('Feature Text')
                                                            ->required()
                                                            ->placeholder('Unlimited projects')
                                                            ->maxLength(200),

                                                        Toggle::make('included')
                                                            ->label('Included')
                                                            ->default(true),

                                                        TextInput::make('tooltip')
                                                            ->label('Tooltip (Optional)')
                                                            ->placeholder('Additional information about this feature')
                                                            ->maxLength(300),
                                                    ])
                                                    ->defaultItems(3)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['text'] ?? null),
                                            ]),

                                        Section::make('Call to Action')
                                            ->schema([
                                                TextInput::make('button_text')
                                                    ->label('Button Text')
                                                    ->required()
                                                    ->placeholder('Get Started')
                                                    ->maxLength(50),

                                                TextInput::make('button_url')
                                                    ->label('Button URL')
                                                    ->placeholder('/signup?plan=professional')
                                                    ->maxLength(500),

                                                Select::make('button_style')
                                                    ->label('Button Style')
                                                    ->options(static::getPlanButtonStyleOptions())
                                                    ->default('btn-primary'),

                                                TextInput::make('trial_text')
                                                    ->label('Trial Text (Optional)')
                                                    ->placeholder('14-day free trial')
                                                    ->maxLength(100),
                                            ])->columns(2),
                                    ])
                                    ->defaultItems(2)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Plan')
                                    ->addActionLabel('Add Plan')
                                    ->minItems(1)
                                    ->maxItems(6),
                            ]),

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make('Grid Layout')
                                    ->schema([
                                        Select::make('columns')
                                            ->label('Number of Columns')
                                            ->options([
                                                '1' => '1 Column',
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                            ])
                                            ->default('3'),

                                        Select::make('card_style')
                                            ->label('Card Style')
                                            ->options(static::getPricingCardStyleOptions())
                                            ->default('shadow'),

                                        Select::make('spacing')
                                            ->label('Card Spacing')
                                            ->options([
                                                'tight' => 'Tight (gap-4)',
                                                'normal' => 'Normal (gap-6)',
                                                'relaxed' => 'Relaxed (gap-8)',
                                            ])
                                            ->default('normal'),
                                    ])
                                    ->columns(3),

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

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Padding)')
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ])
                                    ->columns(4),
                            ]),

                        static::getAnimationTab(supportsStagger: true),
                    ]),

                static::getIdentifiersSection(),
            ])
            ->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $plans = $config['plans'] ?? self::getSamplePlans();

        return static::renderBlock(array_merge($config, [
            'plans' => $plans,
            'section_title' => $config['section_title'] ?? 'Choose Your Plan',
            'section_subtitle' => $config['section_subtitle'] ?? 'Select the perfect plan for your needs',
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

        return view('tallcms::cms.blocks.pricing', [
            'id' => static::getId(),
            'section_title' => $config['section_title'] ?? '',
            'section_subtitle' => $config['section_subtitle'] ?? '',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'plans' => $config['plans'] ?? [],
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'shadow',
            'spacing' => $config['spacing'] ?? 'normal',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['section_title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }

    private static function getSamplePlans(): array
    {
        return [
            [
                'name' => 'Basic',
                'description' => 'Perfect for individuals',
                'currency_symbol' => '$',
                'price' => '9',
                'billing_period' => 'month',
                'is_popular' => false,
                'button_text' => 'Get Started',
                'button_style' => 'btn-outline btn-primary',
                'features' => [
                    ['text' => '5 Projects', 'included' => true],
                    ['text' => '10GB Storage', 'included' => true],
                    ['text' => 'Email Support', 'included' => true],
                ],
            ],
            [
                'name' => 'Professional',
                'description' => 'Perfect for growing teams',
                'currency_symbol' => '$',
                'price' => '29',
                'billing_period' => 'month',
                'is_popular' => true,
                'popular_badge_text' => 'Most Popular',
                'button_text' => 'Get Started',
                'button_style' => 'btn-primary',
                'features' => [
                    ['text' => 'Unlimited Projects', 'included' => true],
                    ['text' => '100GB Storage', 'included' => true],
                    ['text' => 'Priority Support', 'included' => true],
                    ['text' => 'Advanced Analytics', 'included' => true],
                ],
            ],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations',
                'currency_symbol' => '$',
                'price' => '99',
                'billing_period' => 'month',
                'is_popular' => false,
                'button_text' => 'Contact Sales',
                'button_style' => 'btn-secondary',
                'features' => [
                    ['text' => 'Everything in Pro', 'included' => true],
                    ['text' => 'Unlimited Storage', 'included' => true],
                    ['text' => '24/7 Phone Support', 'included' => true],
                    ['text' => 'Custom Integrations', 'included' => true],
                    ['text' => 'Dedicated Manager', 'included' => true],
                ],
            ],
        ];
    }
}
