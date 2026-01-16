<?php

namespace TallCms\Cms\Filament\Blocks;

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

class FaqBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;
    public static function getId(): string
    {
        return 'faq';
    }

    public static function getLabel(): string
    {
        return 'FAQ';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create a frequently asked questions section with accordion functionality')
            ->modalHeading('Configure FAQ Block')
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('FAQ Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('Frequently Asked Questions')
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label('Section Subheading')
                                    ->placeholder('Find answers to common questions')
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('items')
                                    ->label('Questions & Answers')
                                    ->schema([
                                        TextInput::make('question')
                                            ->label('Question')
                                            ->required()
                                            ->placeholder('What is your return policy?')
                                            ->maxLength(500),

                                        Textarea::make('answer')
                                            ->label('Answer')
                                            ->required()
                                            ->placeholder('Our return policy allows returns within 30 days...')
                                            ->rows(4),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(20)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'New Question')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make('Settings')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Section::make('Display Options')
                                    ->schema([
                                        Select::make('style')
                                            ->label('Display Style')
                                            ->options([
                                                'accordion' => 'Accordion (Collapsible)',
                                                'list' => 'List (Always Visible)',
                                            ])
                                            ->default('accordion'),

                                        Toggle::make('first_open')
                                            ->label('First Item Open by Default')
                                            ->helperText('Only applies to accordion style')
                                            ->default(false),

                                        Toggle::make('allow_multiple')
                                            ->label('Allow Multiple Items Open')
                                            ->helperText('Only applies to accordion style')
                                            ->default(false),

                                        Select::make('text_alignment')
                                            ->label('Header Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),
                                    ])
                                    ->columns(2),

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

                                Section::make('SEO')
                                    ->schema([
                                        Toggle::make('show_schema')
                                            ->label('Add FAQ Schema Markup')
                                            ->helperText('Adds schema.org FAQPage structured data for SEO')
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
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $items = $config['items'] ?? self::getSampleItems();

        return static::renderBlock(array_merge($config, [
            'items' => $items,
            'heading' => $config['heading'] ?? 'Frequently Asked Questions',
            'subheading' => $config['subheading'] ?? 'Find answers to common questions about our products and services',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('cms.blocks.faq', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'items' => $config['items'] ?? [],
            'style' => $config['style'] ?? 'accordion',
            'first_open' => $config['first_open'] ?? false,
            'allow_multiple' => $config['allow_multiple'] ?? false,
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'show_schema' => $config['show_schema'] ?? true,
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }

    private static function getSampleItems(): array
    {
        return [
            [
                'question' => 'What is your return policy?',
                'answer' => 'We offer a 30-day money-back guarantee on all purchases. If you\'re not satisfied, simply contact our support team for a full refund.',
            ],
            [
                'question' => 'How long does shipping take?',
                'answer' => 'Standard shipping takes 5-7 business days. Express shipping is available for 2-3 business day delivery.',
            ],
            [
                'question' => 'Do you offer customer support?',
                'answer' => 'Yes! Our support team is available 24/7 via email and live chat. We typically respond within 2 hours.',
            ],
        ];
    }
}
