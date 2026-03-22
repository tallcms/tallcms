@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $maxDepth = min(max((int) ($settings['max_depth'] ?? 3), 2), 4);

    // Check if the page has Posts blocks — if so, use posts-by-category mode
    $groupedPosts = [];
    $hasPostsBlock = false;
    if ($page) {
        $categoryIds = $page->getPostsBlockCategoryIds();
        if (!empty($categoryIds)) {
            $hasPostsBlock = true;
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

    // Heading mode: only for pages without Posts blocks
    $headings = [];
    if (!$hasPostsBlock) {
        preg_match_all('/<h([2-' . $maxDepth . '])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $renderedContent, $headings, PREG_SET_ORDER);
    }

    // Current path for active state detection
    $currentPath = '/' . ltrim(request()->path(), '/');

    // Static indentation map to avoid Tailwind purging issues
    $indentClasses = [
        '2' => '',
        '3' => 'ml-4',
        '4' => 'ml-8',
    ];
@endphp

@if(!empty($groupedPosts))
    {{-- Posts by category mode --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">
            <a href="{{ tallcms_localized_url($parentSlug) }}" class="hover:text-primary transition-colors">{{ $page->title }}</a>
        </h3>
        <div class="space-y-5">
            @foreach($groupedPosts as $group)
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-base-content/40 mb-2">{{ $group['category']->name }}</p>
                    <ul class="space-y-0.5">
                        @foreach($group['posts'] as $post)
                            @php
                                $postSlug = tallcms_i18n_enabled()
                                    ? ($post->getTranslation('slug', app()->getLocale(), false) ?? $post->slug)
                                    : $post->slug;
                                $fullSlug = empty($parentSlug) ? $postSlug : $parentSlug . '/' . $postSlug;
                                $postUrl = tallcms_localized_url($fullSlug);
                                $isActive = $currentPath === '/' . ltrim($postUrl, '/');
                            @endphp
                            <li>
                                <a
                                    href="{{ $postUrl }}"
                                    @class([
                                        'block rounded-lg px-3 py-1.5 text-sm transition-colors',
                                        'bg-primary/10 text-primary font-medium' => $isActive,
                                        'text-base-content/70 hover:text-base-content hover:bg-base-200/50' => !$isActive,
                                    ])
                                >
                                    {{ $post->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </nav>
@elseif(count($headings) > 0)
    {{-- Heading anchor mode (pages without Posts blocks) --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">On This Page</h3>
        <ul class="space-y-0.5">
            @foreach($headings as $heading)
                <li class="{{ $indentClasses[$heading[1]] ?? '' }}">
                    <a href="#{{ $heading[2] }}" class="block rounded-lg px-3 py-1.5 text-sm text-base-content/70 hover:text-base-content hover:bg-base-200/50 transition-colors">
                        {{ strip_tags($heading[3]) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
