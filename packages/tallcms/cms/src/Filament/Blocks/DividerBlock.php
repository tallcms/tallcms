<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class DividerBlock extends RichContentCustomBlock
{
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-minus';
    }

    public static function getDescription(): string
    {
        return 'Decorative spacing or line separator';
    }

    public static function getKeywords(): array
    {
        return ['separator', 'spacing', 'line', 'divider'];
    }

    public static function getSortPriority(): int
    {
        return 90;
    }

    public static function getId(): string
    {
        return 'divider';
    }

    public static function getLabel(): string
    {
        return 'Divider';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Add spacing or a decorative line between sections')
            ->modalHeading('Configure Divider')
            ->modalWidth('lg')
            ->schema([
                Section::make('Style')
                    ->schema([
                        Select::make('style')
                            ->label('Divider Style')
                            ->options([
                                'space' => 'Space Only',
                                'line' => 'Horizontal Line',
                                'line-icon' => 'Line with Icon',
                            ])
                            ->default('line')
                            ->live(),

                        Select::make('height')
                            ->label('Spacing Height')
                            ->options([
                                'small' => 'Small',
                                'medium' => 'Medium',
                                'large' => 'Large',
                                'xl' => 'Extra Large',
                            ])
                            ->default('medium'),
                    ])
                    ->columns(2),

                Section::make('Line Options')
                    ->schema([
                        Select::make('line_style')
                            ->label('Line Style')
                            ->options([
                                'solid' => 'Solid',
                                'dashed' => 'Dashed',
                                'dotted' => 'Dotted',
                            ])
                            ->default('solid'),

                        Select::make('width')
                            ->label('Line Width')
                            ->options([
                                'full' => 'Full Width',
                                'wide' => 'Wide (75%)',
                                'medium' => 'Medium (50%)',
                                'narrow' => 'Narrow (25%)',
                            ])
                            ->default('medium'),

                        ColorPicker::make('color')
                            ->label('Line Color')
                            ->helperText('Leave empty to use theme default'),

                        Select::make('icon')
                            ->label('Center Icon')
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('style') === 'line-icon')
                            ->options([
                                'heroicon-o-star' => 'Star',
                                'heroicon-o-heart' => 'Heart',
                                'heroicon-o-sparkles' => 'Sparkles',
                                'heroicon-o-bolt' => 'Bolt',
                                'heroicon-o-sun' => 'Sun',
                                'heroicon-o-moon' => 'Moon',
                                'heroicon-o-academic-cap' => 'Academic Cap',
                                'heroicon-o-arrow-down' => 'Arrow Down',
                                'heroicon-o-chevron-down' => 'Chevron Down',
                                'heroicon-o-ellipsis-horizontal' => 'Dots',
                            ])
                            ->default('heroicon-o-star'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('style') !== 'space'),

                static::getIdentifiersSection(),
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
        return view('tallcms::cms.blocks.divider', [
            'id' => static::getId(),
            'style' => $config['style'] ?? 'line',
            'height' => $config['height'] ?? 'medium',
            'line_style' => $config['line_style'] ?? 'solid',
            'width' => $config['width'] ?? 'medium',
            'color' => $config['color'] ?? null,
            'icon' => $config['icon'] ?? 'heroicon-o-star',
            'anchor_id' => static::getAnchorId($config, null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }
}
