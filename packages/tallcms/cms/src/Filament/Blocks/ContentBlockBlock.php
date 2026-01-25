<?php

namespace TallCms\Cms\Filament\Blocks;

use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
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
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getDescription(): string
    {
        return 'Rich text content section with title and body';
    }

    public static function getKeywords(): array
    {
        return ['article', 'text', 'prose', 'body'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }

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

                static::getContentWidthField(),

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

                static::getIdentifiersSection(),
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
        $widthConfig = static::resolveWidthClass($config);

        return view('tallcms::cms.blocks.content-block', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'subtitle' => $config['subtitle'] ?? '',
            'body' => $config['body'] ?? '',
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'heading_level' => $config['heading_level'] ?? 'h2',
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
        ])->render();
    }
}
