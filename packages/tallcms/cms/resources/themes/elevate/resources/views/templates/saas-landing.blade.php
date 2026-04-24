{{-- SaaS Landing Template: Full-width, no sidebar, no page title --}}
{{-- Blocks stack edge-to-edge for conversion-optimized landing pages --}}

<div class="cms-content w-full">
    {{-- Main content — hero block is expected as the first block --}}
    <section id="content" data-content-width="{{ $page->content_width ?? 'full-width' }}">
        {!! $renderedContent !!}
    </section>

    {{-- SPA Mode: Additional pages as sections --}}
    @foreach($allPages as $pageData)
        <section id="{{ $pageData['anchor'] }}" data-content-width="{{ $pageData['content_width'] ?? 'full-width' }}">
            {!! $pageData['content'] !!}
        </section>
    @endforeach
</div>
