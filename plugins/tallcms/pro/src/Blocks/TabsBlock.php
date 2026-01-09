<?php

namespace Tallcms\Pro\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Tallcms\Pro\Traits\RequiresLicense;

class TabsBlock extends RichContentCustomBlock
{
    use RequiresLicense;
    public static function getId(): string
    {
        return 'pro-tabs';
    }

    public static function getLabel(): string
    {
        return 'Tabs (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create tabbed content sections')
            ->modalHeading('Configure Tabs Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Our Services'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Explore what we offer')
                            ->rows(2),
                    ]),

                Section::make('Tabs')
                    ->schema([
                        Repeater::make('tabs')
                            ->label('')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Tab Title')
                                    ->required(),

                                TextInput::make('icon')
                                    ->label('Icon (Heroicon)')
                                    ->placeholder('heroicon-o-star')
                                    ->helperText('Optional Heroicon class name'),

                                RichEditor::make('content')
                                    ->label('Tab Content')
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                    ]),
                            ])
                            ->defaultItems(3)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['title'] ?? 'New Tab'),
                    ]),

                Section::make('Options')
                    ->schema([
                        Select::make('layout')
                            ->label('Tab Layout')
                            ->options([
                                'horizontal' => 'Horizontal',
                                'vertical' => 'Vertical',
                            ])
                            ->default('horizontal'),

                        Select::make('style')
                            ->label('Style')
                            ->options([
                                'pills' => 'Pills',
                                'underline' => 'Underline',
                                'boxed' => 'Boxed',
                            ])
                            ->default('pills'),

                        Select::make('alignment')
                            ->label('Tab Alignment')
                            ->options([
                                'left' => 'Left',
                                'center' => 'Center',
                                'right' => 'Right',
                                'full' => 'Full Width',
                            ])
                            ->default('left'),
                    ])
                    ->columns(3),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.tabs', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'tabs' => $config['tabs'] ?? [],
            'layout' => $config['layout'] ?? 'horizontal',
            'style' => $config['style'] ?? 'pills',
            'alignment' => $config['alignment'] ?? 'left',
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.tabs', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'tabs' => $config['tabs'] ?? [],
            'layout' => $config['layout'] ?? 'horizontal',
            'style' => $config['style'] ?? 'pills',
            'alignment' => $config['alignment'] ?? 'left',
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
