<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\MediaCollection;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;

class DocumentListBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    protected static function getDefaultWidth(): string
    {
        return 'standard';
    }

    public static function getCategory(): string
    {
        return 'media';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-document-arrow-down';
    }

    public static function getDescription(): string
    {
        return 'List of downloadable documents from a collection';
    }

    public static function getKeywords(): array
    {
        return ['documents', 'files', 'download', 'pdf', 'attachments', 'list'];
    }

    public static function getSortPriority(): int
    {
        return 15;
    }

    public static function getId(): string
    {
        return 'document_list';
    }

    public static function getLabel(): string
    {
        return 'Document List';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('2xl')
            ->modalDescription('Display a list of downloadable documents')
            ->schema([
                TextInput::make('title')
                    ->label('Section Title')
                    ->maxLength(255)
                    ->placeholder('e.g., Downloads, Resources, Attachments'),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(500)
                    ->placeholder('Optional description text'),

                Select::make('collection_ids')
                    ->label('Collections')
                    ->multiple()
                    ->options(fn () => MediaCollection::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->required()
                    ->helperText('Select collections containing documents'),

                Select::make('file_types')
                    ->label('File Types')
                    ->multiple()
                    ->options([
                        'application/pdf' => 'PDF',
                        'application/msword' => 'Word (DOC)',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word (DOCX)',
                        'application/vnd.ms-excel' => 'Excel (XLS)',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel (XLSX)',
                        'application/zip' => 'ZIP',
                    ])
                    ->placeholder('All document types')
                    ->helperText('Leave empty to show all documents'),

                Select::make('order')
                    ->label('Order')
                    ->options([
                        'newest' => 'Newest First',
                        'oldest' => 'Oldest First',
                        'name' => 'Alphabetical',
                    ])
                    ->default('newest'),

                TextInput::make('max_items')
                    ->label('Maximum Items')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->placeholder('No limit'),

                Select::make('layout')
                    ->label('Layout')
                    ->options([
                        'list' => 'Simple List',
                        'cards' => 'Cards',
                        'compact' => 'Compact',
                    ])
                    ->default('list'),

                Toggle::make('show_file_size')
                    ->label('Show File Size')
                    ->default(true),

                Toggle::make('show_file_type')
                    ->label('Show File Type Badge')
                    ->default(true),

                Section::make('Appearance')
                    ->schema([
                        static::getContentWidthField(),

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
                            ->default(false),
                    ])
                    ->columns(4),

                Section::make('Animation')
                    ->schema([
                        Select::make('animation_type')
                            ->label('Entrance Animation')
                            ->options(static::getAnimationTypeOptions())
                            ->default('')
                            ->helperText('Animation plays when block scrolls into view'),

                        Select::make('animation_duration')
                            ->label('Animation Speed')
                            ->options(static::getAnimationDurationOptions())
                            ->default('anim-duration-700'),

                        Toggle::make('animation_stagger')
                            ->label('Stagger Items')
                            ->helperText('Animate items sequentially instead of all at once')
                            ->default(false)
                            ->live()
                            ->visible(fn (): bool => static::hasPro()),

                        Select::make('animation_stagger_delay')
                            ->label('Stagger Delay')
                            ->options(static::getStaggerDelayOptions())
                            ->default('100')
                            ->visible(fn (Get $get): bool => static::hasPro() && $get('animation_stagger') === true),
                    ])
                    ->columns(2),

                static::getIdentifiersSection(),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::renderBlock($config);
    }

    public static function toHtml(array $config, array $data): string
    {
        return static::renderBlock($config);
    }

    protected static function renderBlock(array $config): string
    {
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        return view('tallcms::cms.blocks.document-list', [
            'id' => static::getId(),
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'collection_ids' => $config['collection_ids'] ?? [],
            'file_types' => $config['file_types'] ?? [],
            'order' => $config['order'] ?? 'newest',
            'max_items' => isset($config['max_items']) ? (int) $config['max_items'] : null,
            'layout' => $config['layout'] ?? 'list',
            'show_file_size' => $config['show_file_size'] ?? true,
            'show_file_type' => $config['show_file_type'] ?? true,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, $config['title'] ?? null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ])->render();
    }
}
