<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class HeroBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'hero';
    }

    public static function getLabel(): string
    {
        return 'Hero';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the hero section with heading, subheading, and background image')
            ->schema([
                TextInput::make('heading')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter hero heading'),
                    
                Textarea::make('subheading')
                    ->maxLength(500)
                    ->placeholder('Enter hero subheading or description'),
                    
                TextInput::make('button_text')
                    ->maxLength(100)
                    ->placeholder('Call to action button text'),
                    
                TextInput::make('button_url')
                    ->url()
                    ->placeholder('https://example.com'),
                    
                FileUpload::make('background_image')
                    ->image()
                    ->directory('cms/hero-blocks')
                    ->nullable(),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        $heading = $config['heading'] ?? 'Hero Heading';
        $subheading = $config['subheading'] ?? 'Hero subheading text';
        $buttonText = $config['button_text'] ?? null;
        
        return '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3rem 2rem; border-radius: 0.5rem; text-align: center;">' .
               '<h2 style="font-size: 2rem; font-weight: bold; margin: 0 0 1rem 0;">' . htmlspecialchars($heading) . '</h2>' .
               '<p style="font-size: 1.2rem; margin: 0 0 1.5rem 0; opacity: 0.9;">' . htmlspecialchars($subheading) . '</p>' .
               ($buttonText ? '<span style="background: white; color: #667eea; padding: 0.75rem 2rem; border-radius: 0.5rem; font-weight: 600;">' . htmlspecialchars($buttonText) . '</span>' : '') .
               '</div>';
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.hero', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $config['button_url'] ?? null,
            'background_image' => $config['background_image'] ?? null,
        ])->render();
    }
}
