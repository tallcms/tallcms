<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use App\Models\CmsPage;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

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
                    
                Select::make('button_link_type')
                    ->label('Button Link Type')
                    ->options([
                        'page' => 'Page',
                        'external' => 'External URL',
                        'custom' => 'Custom URL',
                    ])
                    ->default('page')
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('button_page_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('button_url', null)),
                    
                Select::make('button_page_id')
                    ->label('Select Page')
                    ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get): bool => $get('button_link_type') === 'page'),
                    
                TextInput::make('button_url')
                    ->label('URL')
                    ->required()
                    ->placeholder('https://example.com or /contact or #section')
                    ->visible(fn (Get $get): bool => in_array($get('button_link_type'), ['external', 'custom'])),
                    
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
        return view('cms.blocks.call-to-action', array_merge($config, [
            'title' => $config['title'] ?? 'Call to Action Title',
            'description' => $config['description'] ?? 'Compelling description text',
            'button_text' => $config['button_text'] ?? 'Get Started',
            'button_url' => $config['button_url'] ?? '#',
            'style' => $config['style'] ?? 'primary',
        ]))->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        // Pre-resolve URL to avoid DB hit in view
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        
        return view('cms.blocks.call-to-action', array_merge($config, [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'button_text' => $config['button_text'] ?? '',
            'button_url' => $buttonUrl,
            'style' => $config['style'] ?? 'primary',
        ]))->render();
    }
}
