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

class ComparisonBlock extends RichContentCustomBlock
{
    use RequiresLicense;
    public static function getId(): string
    {
        return 'pro-comparison';
    }

    public static function getLabel(): string
    {
        return 'Comparison (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Side-by-side feature comparison')
            ->modalHeading('Configure Comparison Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Compare Plans'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Find the plan that fits your needs')
                            ->rows(2),
                    ]),

                Section::make('Options')
                    ->schema([
                        TextInput::make('column_a_title')
                            ->label('Column A Title')
                            ->default('Basic')
                            ->required(),

                        TextInput::make('column_b_title')
                            ->label('Column B Title')
                            ->default('Pro')
                            ->required(),

                        Select::make('style')
                            ->label('Style')
                            ->options([
                                'default' => 'Default',
                                'cards' => 'Cards',
                                'minimal' => 'Minimal',
                            ])
                            ->default('default'),
                    ])
                    ->columns(3),

                Section::make('Comparison Rows')
                    ->schema([
                        Repeater::make('features')
                            ->label('Features')
                            ->schema([
                                TextInput::make('feature')
                                    ->label('Feature Name')
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('column_a')
                                    ->label('Column A')
                                    ->options([
                                        'check' => 'Included',
                                        'x' => 'Not Included',
                                        'partial' => 'Partial',
                                        'custom' => 'Custom Text',
                                    ])
                                    ->default('check')
                                    ->live(),

                                TextInput::make('column_a_text')
                                    ->label('Column A Custom Text')
                                    ->visible(fn($get) => $get('column_a') === 'custom'),

                                Select::make('column_b')
                                    ->label('Column B')
                                    ->options([
                                        'check' => 'Included',
                                        'x' => 'Not Included',
                                        'partial' => 'Partial',
                                        'custom' => 'Custom Text',
                                    ])
                                    ->default('check')
                                    ->live(),

                                TextInput::make('column_b_text')
                                    ->label('Column B Custom Text')
                                    ->visible(fn($get) => $get('column_b') === 'custom'),
                            ])
                            ->columns(2)
                            ->defaultItems(5)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['feature'] ?? 'Feature'),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.comparison', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'column_a_title' => $config['column_a_title'] ?? 'Basic',
            'column_b_title' => $config['column_b_title'] ?? 'Pro',
            'features' => $config['features'] ?? [],
            'style' => $config['style'] ?? 'default',
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.comparison', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'column_a_title' => $config['column_a_title'] ?? 'Basic',
            'column_b_title' => $config['column_b_title'] ?? 'Pro',
            'features' => $config['features'] ?? [],
            'style' => $config['style'] ?? 'default',
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
