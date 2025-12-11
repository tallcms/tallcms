<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;

class ContentBlockBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'content_block';
    }

    public static function getLabel(): string
    {
        return 'Content Block';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Add a content section with title and rich text body')
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->maxLength(255)
                    ->placeholder('Enter section title'),
                    
                RichEditor::make('body')
                    ->label('Content')
                    ->placeholder('Write your content here...')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                        'blockquote',
                    ]),
                    
                Toggle::make('first_section')
                    ->label('First Section')
                    ->helperText('Add top spacing if this is the first content after navigation')
                    ->default(false),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.content-block', [
            'title' => $config['title'] ?? 'Content Block Title',
            'body' => $config['body'] ?? '<p>Your content will appear here. You can use <strong>formatting</strong>, <em>emphasis</em>, and other rich text features.</p>',
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.content-block', [
            'title' => $config['title'] ?? '',
            'body' => $config['body'] ?? '',
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }
}