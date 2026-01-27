<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class DividerBlock extends RichContentCustomBlock
{
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
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
                                'line-text' => 'Line with Text',
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

                        static::getContentWidthField(),
                    ])
                    ->columns(3),

                Section::make('Line Options')
                    ->schema([
                        Select::make('color')
                            ->label('Color')
                            ->options([
                                'default' => 'Default',
                                'primary' => 'Primary',
                                'secondary' => 'Secondary',
                                'accent' => 'Accent',
                                'neutral' => 'Neutral',
                                'success' => 'Success',
                                'warning' => 'Warning',
                                'info' => 'Info',
                                'error' => 'Error',
                            ])
                            ->default('default'),

                        Select::make('position')
                            ->label('Content Position')
                            ->options([
                                'center' => 'Center',
                                'start' => 'Start',
                                'end' => 'End',
                            ])
                            ->default('center')
                            ->visible(fn (Get $get): bool => in_array($get('style'), ['line-text', 'line-icon'])),

                        TextInput::make('text')
                            ->label('Divider Text')
                            ->placeholder('OR')
                            ->maxLength(50)
                            ->visible(fn (Get $get): bool => $get('style') === 'line-text'),

                        Select::make('icon')
                            ->label('Icon')
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
                                'heroicon-o-plus' => 'Plus',
                                'heroicon-o-minus' => 'Minus',
                                'heroicon-o-x-mark' => 'X Mark',
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
        $widthConfig = static::resolveWidthClass($config);

        return view('tallcms::cms.blocks.divider', [
            'id' => static::getId(),
            'style' => $config['style'] ?? 'line',
            'height' => $config['height'] ?? 'medium',
            'color' => $config['color'] ?? 'default',
            'position' => $config['position'] ?? 'center',
            'text' => $config['text'] ?? null,
            'icon' => $config['icon'] ?? null,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'anchor_id' => static::getAnchorId($config, null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }
}
