@php
    use App\Models\CmsPost;
    use App\Models\CmsCategory;
    use Illuminate\Support\Facades\Storage;

    // Get theme presets
    $textPresets = theme_text_presets();
    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];

    // Block configuration with defaults
    $configCategories = $categories ?? [];
    $layout = $layout ?? 'grid';
    $columns = $columns ?? '3';
    $postsCount = (int) ($posts_count ?? 6);
    $offset = (int) ($offset ?? 0);
    $sortBy = $sort_by ?? 'newest';
    $pinnedPosts = $pinned_posts ?? [];
    $featuredOnly = $featured_only ?? false;
    $showImage = $show_image ?? true;
    $showExcerpt = $show_excerpt ?? true;
    $showDate = $show_date ?? true;
    $showAuthor = $show_author ?? false;
    $showCategories = $show_categories ?? true;
    $showReadMore = $show_read_more ?? true;
    $emptyMessage = $empty_message ?? 'No posts found.';
    $firstSection = $first_section ?? false;
    $isPreview = $isPreview ?? false;

    // Get parent slug - try multiple sources in order of preference:
    // 1. Passed directly to template ($parentSlug)
    // 2. Shared view data from CmsPageRenderer ($cmsPageSlug)
    // 3. Route parameter as fallback (handles direct page views)
    $parentSlug = $parentSlug
        ?? ($cmsPageSlug ?? null)
        ?? request()->route('slug')
        ?? '';

    // Check for category filter from query string
    $filterCategorySlug = request()->query('category');
    $filterCategory = $filterCategorySlug
        ? CmsCategory::where('slug', $filterCategorySlug)->first()
        : null;

    // Determine if we should show drafts (preview mode for authenticated users)
    // $isPreview is set by PostsBlock::toPreviewHtml() in admin builder preview
    // ?preview query param is for frontend preview routes/links
    $showDrafts = auth()->check() && ($isPreview || request()->has('preview'));

    // Build the base query
    $query = CmsPost::query()->with(['categories', 'author']);

    // Apply publish filter (allow drafts in preview mode)
    if (!$showDrafts) {
        $query->published();
    }

    // Apply category filter - query string takes precedence over config
    $activeCategories = $filterCategory ? [$filterCategory->id] : $configCategories;
    if (!empty($activeCategories)) {
        $query->whereHas('categories', function ($q) use ($activeCategories) {
            $q->whereIn('tallcms_categories.id', $activeCategories);
        });
    }

    // Featured only filter
    if ($featuredOnly) {
        $query->featured();
    }

    // Handle sorting and retrieval
    if ($sortBy === 'manual' && !empty($pinnedPosts)) {
        // Manual order: get pinned posts in specified order
        // Still respect category filter and posts_count limit
        $manualQuery = CmsPost::query()->with(['categories', 'author']);

        if (!$showDrafts) {
            $manualQuery->published();
        }

        // Apply category filter to manual selection too
        if (!empty($activeCategories)) {
            $manualQuery->whereHas('categories', function ($q) use ($activeCategories) {
                $q->whereIn('tallcms_categories.id', $activeCategories);
            });
        }

        $posts = $manualQuery
            ->whereIn('id', $pinnedPosts)
            ->get()
            ->sortBy(function ($post) use ($pinnedPosts) {
                return array_search($post->id, $pinnedPosts);
            })
            ->values()
            ->skip($offset)
            ->take($postsCount);
    } else {
        // Apply sorting
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            case 'featured_first':
                $query->orderBy('is_featured', 'desc')
                      ->orderBy('published_at', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }

        // Apply offset and limit
        $posts = $query->offset($offset)->limit($postsCount)->get();
    }

    // Section classes with spacing
    $sectionClasses = collect([
        'w-full',
        'px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16',
        $firstSection ? 'pt-12 sm:pt-16 pb-12 sm:pb-16' : 'py-12 sm:py-16'
    ])->filter()->join(' ');

    // Grid column classes
    $gridColumnClass = match($columns) {
        '2' => 'posts-grid--cols-2',
        '4' => 'posts-grid--cols-4',
        default => 'posts-grid--cols-3'
    };

    // Layout container class
    $layoutClass = match($layout) {
        'list' => 'posts-list',
        'compact-list' => 'posts-list posts-list--compact',
        default => "posts-grid {$gridColumnClass}"
    };

    // Build inline CSS custom properties
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-link-hover-color: ' . ($textPreset['link_hover'] ?? '#1d4ed8')
    ])->join('; ') . ';';

    // Helper to generate post URL using route helper
    $getPostUrl = function($post) use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        // For homepage (empty slug), post links to /{post-slug}
        // For other pages, links to /{page-slug}/{post-slug}
        $slug = empty($parentSlug) ? $post->slug : $parentSlug . '/' . $post->slug;
        return route('cms.page', ['slug' => $slug]);
    };

    // Helper to generate category filter URL
    $getCategoryFilterUrl = function($category) use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        // For homepage, link to /?category=slug
        // For other pages, link to /{page-slug}?category=slug
        if (empty($parentSlug)) {
            return route('cms.home') . '?category=' . $category->slug;
        }
        return route('cms.page', ['slug' => $parentSlug]) . '?category=' . $category->slug;
    };

    // Helper to generate clear filter URL
    $getClearFilterUrl = function() use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        if (empty($parentSlug)) {
            return route('cms.home');
        }
        return route('cms.page', ['slug' => $parentSlug]);
    };
