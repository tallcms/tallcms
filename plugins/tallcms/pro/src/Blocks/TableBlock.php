<?php

namespace Tallcms\Pro\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Tallcms\Pro\Traits\RequiresLicense;

class TableBlock extends RichContentCustomBlock
{
    use RequiresLicense;
    public static function getId(): string
    {
        return 'pro-table';
    }

    public static function getLabel(): string
    {
        return 'Table (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create data tables with headers and rows')
            ->modalHeading('Configure Table Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Pricing Comparison'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Compare our plans side by side')
                            ->rows(2),
                    ]),

                Section::make('Table Headers')
                    ->schema([
                        Repeater::make('headers')
                            ->label('Column Headers')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Header Label')
                                    ->required(),

                                Select::make('align')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('left'),
                            ])
                            ->columns(2)
                            ->defaultItems(3)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? 'Column'),
                    ]),

                Section::make('Table Rows')
                    ->schema([
                        Repeater::make('rows')
                            ->label('Data Rows')
                            ->schema([
                                Repeater::make('cells')
                                    ->label('Cells')
                                    ->schema([
                                        TextInput::make('value')
                                            ->label('Cell Value')
                                            ->required(),
                                    ])
                                    ->defaultItems(3)
                                    ->grid(3),

                                Toggle::make('highlight')
                                    ->label('Highlight this row')
                                    ->default(false),
                            ])
                            ->defaultItems(3)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['cells'][0]['value'] ?? 'Row'),
                    ]),

                Section::make('Options')
                    ->schema([
                        Toggle::make('striped')
                            ->label('Striped Rows')
                            ->default(true),

                        Toggle::make('bordered')
                            ->label('Show Borders')
                            ->default(true),

                        Toggle::make('hover')
                            ->label('Hover Effect')
                            ->default(true),

                        Toggle::make('responsive')
                            ->label('Responsive (scroll on mobile)')
                            ->default(true),
                    ])
                    ->columns(4),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.table', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'headers' => $config['headers'] ?? [],
            'rows' => $config['rows'] ?? [],
            'striped' => $config['striped'] ?? true,
            'bordered' => $config['bordered'] ?? true,
            'hover' => $config['hover'] ?? true,
            'responsive' => $config['responsive'] ?? true,
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.table', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'headers' => $config['headers'] ?? [],
            'rows' => $config['rows'] ?? [],
            'striped' => $config['striped'] ?? true,
            'bordered' => $config['bordered'] ?? true,
            'hover' => $config['hover'] ?? true,
            'responsive' => $config['responsive'] ?? true,
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
