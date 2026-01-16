<?php

namespace TallCms\Cms\Filament\Blocks;

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

class TestimonialsBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;

    protected static function getTestimonialCardStyleOptions(): array
    {
        return [
            'card bg-base-200 shadow-lg' => 'Cards with Shadow',
            'card bg-base-100 border border-base-300' => 'Bordered Cards',
            'card bg-base-100/50' => 'Minimal',
            'card bg-base-200 shadow-lg quote-marks' => 'Large Quote Marks',
        ];
    }

    public static function getId(): string
    {
        return 'testimonials';
    }

    public static function getLabel(): string
    {
        return 'Testimonials';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Display customer testimonials and social proof')
            ->modalHeading('Configure Testimonials Block')
            ->modalWidth('6xl')
            ->schema([
                Tabs::make('Testimonials Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('What Our Customers Say')
                                    ->maxLength(255),

                                Textarea::make('subheading')
                                    ->label('Section Subheading')
                                    ->placeholder('Hear from people who love our product')
                                    ->maxLength(500)
                                    ->rows(2),

                                Repeater::make('testimonials')
                                    ->label('Testimonials')
                                    ->schema([
                                        Textarea::make('quote')
                                            ->label('Testimonial Quote')
                                            ->required()
                                            ->placeholder('This product has completely transformed how we work...')
                                            ->rows(3),

                                        TextInput::make('author_name')
                                            ->label('Author Name')
                                            ->required()
                                            ->placeholder('Jane Smith')
                                            ->maxLength(100),

                                        TextInput::make('author_title')
                                            ->label('Title / Company')
                                            ->placeholder('CEO at Acme Inc.')
                                            ->maxLength(150),

                                        FileUpload::make('author_image')
                                            ->label('Author Photo')
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('testimonials')
                                            ->visibility(\cms_media_visibility())
                                            ->imageEditor()
                                            ->circleCropper(),

                                        FileUpload::make('company_logo')
                                            ->label('Company Logo (Optional)')
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('testimonials/logos')
                                            ->visibility(\cms_media_visibility()),

                                        Select::make('rating')
                                            ->label('Star Rating')
                                            ->options([
                                                '' => 'No Rating',
                                                '5' => '5 Stars',
                                                '4' => '4 Stars',
                                                '3' => '3 Stars',
                                                '2' => '2 Stars',
                                                '1' => '1 Star',
                                            ])
                                            ->default('5'),
                                    ])
                                    ->defaultItems(3)
                                    ->minItems(1)
                                    ->maxItems(12)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['author_name'] ?? 'New Testimonial')
                                    ->reorderableWithButtons(),
                            ]),

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Section::make('Display Options')
                                    ->schema([
                                        Select::make('layout')
                                            ->label('Layout')
                                            ->options([
                                                'grid' => 'Grid',
                                                'single' => 'Single (Large)',
                                            ])
                                            ->default('grid'),

                                        Select::make('columns')
                                            ->label('Columns (Grid Layout)')
                                            ->options([
                                                '1' => '1 Column',
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                            ])
                                            ->default('3'),

                                        Select::make('card_style')
                                            ->label('Card Style')
                                            ->options(static::getTestimonialCardStyleOptions())
                                            ->default('card bg-base-200 shadow-lg'),

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

                                Section::make('Content Display')
                                    ->schema([
                                        Toggle::make('show_rating')
                                            ->label('Show Star Ratings')
                                            ->default(true),

                                        Toggle::make('show_company_logo')
                                            ->label('Show Company Logos')
                                            ->default(false),

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Padding)')
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ])
                                    ->columns(3),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $testimonials = $config['testimonials'] ?? self::getSampleTestimonials();

        return static::renderBlock(array_merge($config, [
            'testimonials' => $testimonials,
            'heading' => $config['heading'] ?? 'What Our Customers Say',
            'subheading' => $config['subheading'] ?? 'Trusted by thousands of happy customers worldwide',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('tallcms::cms.blocks.testimonials', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'testimonials' => $config['testimonials'] ?? [],
            'layout' => $config['layout'] ?? 'grid',
            'columns' => $config['columns'] ?? '3',
            'card_style' => $config['card_style'] ?? 'card bg-base-200 shadow-lg',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'show_rating' => $config['show_rating'] ?? true,
            'show_company_logo' => $config['show_company_logo'] ?? false,
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }

    private static function getSampleTestimonials(): array
    {
        return [
            [
                'quote' => 'This product has completely transformed how we work. The team is more productive than ever.',
                'author_name' => 'Sarah Johnson',
                'author_title' => 'CEO at TechCorp',
                'rating' => '5',
            ],
            [
                'quote' => 'Incredible support and an intuitive interface. We saw results from day one.',
                'author_name' => 'Michael Chen',
                'author_title' => 'Product Manager at StartupXYZ',
                'rating' => '5',
            ],
            [
                'quote' => 'The best investment we\'ve made this year. Highly recommend to any growing business.',
                'author_name' => 'Emily Rodriguez',
                'author_title' => 'Founder of GrowthLabs',
                'rating' => '5',
            ],
        ];
    }
}
