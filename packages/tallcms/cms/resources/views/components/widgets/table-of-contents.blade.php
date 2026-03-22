@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $maxDepth = min(max((int) ($settings['max_depth'] ?? 3), 2), 4);

    // Primary: extract headings with IDs from rendered content
    preg_match_all('/<h([2-' . $maxDepth . '])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $renderedContent, $headings, PREG_SET_ORDER);

    // Fallback: if no headings, build a posts-by-category TOC from parent page's Posts blocks
    $groupedPosts = [];
    if (count($headings) === 0 && $page) {
        $categoryIds = $page->getPostsBlockCategoryIds();
        if (!empty($categoryIds)) {
            $parentSlug = ($page->slug === '/') ? '' : $page->slug;

            $categories = \TallCms\Cms\Models\CmsCategory::whereIn('id', $categoryIds)
                ->get()
                ->sortBy(fn ($c) => array_search($c->id, $categoryIds))
                ->values();

            foreach ($categories as $category) {
                $posts = \TallCms\Cms\Models\CmsPost::query()
                    ->published()
                    ->whereHas('categories', fn ($q) => $q->where('tallcms_categories.id', $category->id))
                    ->orderBy('published_at', 'asc')
                    ->get();

                if ($posts->isNotEmpty()) {
                    $groupedPosts[] = [
                        'category' => $category,
                        'posts' => $posts,
                    ];
                }
            }
        }
    }

    // Static indentation map to avoid Tailwind purging issues
    $indentClasses = [
        '2' => '',
        '3' => 'ml-4',
        '4' => 'ml-8',
    ];
@endphp

@if(count($headings) > 0)
    {{-- Heading anchor mode --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">On This Page</h3>
        <ul class="space-y-2 text-sm">
            @foreach($headings as $heading)
                <li class="{{ $indentClasses[$heading[1]] ?? '' }}">
                    <a href="#{{ $heading[2] }}" class="link link-hover text-base-content/70 hover:text-base-content transition-colors">
                        {{ strip_tags($heading[3]) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@elseif(!empty($groupedPosts))
    {{-- Posts by category mode (post detail pages) --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">
            <a href="{{ tallcms_localized_url($parentSlug) }}" class="link link-hover">{{ $page->title }}</a>
        </h3>
        <div class="space-y-4">
            @foreach($groupedPosts as $group)
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1">{{ $group['category']->name }}</p>
                    <ul class="space-y-1 text-sm">
                        @foreach($group['posts'] as $post)
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
                </div>
            @endforeach
        </div>
    </nav>
@endif
