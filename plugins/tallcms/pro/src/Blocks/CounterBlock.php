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

class CounterBlock extends RichContentCustomBlock
{
    use RequiresLicense;
    public static function getId(): string
    {
        return 'pro-counter';
    }

    public static function getLabel(): string
    {
        return 'Counter (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Animated number counters with labels')
            ->modalHeading('Configure Counter Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Our Impact'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Numbers that speak for themselves')
                            ->rows(2),
                    ]),

                Section::make('Counters')
                    ->schema([
                        Repeater::make('counters')
                            ->label('')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Number Value')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('1000'),

                                TextInput::make('prefix')
                                    ->label('Prefix')
                                    ->placeholder('$'),

                                TextInput::make('suffix')
                                    ->label('Suffix')
                                    ->placeholder('+'),

                                TextInput::make('label')
                                    ->label('Label')
                                    ->required()
                                    ->placeholder('Happy Customers'),

                                TextInput::make('description')
                                    ->label('Description')
                                    ->placeholder('And growing every day'),
                            ])
                            ->columns(2)
                            ->defaultItems(4)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => ($state['prefix'] ?? '') . ($state['value'] ?? '0') . ($state['suffix'] ?? '') . ' - ' . ($state['label'] ?? 'Counter')),
                    ]),

                Section::make('Options')
                    ->schema([
                        Select::make('columns')
                            ->label('Columns')
                            ->options([
                                '2' => '2 Columns',
                                '3' => '3 Columns',
                                '4' => '4 Columns',
                            ])
                            ->default('4'),

                        Select::make('style')
                            ->label('Style')
                            ->options([
                                'default' => 'Default',
                                'cards' => 'Cards',
                                'minimal' => 'Minimal',
                            ])
                            ->default('default'),

                        TextInput::make('duration')
                            ->label('Animation Duration (ms)')
                            ->numeric()
                            ->default(2000)
                            ->helperText('Time for counter animation'),
                    ])
                    ->columns(3),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.counter', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'counters' => $config['counters'] ?? [],
            'columns' => $config['columns'] ?? '4',
            'style' => $config['style'] ?? 'default',
            'duration' => $config['duration'] ?? 2000,
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.counter', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'counters' => $config['counters'] ?? [],
            'columns' => $config['columns'] ?? '4',
            'style' => $config['style'] ?? 'default',
            'duration' => $config['duration'] ?? 2000,
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
