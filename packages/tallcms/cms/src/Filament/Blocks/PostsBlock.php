<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;

class PostsBlock extends RichContentCustomBlock
{
    use HasDaisyUIOptions;
    public static function getId(): string
    {
        return 'posts';
    }

    public static function getLabel(): string
    {
        return 'Posts';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('2xl')
            ->modalDescription('Display a list of posts filtered by category')
            ->schema([
                // Hidden block UUID for stable pagination parameters
                Hidden::make('block_uuid')
                    ->default(fn () => Str::uuid()->toString()),

                // Filtering
                Select::make('categories')
                    ->label('Filter by Categories')
                    ->multiple()
                    ->options(fn () => CmsCategory::pluck('name', 'id')->toArray())
                    ->placeholder('All categories')
                    ->helperText('Leave empty to show posts from all categories'),

                Toggle::make('featured_only')
                    ->label('Featured Posts Only')
                    ->default(false),

                // Layout
                Select::make('layout')
                    ->label('Layout Style')
                    ->options([
                        'grid' => 'Grid (cards)',
                        'list' => 'List (horizontal)',
                        'compact-list' => 'Compact List (minimal)',
                    ])
                    ->default('grid')
                    ->live(),

                Select::make('columns')
                    ->label('Grid Columns')
                    ->options([
                        '2' => '2 Columns',
                        '3' => '3 Columns',
                        '4' => '4 Columns',
                    ])
                    ->default('3')
                    ->visible(fn (Get $get) => $get('layout') === 'grid')
                    ->helperText('Responsive: 1 on mobile, 2 on tablet, selected on desktop'),

                // Quantity & Sorting
                Select::make('posts_count')
                    ->label('Number of Posts')
                    ->options([
                        '3' => '3 posts',
                        '6' => '6 posts',
                        '9' => '9 posts',
                        '12' => '12 posts',
                        '24' => '24 posts',
                    ])
                    ->default('6'),

                TextInput::make('offset')
                    ->label('Offset (Skip Posts)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Skip first N posts (useful for multi-section layouts)'),

                Select::make('sort_by')
                    ->label('Sort By')
                    ->options([
                        'newest' => 'Newest First',
                        'oldest' => 'Oldest First',
                        'featured_first' => 'Featured First, then Newest',
                        'manual' => 'Manual Selection',
                    ])
                    ->default('newest')
                    ->live(),

                Select::make('pinned_posts')
                    ->label('Select Posts (Manual Order)')
                    ->multiple()
                    ->options(fn () => CmsPost::published()->pluck('title', 'id')->toArray())
                    ->visible(fn (Get $get) => $get('sort_by') === 'manual')
                    ->helperText('Select and order posts manually'),

                // Display Options
                Toggle::make('show_image')
                    ->label('Show Featured Image')
                    ->default(true),

                Toggle::make('show_excerpt')
                    ->label('Show Excerpt')
                    ->default(true),

                Toggle::make('show_date')
                    ->label('Show Date')
                    ->default(true),

                Toggle::make('show_author')
                    ->label('Show Author')
                    ->default(false),

                Toggle::make('show_categories')
                    ->label('Show Categories')
                    ->default(true),

                Toggle::make('show_read_more')
                    ->label('Show Read More Link')
                    ->default(true),

                // Empty State
                TextInput::make('empty_message')
                    ->label('Empty State Message')
                    ->placeholder('No posts found.')
                    ->helperText('Message shown when no posts match the filters'),

                // Pagination
                Section::make('Pagination')
                    ->schema([
                        Toggle::make('enable_pagination')
                            ->label('Enable Pagination')
                            ->helperText('Show pagination controls when there are more posts than displayed')
                            ->default(false)
                            ->live(),

                        Select::make('per_page')
                            ->label('Posts Per Page')
                            ->options([
                                '6' => '6 posts',
                                '9' => '9 posts',
                                '12' => '12 posts',
                                '18' => '18 posts',
                                '24' => '24 posts',
                            ])
                            ->default('12')
                            ->visible(fn (Get $get) => $get('enable_pagination'))
                            ->helperText('Number of posts to show per page'),
                    ])
                    ->columns(2),

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
        return static::renderBlock($config, true, 'preview');
    }

    public static function toHtml(array $config, array $data): string
    {
        // Don't pass parentSlug - let the template resolve it from request()->route('slug')
        // This ensures correct URL generation when viewing the page
        return static::renderBlock($config, false);
    }

    protected static function renderBlock(array $config, bool $isPreview, ?string $parentSlug = null): string
    {
        $params = [
            ...$config,
            'isPreview' => $isPreview,
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
        ];

        if ($parentSlug !== null) {
            $params['parentSlug'] = $parentSlug;
        }

        return view('tallcms::cms.blocks.posts', $params)->render();
    }
}
