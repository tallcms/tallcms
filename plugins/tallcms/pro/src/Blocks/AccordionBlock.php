<?php

namespace Tallcms\Pro\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Tallcms\Pro\Traits\RequiresLicense;

class AccordionBlock extends RichContentCustomBlock
{
    use RequiresLicense;
    public static function getId(): string
    {
        return 'pro-accordion';
    }

    public static function getLabel(): string
    {
        return 'Accordion (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create collapsible accordion sections')
            ->modalHeading('Configure Accordion Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Frequently Asked Questions'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Find answers to common questions')
                            ->rows(2),
                    ]),

                Section::make('Accordion Items')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->required(),

                                Textarea::make('content')
                                    ->label('Content')
                                    ->required()
                                    ->rows(3),
                            ])
                            ->defaultItems(3)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['title'] ?? 'New Item'),
                    ]),

                Section::make('Options')
                    ->schema([
                        Toggle::make('allow_multiple')
                            ->label('Allow Multiple Open')
                            ->helperText('Allow multiple items to be expanded at once')
                            ->default(false),

                        Toggle::make('first_open')
                            ->label('First Item Open')
                            ->helperText('Automatically expand the first item')
                            ->default(true),

                        Select::make('style')
                            ->label('Style')
                            ->options([
                                'default' => 'Default (Card)',
                                'bordered' => 'Bordered',
                                'minimal' => 'Minimal',
                            ])
                            ->default('default'),
                    ])
                    ->columns(3),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.accordion', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'items' => $config['items'] ?? [],
            'allow_multiple' => $config['allow_multiple'] ?? false,
            'first_open' => $config['first_open'] ?? true,
            'style' => $config['style'] ?? 'default',
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.accordion', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'items' => $config['items'] ?? [],
            'allow_multiple' => $config['allow_multiple'] ?? false,
            'first_open' => $config['first_open'] ?? true,
            'style' => $config['style'] ?? 'default',
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
