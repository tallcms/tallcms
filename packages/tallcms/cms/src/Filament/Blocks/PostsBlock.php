<?php

namespace TallCms\Cms\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockIdentifiers;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;
use TallCms\Cms\Filament\Blocks\Concerns\HasContentWidth;
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;

class PostsBlock extends RichContentCustomBlock
{
    use HasAnimationOptions;
    use HasBlockIdentifiers;
    use HasBlockMetadata;
    use HasContentWidth;
    use HasDaisyUIOptions;

    public static function getCategory(): string
    {
        return 'dynamic';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-newspaper';
    }

    public static function getDescription(): string
    {
        return 'Display blog posts and articles';
    }

    public static function getKeywords(): array
    {
        return ['blog', 'articles', 'posts', 'recent'];
    }

    public static function getSortPriority(): int
    {
        return 10;
    }
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
            ->modalWidth('5xl')
            ->modalDescription('Display a list of posts filtered by category')
            ->schema([
                // Hidden block UUID for stable pagination parameters
                Hidden::make('block_uuid')
                    ->default(fn () => Str::uuid()->toString()),

                Tabs::make('Posts Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-newspaper')
                            ->schema([
                                Section::make('Filtering')
                                    ->schema([
                                        Select::make('categories')
                                            ->label('Filter by Categories')
                                            ->multiple()
                                            ->options(fn () => CmsCategory::pluck('name', 'id')->toArray())
                                            ->placeholder('All categories')
                                            ->helperText('Leave empty to show posts from all categories'),

                                        Toggle::make('featured_only')
                                            ->label('Featured Posts Only')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Section::make('Quantity & Sorting')
                                    ->schema([
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
                                            ->helperText('Skip first N posts (incompatible with pagination)'),

                                        Select::make('sort_by')
                                            ->label('Sort By')
                                            ->options([
                                                'newest' => 'Newest First',
                                                'oldest' => 'Oldest First',
                                                'title_asc' => 'Title (A-Z)',
                                                'title_desc' => 'Title (Z-A)',
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
                                    ])
                                    ->columns(2),

                                Section::make('Display Options')
                                    ->schema([
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
                                    ])
                                    ->columns(3),

                                TextInput::make('empty_message')
                                    ->label('Empty State Message')
                                    ->placeholder('No posts found.')
                                    ->helperText('Message shown when no posts match the filters'),
                            ]),

                        Tab::make('Layout')
                            ->icon('heroicon-m-squares-2x2')
                            ->schema([
                                Select::make('layout')
                                    ->label('Layout Style')
                                    ->options([
                                        'grid' => 'Grid (cards)',
                                        'list' => 'List (horizontal)',
                                        'compact-list' => 'Compact List (minimal)',
                                        'featured-hero' => 'Featured Hero + Grid',
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
                                    ->visible(fn (Get $get) => in_array($get('layout'), ['grid', 'featured-hero']))
                                    ->helperText('Responsive: 1 on mobile, 2 on tablet, selected on desktop'),

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

                                Section::make('Featured Posts')
                                    ->schema([
                                        Toggle::make('show_featured_badge')
                                            ->label('Show Featured Badge')
                                            ->helperText('Display a badge on featured posts')
                                            ->default(false)
                                            ->live(),

                                        Select::make('featured_badge_style')
                                            ->label('Badge Style')
                                            ->options([
                                                'badge' => 'Text Badge ("Featured")',
                                                'star' => 'Star Icon',
                                                'ribbon' => 'Corner Ribbon',
                                            ])
                                            ->default('badge')
                                            ->visible(fn (Get $get) => $get('show_featured_badge')),

                                        Select::make('featured_badge_color')
                                            ->label('Badge Color')
                                            ->options([
                                                'primary' => 'Primary',
                                                'secondary' => 'Secondary',
                                                'accent' => 'Accent',
                                                'warning' => 'Warning (Gold)',
                                            ])
                                            ->default('warning')
                                            ->visible(fn (Get $get) => $get('show_featured_badge')),

                                        Select::make('featured_card_style')
                                            ->label('Featured Card Style')
                                            ->options([
                                                'default' => 'Same as Regular',
                                                'border' => 'Accent Border',
                                                'gradient' => 'Gradient Background',
                                                'elevated' => 'Elevated Shadow',
                                            ])
                                            ->default('default'),
                                    ])
                                    ->columns(4),

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
                                            ->helperText('Overrides padding setting above')
                                            ->default(false),
                                    ])
                                    ->columns(4),
                            ]),

                        Tab::make('Animation')
                            ->icon('heroicon-m-sparkles')
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
                                    ->helperText('Animate post cards sequentially instead of all at once')
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
                    ]),

                static::getIdentifiersSection(),
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
        $widthConfig = static::resolveWidthClass($config);
        $animConfig = static::getAnimationConfig($config);

        $params = [
            ...$config,
            'isPreview' => $isPreview,
            'contentWidthClass' => $widthConfig['class'],
            'contentPadding' => $widthConfig['padding'],
            'background' => $config['background'] ?? 'bg-base-100',
            'padding' => $config['padding'] ?? 'py-16',
            'first_section' => $config['first_section'] ?? false,
            'anchor_id' => static::getAnchorId($config, null),
            'css_classes' => static::getCssClasses($config),
            'animation_type' => $animConfig['animation_type'],
            'animation_duration' => $animConfig['animation_duration'],
            'animation_stagger' => $animConfig['animation_stagger'],
            'animation_stagger_delay' => $animConfig['animation_stagger_delay'],
        ];

        if ($parentSlug !== null) {
            $params['parentSlug'] = $parentSlug;
        }

        return view('tallcms::cms.blocks.posts', $params)->render();
    }
}
