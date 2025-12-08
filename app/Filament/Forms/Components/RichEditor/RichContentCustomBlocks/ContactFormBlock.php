<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class ContactFormBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'contact_form';
    }

    public static function getLabel(): string
    {
        return 'ContactForm';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the contact_form block')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter block title'),
                    
                Textarea::make('description')
                    ->maxLength(500)
                    ->placeholder('Enter block description'),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.contact-form', [
            'title' => $config['title'] ?? 'Sample Title',
            'description' => $config['description'] ?? 'Sample description text',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.contact-form', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}