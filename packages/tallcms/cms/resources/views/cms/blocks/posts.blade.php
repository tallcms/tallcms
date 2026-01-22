@php
    use TallCms\Cms\Models\CmsPost;
    use TallCms\Cms\Models\CmsCategory;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

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
    $sectionBackground = $background ?? 'bg-base-100';
    $sectionPaddingClass = $padding ?? 'py-16';
    $isPreview = $isPreview ?? false;

    // Pagination configuration
    $enablePagination = $enable_pagination ?? false;
    $perPage = (int) ($per_page ?? 12);

    // Block UUID for pagination - use persisted UUID or generate stable fallback
    $blockUuid = $block_uuid ?? null;
    if (!$blockUuid) {
        // Fallback: use a static counter to ensure unique IDs for multiple blocks on same page
        // Combined with page slug for cross-page uniqueness
        static $blockCounter = 0;
        $blockCounter++;
        $pageSlugForId = $cmsPageSlug ?? request()->route('slug') ?? 'home';
        $blockUuid = 'legacy-' . substr(md5($pageSlugForId), 0, 4) . '-' . $blockCounter;
    }
    $paginationParam = "posts_{$blockUuid}";

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

    // Track if we're using pagination
    $isPaginated = false;
    $posts = collect();

    // Handle sorting and retrieval
    if ($sortBy === 'manual' && !empty($pinnedPosts)) {
        // Manual selection doesn't support pagination
        $manualQuery = CmsPost::query()->with(['categories', 'author']);

        if (!$showDrafts) {
            $manualQuery->published();
        }

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

        // Use pagination or simple limit
        // Note: offset and pagination are mutually exclusive - offset is ignored when pagination is enabled
        if ($enablePagination && !$isPreview && $offset === 0) {
            $currentPage = (int) request()->input($paginationParam, 1);
            $posts = $query->paginate($perPage, ['*'], $paginationParam, $currentPage)
                ->withQueryString();
            $isPaginated = true;
        } else {
            // Simple limit/offset mode (no pagination)
            if ($offset > 0) {
                $query->skip($offset);
            }
            $posts = $query->limit($postsCount)->get();
        }
    }

    // Section spacing
    $sectionPadding = $firstSection ? 'pb-16' : $sectionPaddingClass;

    // Grid column classes
    $gridColumnClass = match($columns) {
        '2' => 'sm:grid-cols-2',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3'
    };

    // Helper to generate post URL
    $getPostUrl = function($post) use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        $slug = empty($parentSlug) ? $post->slug : $parentSlug . '/' . $post->slug;
        return route('tallcms.cms.page', ['slug' => $slug]);
    };

    // Helper to generate category filter URL
    $getCategoryFilterUrl = function($category) use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        if (empty($parentSlug)) {
            return route('tallcms.cms.home') . '?category=' . $category->slug;
        }
        return route('tallcms.cms.page', ['slug' => $parentSlug]) . '?category=' . $category->slug;
    };

    // Helper to generate clear filter URL
    $getClearFilterUrl = function() use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        if (empty($parentSlug)) {
            return route('tallcms.cms.home');
        }
        return route('tallcms.cms.page', ['slug' => $parentSlug]);
    };
@endphp

<section class="posts-block {{ $sectionPadding }} {{ $sectionBackground }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Active filter indicator --}}
        @if($filterCategory)
            <div class="mb-6 flex items-center gap-2">
                <span class="text-sm text-base-content/70">Filtering by:</span>
                <span class="badge badge-lg gap-1" style="background-color: {{ $filterCategory->color ?? 'var(--p)' }}20; color: {{ $filterCategory->color ?? 'var(--p)' }};">
                    {{ $filterCategory->name }}
                    <a
                        href="{{ $getClearFilterUrl() }}"
                        class="ml-1 hover:opacity-70"
                        title="Clear filter"
                    >
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                    </a>
                </span>
            </div>
        @endif

        @if($posts->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-12">
                <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-base-content/40" />
                <p class="mt-4 text-lg text-base-content/70">
                    {{ $emptyMessage }}
                </p>
                @if($filterCategory)
                    <a
                        href="{{ $getClearFilterUrl() }}"
                        class="mt-4 inline-flex items-center link link-primary"
                    >
                        View all posts
                    </a>
                @endif
            </div>
        @else
            @if($layout === 'grid')
                {{-- Grid Layout --}}
                <div class="grid gap-6 sm:gap-8 {{ $gridColumnClass }}">
                    @foreach($posts as $post)
                        @php $postUrl = $getPostUrl($post); @endphp
                        <article class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                            @if($showImage && $post->featured_image)
                                <figure>
                                    <a href="{{ $postUrl }}" class="block">
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                            alt="{{ $post->title }}"
                                            class="w-full h-48 object-cover"
                                            loading="lazy"
                                        >
                                    </a>
                                </figure>
                            @endif

                            <div class="card-body">
                                @if($showCategories && $post->categories->isNotEmpty())
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach($post->categories->take(3) as $category)
                                            <a
                                                href="{{ $getCategoryFilterUrl($category) }}"
                                                class="badge badge-sm hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                                            >
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <h3 class="card-title text-lg">
                                    <a href="{{ $postUrl }}" class="hover:underline text-base-content">
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                @if($showDate || $showAuthor)
                                    <div class="flex items-center gap-2 text-sm text-base-content/60">
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
                                    <p class="text-sm text-base-content/70 line-clamp-3">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                @if($showReadMore)
                                    <div class="card-actions justify-start mt-2">
                                        <a href="{{ $postUrl }}" class="link link-primary link-hover text-sm inline-flex items-center">
                                            Read more
                                            <x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                {{-- List Layout --}}
                <div class="space-y-6">
                    @foreach($posts as $post)
                        @php $postUrl = $getPostUrl($post); @endphp
                        <article class="card card-side bg-base-200 shadow-sm hover:shadow-md transition-shadow duration-200 {{ $layout === 'compact-list' ? 'py-2 border-b border-base-300 bg-transparent shadow-none' : '' }}">
                            @if($showImage && $post->featured_image && $layout !== 'compact-list')
                                <figure class="flex-shrink-0">
                                    <a href="{{ $postUrl }}">
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                            alt="{{ $post->title }}"
                                            class="w-48 h-32 object-cover rounded-l-xl"
                                            loading="lazy"
                                        >
                                    </a>
                                </figure>
                            @endif

                            <div class="card-body py-4">
                                @if($showCategories && $post->categories->isNotEmpty() && $layout !== 'compact-list')
                                    <div class="flex flex-wrap gap-2 mb-1">
                                        @foreach($post->categories->take(3) as $category)
                                            <a
                                                href="{{ $getCategoryFilterUrl($category) }}"
                                                class="badge badge-sm hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                                            >
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <h3 class="card-title {{ $layout === 'compact-list' ? 'text-base' : 'text-xl' }}">
                                    <a href="{{ $postUrl }}" class="hover:underline text-base-content">
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                @if($showDate || $showAuthor)
                                    <div class="flex items-center gap-2 text-sm text-base-content/60">
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
                                    <p class="text-sm text-base-content/70 line-clamp-2">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                @if($showReadMore && $layout !== 'compact-list')
                                    <div class="card-actions justify-start mt-1">
                                        <a href="{{ $postUrl }}" class="link link-primary link-hover text-sm inline-flex items-center">
                                            Read more
                                            <x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            {{-- Pagination --}}
            @if($isPaginated && $posts->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $posts->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