@endphp

<section class="posts-block {{ $sectionClasses }}" style="{{ $customProperties }}">
    <div class="max-w-7xl mx-auto">
        {{-- Active filter indicator --}}
        @if($filterCategory)
            <div class="mb-6 flex items-center gap-2">
                <span class="text-sm" style="color: var(--block-text-color);">Filtering by:</span>
                <span
                    class="inline-flex items-center gap-1 text-sm font-medium px-3 py-1 rounded-full"
                    style="background-color: {{ $filterCategory->color ?? '#e5e7eb' }}20; color: {{ $filterCategory->color ?? '#374151' }};"
                >
                    {{ $filterCategory->name }}
                    <a
                        href="{{ $getClearFilterUrl() }}"
                        class="ml-1 hover:opacity-70"
                        title="Clear filter"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </span>
            </div>
        @endif

        @if($posts->isEmpty())
            {{-- Empty State --}}
            <div class="posts-empty text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <p class="mt-4 text-lg" style="color: var(--block-text-color);">
                    {{ $emptyMessage }}
                </p>
                @if($filterCategory)
                    <a
                        href="{{ $getClearFilterUrl() }}"
                        class="mt-4 inline-flex items-center text-sm font-medium"
                        style="color: var(--block-link-color);"
                    >
                        View all posts
                    </a>
                @endif
            </div>
        @else
            <div class="{{ $layoutClass }}">
                @foreach($posts as $post)
                    @php
                        $postUrl = $getPostUrl($post);
                    @endphp

                    @if($layout === 'grid')
                        {{-- Grid Card Layout --}}
                        <article class="post-card bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                            @if($showImage && $post->featured_image)
                                <a href="{{ $postUrl }}" class="block">
                                    <div class="post-card__image-wrapper">
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                            alt="{{ $post->title }}"
                                            class="post-card__image w-full h-48 object-cover"
                                            loading="lazy"
                                        >
                                    </div>
                                </a>
                            @endif

                            <div class="post-card__content p-5">
                                @if($showCategories && $post->categories->isNotEmpty())
                                    <div class="post-card__categories flex flex-wrap gap-2 mb-3">
                                        @foreach($post->categories->take(3) as $category)
                                            <a
                                                href="{{ $getCategoryFilterUrl($category) }}"
                                                class="inline-block text-xs font-medium px-2 py-1 rounded-full hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $category->color ?? '#e5e7eb' }}20; color: {{ $category->color ?? '#374151' }};"
                                            >
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <h3 class="post-card__title text-lg font-semibold mb-2 leading-tight">
                                    <a
                                        href="{{ $postUrl }}"
                                        class="hover:underline"
                                        style="color: var(--block-heading-color);"
                                    >
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                @if($showDate || $showAuthor)
                                    <div class="post-card__meta flex items-center gap-3 text-sm mb-3" style="color: var(--block-text-color); opacity: 0.7;">
                                        @if($showDate && $post->published_at)
                                            <time datetime="{{ $post->published_at->toISOString() }}">
                                                {{ $post->published_at->format('M j, Y') }}
                                            </time>
                                        @endif
                                        @if($showDate && $showAuthor && $post->author)
                                            <span>&middot;</span>
                                        @endif
                                        @if($showAuthor && $post->author)
                                            <span>{{ $post->author->name }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($showExcerpt && $post->excerpt)
                                    <p class="post-card__excerpt text-sm line-clamp-3" style="color: var(--block-text-color);">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                @if($showReadMore)
                                    <a
                                        href="{{ $postUrl }}"
                                        class="post-card__read-more inline-flex items-center mt-4 text-sm font-medium"
                                        style="color: var(--block-link-color);"
                                    >
                                        Read more
                                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </article>

                    @else
                        {{-- List Layout --}}
                        <article class="post-card post-card--list flex gap-6 {{ $layout === 'compact-list' ? 'py-4 border-b border-gray-100' : 'bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow duration-200' }}">
                            @if($showImage && $post->featured_image && $layout !== 'compact-list')
                                <a href="{{ $postUrl }}" class="flex-shrink-0">
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                        alt="{{ $post->title }}"
                                        class="post-card__image w-48 h-32 object-cover rounded-lg"
                                        loading="lazy"
                                    >
                                </a>
                            @endif

                            <div class="post-card__content flex-1 min-w-0">
                                @if($showCategories && $post->categories->isNotEmpty() && $layout !== 'compact-list')
                                    <div class="post-card__categories flex flex-wrap gap-2 mb-2">
                                        @foreach($post->categories->take(3) as $category)
                                            <a
                                                href="{{ $getCategoryFilterUrl($category) }}"
                                                class="inline-block text-xs font-medium px-2 py-1 rounded-full hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $category->color ?? '#e5e7eb' }}20; color: {{ $category->color ?? '#374151' }};"
                                            >
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <h3 class="post-card__title {{ $layout === 'compact-list' ? 'text-base' : 'text-xl' }} font-semibold mb-1 leading-tight">
                                    <a
                                        href="{{ $postUrl }}"
                                        class="hover:underline"
                                        style="color: var(--block-heading-color);"
                                    >
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                @if($showDate || $showAuthor)
                                    <div class="post-card__meta flex items-center gap-3 text-sm {{ $layout === 'compact-list' ? '' : 'mb-2' }}" style="color: var(--block-text-color); opacity: 0.7;">
                                        @if($showDate && $post->published_at)
                                            <time datetime="{{ $post->published_at->toISOString() }}">
                                                {{ $post->published_at->format('M j, Y') }}
                                            </time>
                                        @endif
                                        @if($showDate && $showAuthor && $post->author)
                                            <span>&middot;</span>
                                        @endif
                                        @if($showAuthor && $post->author)
                                            <span>{{ $post->author->name }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($showExcerpt && $post->excerpt && $layout !== 'compact-list')
                                    <p class="post-card__excerpt text-sm line-clamp-2" style="color: var(--block-text-color);">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                @if($showReadMore && $layout !== 'compact-list')
                                    <a
                                        href="{{ $postUrl }}"
                                        class="post-card__read-more inline-flex items-center mt-3 text-sm font-medium"
                                        style="color: var(--block-link-color);"
                                    >
                                        Read more
                                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>
