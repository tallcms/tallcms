<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ImageGalleryBlock extends RichContentCustomBlock
{
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
                    ->multiple()
                    ->directory('cms/galleries')
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
                    ->helperText('Upload up to 12 images. Drag to reorder.'),
                    
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
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        $title = $config['title'] ?? '';
        $imageCount = count($config['images'] ?? []);
        $layout = $config['layout'] ?? 'grid-3';
        
        return '<div style="background: #f8fafc; padding: 1.5rem; border-radius: 0.5rem; border: 2px dashed #e2e8f0; text-align: center;">' .
               '<div style="display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">' .
               '<svg style="width: 2rem; height: 2rem; color: #6b7280; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
               '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>' .
               '</svg>' .
               '<span style="font-weight: 600; color: #374151;">Image Gallery</span>' .
               '</div>' .
               ($title ? '<h4 style="font-size: 1.2rem; font-weight: bold; margin: 0 0 0.5rem 0; color: #1f2937;">' . htmlspecialchars($title) . '</h4>' : '') .
               '<p style="color: #6b7280; margin: 0;">' . $imageCount . ' images • ' . ucfirst(str_replace('-', ' ', $layout)) . '</p>' .
               '</div>';
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.image-gallery', [
            'title' => $config['title'] ?? null,
            'images' => $config['images'] ?? [],
            'layout' => $config['layout'] ?? 'grid-3',
            'image_size' => $config['image_size'] ?? 'medium',
        ])->render();
    }
}
