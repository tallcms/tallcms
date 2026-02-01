@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $maxDepth = min(max((int) ($settings['max_depth'] ?? 3), 2), 4);

    // Match headings with IDs, handling inline markup with .*? and 's' flag
    $pattern = '/<h([2-' . $maxDepth . '])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is';
    preg_match_all($pattern, $renderedContent, $headings, PREG_SET_ORDER);

    // Static indentation map to avoid Tailwind purging issues
    $indentClasses = [
        '2' => '',        // h2: no indent
        '3' => 'ml-4',    // h3: 1 level
        '4' => 'ml-8',    // h4: 2 levels
    ];
@endphp

@if(count($headings) > 0)
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
@endif
