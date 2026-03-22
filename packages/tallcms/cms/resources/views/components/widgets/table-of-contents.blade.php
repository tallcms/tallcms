@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $maxDepth = min(max((int) ($settings['max_depth'] ?? 3), 2), 4);
    $posts = collect();
    $parentSlug = '';
    $postsBlockConfig = ($page) ? $page->getPostsBlockConfig() : [];

    if (!empty($postsBlockConfig)) {
        // Posts mode: query posts matching the block config
        $query = \TallCms\Cms\Models\CmsPost::query()->published();

        // Category filter
        $categories = $postsBlockConfig['categories'] ?? [];
        if (!empty($categories)) {
            $query->whereHas('categories', fn ($q) =>
                $q->whereIn('tallcms_categories.id', $categories)
            );
        }

        // Featured only
        if ($postsBlockConfig['featured_only'] ?? false) {
            $query->featured();
        }

        // Sort
        $sortBy = $postsBlockConfig['sort_by'] ?? 'newest';
        $pinnedPosts = $postsBlockConfig['pinned_posts'] ?? [];

        if ($sortBy === 'manual' && !empty($pinnedPosts)) {
            $manualQuery = \TallCms\Cms\Models\CmsPost::query()->published();
            if (!empty($categories)) {
                $manualQuery->whereHas('categories', fn ($q) =>
                    $q->whereIn('tallcms_categories.id', $categories)
                );
            }
            $posts = $manualQuery->whereIn('id', $pinnedPosts)->get()
                ->sortBy(fn ($p) => array_search($p->id, $pinnedPosts))->values();
        } else {
            match ($sortBy) {
                'oldest' => $query->orderBy('published_at', 'asc'),
                'title_asc' => $query->orderByRaw('LOWER(title) ASC'),
                'title_desc' => $query->orderByRaw('LOWER(title) DESC'),
                default => $query->orderBy('published_at', 'desc'),
            };

            $offset = (int) ($postsBlockConfig['offset'] ?? 0);
            $count = (int) ($postsBlockConfig['posts_count'] ?? 20);
            $posts = $query->skip($offset)->take($count)->get();
        }

        // Parent slug for post URLs
        $parentSlug = ($page->slug === '/') ? '' : $page->slug;
    }

    // Heading mode fallback (when no posts block or posts block returns empty)
    $headings = [];
    if ($posts->isEmpty()) {
        preg_match_all('/<h([2-' . $maxDepth . '])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $renderedContent, $headings, PREG_SET_ORDER);
    }
@endphp

@if($posts->isNotEmpty())
    {{-- Posts TOC mode --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">Posts</h3>
        <ul class="space-y-2 text-sm">
            @foreach($posts as $post)
                @php
                    $postSlug = tallcms_i18n_enabled()
                        ? ($post->getTranslation('slug', app()->getLocale(), false) ?? $post->slug)
                        : $post->slug;
                    $fullSlug = empty($parentSlug) ? $postSlug : $parentSlug . '/' . $postSlug;
                    $postUrl = tallcms_localized_url($fullSlug);
                @endphp
                <li>
                    <a href="{{ $postUrl }}" class="link link-hover text-base-content/70 hover:text-base-content transition-colors">
                        {{ $post->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@elseif(count($headings) > 0)
    {{-- Heading anchor mode (existing behavior) --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">On This Page</h3>
        <ul class="space-y-2 text-sm">
            @foreach($headings as $heading)
                <li class="{{ ['2' => '', '3' => 'ml-4', '4' => 'ml-8'][$heading[1]] ?? '' }}">
                    <a href="#{{ $heading[2] }}" class="link link-hover text-base-content/70 hover:text-base-content transition-colors">
                        {{ strip_tags($heading[3]) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
