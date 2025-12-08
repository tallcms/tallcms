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
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.hero', [
            'heading' => $config['heading'] ?? 'Hero Heading',
            'subheading' => $config['subheading'] ?? 'Hero subheading text',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $config['button_url'] ?? null,
            'background_image' => $config['background_image'] ?? null,
        ])->render();
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
