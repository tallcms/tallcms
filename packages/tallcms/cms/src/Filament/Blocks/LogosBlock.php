<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\MediaCollection;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class LogosBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'wide';
    }

    public static function getCategory(): string
    {
        return 'social-proof';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-building-office';
    }

    public static function getDescription(): string
    {
        return 'Client or partner logo showcase';
    }

    public static function getKeywords(): array
    {
        return ['clients', 'partners', 'brands', 'logos'];
    }

    public static function getSortPriority(): int
    {
        return 30;
    }

    public static function getId(): string
    {
        return 'logos';
    }

    public static function getLabel(): string
    {
        return 'Logos';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Display client or partner logos in a grid or inline layout')
            ->modalHeading('Configure Logos Block')
            ->modalWidth('5xl')
            ->schema([
                Tabs::make('Logos Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                TextInput::make('heading')
                                    ->label('Section Heading')
                                    ->placeholder('Trusted by leading companies')
                                    ->maxLength(255),

                                Select::make('source')
                                    ->label('Logo Source')
                                    ->options([
                                        'manual' => 'Manual Upload',
                                        'collection' => 'Media Collection',
                                    ])
                                    ->default('manual')
                                    ->live()
                                    ->helperText('Use a media collection for easier management'),

                                Select::make('collection_id')
                                    ->label('Media Collection')
                                    ->options(fn () => MediaCollection::pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('source') === 'collection')
                                    ->helperText('Select a collection containing logo images'),

                                Repeater::make('logos')
                                    ->label('Logos')
                                    ->visible(fn (Get $get): bool => $get('source') !== 'collection')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Logo Image')
                                            ->required()
                                            ->image()
                                            ->disk(\cms_media_disk())
                                            ->directory('logos')
                                            ->visibility(\cms_media_visibility()),

                                        TextInput::make('alt')
                                            ->label('Company Name')
                                            ->required()
                                            ->placeholder('Acme Inc.')
                                            ->helperText('Used for accessibility')
                                            ->maxLength(100),

                                        TextInput::make('url')
                                            ->label('Link (Optional)')
                                            ->placeholder('https://example.com')
                                            ->maxLength(500),
                                    ])
                                    ->defaultItems(4)
                                    ->minItems(1)
                                    ->maxItems(12)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['alt'] ?? 'New Logo')
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
                                                'inline' => 'Inline (Centered)',
                                            ])
                                            ->default('grid'),

                                        Select::make('columns')
                                            ->label('Columns (Grid Layout)')
                                            ->options([
                                                '2' => '2 Columns',
                                                '3' => '3 Columns',
                                                '4' => '4 Columns',
                                                '5' => '5 Columns',
                                                '6' => '6 Columns',
                                            ])
                                            ->default('5'),

                                        Select::make('size')
                                            ->label('Logo Size')
                                            ->options([
                                                'small' => 'Small',
                                                'medium' => 'Medium',
                                                'large' => 'Large',
                                            ])
                                            ->default('medium'),
                                    ])
                                    ->columns(3),

                                Section::make('Styling')
                                    ->schema([
                                        Toggle::make('grayscale')
                                            ->label('Grayscale Logos')
                                            ->helperText('Display logos in grayscale')
                                            ->default(true),

                                        Toggle::make('hover_color')
                                            ->label('Color on Hover')
                                            ->helperText('Show color when hovering (only if grayscale is enabled)')
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Section::make('Appearance')
                                    ->schema([
                                        static::getContentWidthField(),

                                        Select::make('background')
                                            ->label('Background')
                                            ->options(static::getBackgroundOptions())
                                            ->default('bg-base-100'),

                                        Select::make('padding')
                                            ->label('Section Padding')
                                            ->options(static::getPaddingOptions())
                                            ->default('py-16'),

                                        Toggle::make('first_section')
                                            ->label('First Section (Remove Top Padding)')
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ])
                                    ->columns(4),
                            ]),

                        static::getAnimationTab(supportsStagger: false),
                    ]),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $logos = self::resolveLogos($config, forPreview: true);

        return static::renderBlock(array_merge($config, [
            'logos' => $logos,
            'heading' => $config['heading'] ?? 'Trusted by leading companies',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        $logos = self::resolveLogos($config, forPreview: false);

        // Return empty string if no logos configured (signals misconfiguration)
        if (empty($logos)) {
            return '';
        }

        return static::renderBlock(array_merge($config, ['logos' => $logos]));
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.logos', [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'logos' => $config['logos'] ?? [],
            'layout' => $config['layout'] ?? 'grid',
            'columns' => $config['columns'] ?? '5',
            'size' => $config['size'] ?? 'medium',
            'grayscale' => $config['grayscale'] ?? true,
            'hover_color' => $config['hover_color'] ?? true,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['heading'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
        ])->render();
    }

    private static function resolveLogos(array $config, bool $forPreview): array
    {
        $source = $config['source'] ?? 'manual';

        if ($source === 'collection') {
            if (empty($config['collection_id'])) {
                // Collection source selected but no collection chosen
                // In preview: show placeholders; in production: return empty to signal misconfiguration
                return $forPreview ? self::getSampleLogos() : [];
            }

            $collection = MediaCollection::with('media')->find($config['collection_id']);
            if (! $collection || $collection->media->isEmpty()) {
                // Collection not found or empty
                return $forPreview ? self::getSampleLogos() : [];
            }

            return $collection->media->map(fn ($media) => [
                'image' => $media->path,
                'alt' => $media->alt ?? $media->name ?? 'Logo',
                'url' => null,
            ])->toArray();
        }

        // Manual logos
        $logos = $config['logos'] ?? [];
        if (empty($logos)) {
            return $forPreview ? self::getSampleLogos() : [];
        }

        return $logos;
    }

    private static function getSampleLogos(): array
    {
        return [
            ['alt' => 'Company 1', 'image' => null, 'url' => null],
            ['alt' => 'Company 2', 'image' => null, 'url' => null],
            ['alt' => 'Company 3', 'image' => null, 'url' => null],
            ['alt' => 'Company 4', 'image' => null, 'url' => null],
            ['alt' => 'Company 5', 'image' => null, 'url' => null],
        ];
    }
}
