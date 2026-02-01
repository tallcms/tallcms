{{-- Full Width Template: No max-width constraints --}}
<div class="cms-content w-full">
    {{-- Main page content - full width --}}
    <section id="content" data-content-width="full">
        {!! $renderedContent !!}
    </section>

    {{-- SPA Mode: Additional pages as sections --}}
    @foreach($allPages as $pageData)
        <section id="{{ $pageData['anchor'] }}" data-content-width="full">
            {!! $pageData['content'] !!}
        </section>
    @endforeach
</div>
