<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\MediaCollection;

class ImageGalleryBlock extends RichContentCustomBlock
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
        return 'media';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function getDescription(): string
    {
        return 'Media gallery with lightbox for images and videos';
    }

    public static function getKeywords(): array
    {
        return ['images', 'photos', 'gallery', 'lightbox', 'video', 'media'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }
    public static function getId(): string
    {
        return 'image_gallery';
    }

    public static function getLabel(): string
    {
        return 'Media Gallery';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('2xl')
            ->modalDescription('Create a media gallery with images and videos')
            ->schema([
                TextInput::make('title')
                    ->maxLength(255)
                    ->placeholder('Gallery title (optional)'),

                Select::make('source')
                    ->label('Image Source')
                    ->options([
                        'manual' => 'Manual Upload',
                        'collection' => 'Media Collection(s)',
                    ])
                    ->default('manual')
                    ->live()
                    ->helperText('Use collections to reuse images from Media Library'),

                Select::make('collection_ids')
                    ->label('Collections')
                    ->multiple()
                    ->options(fn () => MediaCollection::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('source') === 'collection')
                    ->helperText('Select one or more collections'),

                Select::make('media_type')
                    ->label('Media Type')
                    ->options([
                        'images' => 'Images Only',
                        'videos' => 'Videos Only',
                        'all' => 'Images & Videos',
                    ])
                    ->default('images')
                    ->visible(fn (Get $get): bool => $get('source') === 'collection'),

                Select::make('collection_order')
                    ->label('Order')
                    ->options([
                        'newest' => 'Newest First',
                        'oldest' => 'Oldest First',
                        'random' => 'Random',
                    ])
                    ->default('newest')
                    ->visible(fn (Get $get): bool => $get('source') === 'collection'),

                TextInput::make('max_items')
                    ->label('Maximum Items')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->placeholder('No limit')
                    ->visible(fn (Get $get): bool => $get('source') === 'collection'),

                FileUpload::make('images')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->multiple()
                    ->directory('cms/galleries')
                    ->disk(\cms_media_disk())
                    ->visibility(\cms_media_visibility())
                    ->maxFiles(12)
                    ->reorderable()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->requiredIf('source', 'manual')
                    ->visible(fn (Get $get): bool => $get('source') !== 'collection')
                    ->helperText('Recommended: 1200×800px or larger. Up to 12 images, max 5MB each. Formats: JPEG, PNG, WebP. Drag to reorder.'),

                Select::make('layout')
                    ->options([
                        'grid-2' => 'Grid (2 columns)',
                        'grid-3' => 'Grid (3 columns)',
                        'grid-4' => 'Grid (4 columns)',
                        'masonry' => 'Masonry layout',
                        'carousel' => 'Carousel/Slider',
                    ])
                    ->default('grid-3'),

                Select::make('image_size')
                    ->label('Image Size')
                    ->options([
                        'small' => 'Small (200px)',
                        'medium' => 'Medium (300px)',
                        'large' => 'Large (400px)',
                        'full' => 'Full width',
                    ])
                    ->default('medium'),

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

                Section::make('Animation')
                    ->schema([
                        Select::make('animation_type')
                            ->label('Entrance Animation')
                            ->options(static::getAnimationTypeOptions())
                            ->default('')
                            ->helperText('Animation plays when block scrolls into view'),

                        Select::make('animation_duration')
                            ->label('Animation Speed')
                            ->options(static::getAnimationDurationOptions())
                            ->default('anim-duration-700'),

                        Toggle::make('animation_stagger')
                            ->label('Stagger Items')
                            ->helperText('Animate images sequentially instead of all at once')
                            ->default(false)
                            ->live()
                            ->visible(fn (): bool => static::hasPro()),

                        Select::make('animation_stagger_delay')
                            ->label('Stagger Delay')
                            ->options(static::getStaggerDelayOptions())
                            ->default('100')
                            ->visible(fn (Get $get): bool => static::hasPro() && $get('animation_stagger') === true),
                    ])
                    ->columns(2),

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
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.image-gallery', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'source' => $config['source'] ?? 'manual',
            'images' => $config['images'] ?? [],
            'collection_ids' => $config['collection_ids'] ?? [],
            'collection_order' => $config['collection_order'] ?? 'newest',
            'media_type' => $config['media_type'] ?? 'images',
            'max_items' => isset($config['max_items']) ? (int) $config['max_items'] : (isset($config['max_images']) ? (int) $config['max_images'] : null),
            'layout' => $config['layout'] ?? 'grid-3',
            'image_size' => $config['image_size'] ?? 'medium',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }
}
