<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CallToActionBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'call_to_action';
    }

    public static function getLabel(): string
    {
        return 'Call to action';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create a compelling call to action section')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter CTA title'),
                    
                Textarea::make('description')
                    ->maxLength(500)
                    ->placeholder('Enter CTA description'),
                    
                TextInput::make('button_text')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Get Started'),
                    
                TextInput::make('button_url')
                    ->required()
                    ->url()
                    ->placeholder('https://example.com/signup'),
                    
                Select::make('style')
                    ->options([
                        'primary' => 'Primary (Blue)',
                        'secondary' => 'Secondary (Gray)',
                        'success' => 'Success (Green)',
                        'warning' => 'Warning (Yellow)',
                        'danger' => 'Danger (Red)',
                    ])
                    ->default('primary'),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.call-to-action', [
            'title' => $config['title'] ?? 'Call to Action Title',
            'description' => $config['description'] ?? 'Compelling description text',
            'button_text' => $config['button_text'] ?? 'Get Started',
            'button_url' => $config['button_url'] ?? '#',
            'style' => $config['style'] ?? 'primary',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.call-to-action', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'button_text' => $config['button_text'] ?? '',
            'button_url' => $config['button_url'] ?? '',
            'style' => $config['style'] ?? 'primary',
        ])->render();
    }
}
