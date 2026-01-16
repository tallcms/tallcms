<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Services\BlockLinkResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class CallToActionBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;

    public static function getId(): string
    {
        return 'call_to_action';
    }

    public static function getLabel(): string
    {
        return 'Call to action';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create a compelling call to action section')
            ->schema([
                Tabs::make('CTA Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter CTA title'),

                                Textarea::make('description')
                                    ->maxLength(500)
                                    ->placeholder('Enter CTA description'),

                                Section::make('Primary Button')
                                    ->schema([
                                        TextInput::make('button_text')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('Get Started')
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
                                            ->placeholder('e.g., No credit card required')
                                            ->helperText('Short reassurance text below the button')
                                            ->maxLength(100)
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
                                            ->placeholder('e.g., Free to try')
                                            ->helperText('Short reassurance text below the button')
                                            ->maxLength(100)
                                            ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->compact()
                                    ->collapsible(),
                            ]),

                        Tab::make('Styling')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make('Button Styles')
                                    ->description('Choose button styles from daisyUI presets')
                                    ->schema([
                                        Select::make('button_variant')
                                            ->label('Primary Button')
                                            ->options(static::getButtonVariantOptions())
                                            ->default('btn-primary'),

                                        Select::make('button_size')
                                            ->label('Button Size')
                                            ->options(static::getButtonSizeOptions())
                                            ->default('btn-lg'),

                                        Select::make('secondary_button_variant')
                                            ->label('Secondary Button')
                                            ->options(static::getSecondaryButtonVariantOptions())
                                            ->default('btn-ghost'),
                                    ])
                                    ->columns(3),

                                Section::make('Section Appearance')
                                    ->schema([
                                        Select::make('background')
                                            ->label('Background')
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-200'),

                                        Select::make('text_alignment')
                                            ->label('Text Alignment')
                                            ->options(static::getTextAlignmentOptions())
                                            ->default('text-center'),

                                        Select::make('padding')
                                            ->label('Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),
                                    ])
                                    ->columns(3),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock($config);
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        $secondaryButtonUrl = BlockLinkResolver::resolveButtonUrl($config, 'secondary_button');

        return view('tallcms::cms.blocks.call-to-action', [
            'id' => static::getId(),
            'title' => $config['title'] ?? 'Call to Action Title',
            'description' => $config['description'] ?? '',
            'button_text' => $config['button_text'] ?? 'Get Started',
            'button_url' => $buttonUrl,
            'button_classes' => static::buildButtonClasses($config),
            'button_microcopy' => $config['button_microcopy'] ?? null,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'secondary_button_classes' => static::buildButtonClasses($config, 'secondary_button'),
            'secondary_button_microcopy' => $config['secondary_button_microcopy'] ?? null,
            'background' => $config['background'] ?? 'bg-base-200',
            'text_alignment' => $config['text_alignment'] ?? 'text-center',
            'padding' => $config['padding'] ?? 'py-16',
        ])->render();
    }
}
