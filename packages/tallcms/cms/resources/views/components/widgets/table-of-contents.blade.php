@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $maxDepth = min(max((int) ($settings['max_depth'] ?? 3), 2), 4);

    // Primary: extract headings with IDs from rendered content
    preg_match_all('/<h([2-' . $maxDepth . '])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $renderedContent, $headings, PREG_SET_ORDER);

    // Fallback: if no headings found, check for Posts block categories on the parent page
    // This provides navigation on post detail pages (links back to category sections)
    $categories = collect();
    if (count($headings) === 0 && $page) {
        $categoryIds = $page->getPostsBlockCategoryIds();
        if (!empty($categoryIds)) {
            $categories = \TallCms\Cms\Models\CmsCategory::whereIn('id', $categoryIds)
                ->get()
                ->sortBy(fn ($c) => array_search($c->id, $categoryIds))
                ->values();
        }
    }

    // Static indentation map to avoid Tailwind purging issues
    $indentClasses = [
        '2' => '',        // h2: no indent
        '3' => 'ml-4',    // h3: 1 level
        '4' => 'ml-8',    // h4: 2 levels
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
@elseif($categories->isNotEmpty())
    {{-- Category navigation mode (post detail pages) --}}
    @php
        $pageUrl = ($page->slug === '/') ? '' : $page->slug;
    @endphp
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="Table of contents">
        <h3 class="text-lg font-semibold mb-4">
            <a href="{{ tallcms_localized_url($pageUrl) }}" class="link link-hover">{{ $page->title }}</a>
        </h3>
        <ul class="space-y-2 text-sm">
            @foreach($categories as $category)
                <li>
                    <a href="{{ tallcms_localized_url($pageUrl) }}#{{ $category->slug }}" class="link link-hover text-base-content/70 hover:text-base-content transition-colors">
                        {{ $category->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
