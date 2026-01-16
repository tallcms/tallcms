<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class ImageGalleryBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;
    public static function getId(): string
    {
        return 'image_gallery';
    }

    public static function getLabel(): string
    {
        return 'Image Gallery';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('2xl')
            ->modalDescription('Create a beautiful image gallery with multiple layout options')
            ->schema([
                TextInput::make('title')
                    ->maxLength(255)
                    ->placeholder('Gallery title (optional)'),

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
                    ->required()
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
                    ->columns(3),
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
        return view('tallcms::cms.blocks.image-gallery', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'images' => $config['images'] ?? [],
            'layout' => $config['layout'] ?? 'grid-3',
            'image_size' => $config['image_size'] ?? 'medium',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }
}
