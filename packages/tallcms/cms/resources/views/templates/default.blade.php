{{-- Default Template: Standard page layout --}}
<div class="cms-content w-full">
    {{-- Main page content --}}
    <section id="content" data-content-width="{{ $page->content_width ?? 'standard' }}">
        {!! $renderedContent !!}
    </section>

    {{-- SPA Mode: Additional pages as sections --}}
    @foreach($allPages as $pageData)
        <section id="{{ $pageData['anchor'] }}" data-content-width="{{ $pageData['content_width'] ?? 'standard' }}">
            {!! $pageData['content'] !!}
        </section>
    @endforeach
</div>
