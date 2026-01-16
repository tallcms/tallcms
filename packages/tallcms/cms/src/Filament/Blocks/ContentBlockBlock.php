<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class ContentBlockBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;

    public static function getId(): string
    {
        return 'content_block';
    }

    public static function getLabel(): string
    {
        return 'Content';
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

                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->maxLength(255)
                    ->placeholder('Optional subtitle or subheading'),

                RichEditor::make('body')
                    ->label('Content')
                    ->placeholder('Write your content here...'),

                Select::make('content_width')
                    ->label('Content Width')
                    ->options([
                        'narrow' => 'Narrow (prose-focused)',
                        'normal' => 'Normal (default)',
                        'wide' => 'Wide (more space)',
                    ])
                    ->default('normal'),

                Select::make('heading_level')
                    ->label('Heading Level')
                    ->options([
                        'h2' => 'H2 (recommended for sections)',
                        'h3' => 'H3 (for subsections)',
                        'h4' => 'H4 (for smaller headings)',
                    ])
                    ->default('h2')
                    ->helperText('Choose appropriate heading level for page structure'),

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
        return static::renderBlock(array_merge($config, [
            'title' => $config['title'] ?? 'Content Block Title',
            'subtitle' => $config['subtitle'] ?? 'Optional subtitle for better content hierarchy',
            'body' => $config['body'] ?? '<p>Your content will appear here. You can use <strong>formatting</strong>, <em>emphasis</em>, and other rich text features.</p>',
        ]));
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        return view('cms.blocks.content-block', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'subtitle' => $config['subtitle'] ?? '',
            'body' => $config['body'] ?? '',
            'content_width' => $config['content_width'] ?? 'normal',
            'heading_level' => $config['heading_level'] ?? 'h2',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
        ])->render();
    }
}
