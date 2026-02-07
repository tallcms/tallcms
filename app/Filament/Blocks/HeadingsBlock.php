<?php

namespace App\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class HeadingsBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;

    public static function getId(): string
    {
        return 'headings';
    }

    public static function getLabel(): string
    {
        return 'Headings';
    }

    /**
     * Block category for the enhanced block panel.
     * Options: content, media, social-proof, dynamic, forms, other
     */
    public static function getCategory(): string
    {
        return 'content';
    }

    /**
     * Icon displayed in the block panel.
     * Use any valid Heroicon name (e.g., heroicon-o-star, heroicon-o-photo)
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    /**
     * Brief description shown in search results.
     */
    public static function getDescription(): string
    {
        return 'A custom Headings block';
    }

    /**
     * Additional keywords for search.
     */
    public static function getKeywords(): array
    {
        return ['custom', 'headings'];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the Headings block')
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
        return view('cms.blocks.headings', [
            'title' => $config['title'] ?? 'Sample Title',
            'description' => $config['description'] ?? 'Sample description text',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.headings', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}
