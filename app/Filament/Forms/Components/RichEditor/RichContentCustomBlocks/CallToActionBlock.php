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
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        $title = $config['title'] ?? 'Call to Action Title';
        $description = $config['description'] ?? 'Compelling description text';
        $buttonText = $config['button_text'] ?? 'Get Started';
        $style = $config['style'] ?? 'primary';
        
        $colors = [
            'primary' => 'background: #3b82f6; color: white;',
            'secondary' => 'background: #6b7280; color: white;',
            'success' => 'background: #10b981; color: white;',
            'warning' => 'background: #f59e0b; color: white;',
            'danger' => 'background: #ef4444; color: white;',
        ];
        
        return '<div style="background: #f8fafc; padding: 2rem; border-radius: 0.5rem; text-align: center; border: 2px dashed #e2e8f0;">' .
               '<h3 style="font-size: 1.5rem; font-weight: bold; margin: 0 0 1rem 0; color: #1f2937;">' . htmlspecialchars($title) . '</h3>' .
               '<p style="font-size: 1rem; margin: 0 0 1.5rem 0; color: #6b7280;">' . htmlspecialchars($description) . '</p>' .
               '<span style="' . ($colors[$style] ?? $colors['primary']) . ' padding: 0.75rem 2rem; border-radius: 0.5rem; font-weight: 600; display: inline-block;">' . htmlspecialchars($buttonText) . '</span>' .
               '</div>';
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
