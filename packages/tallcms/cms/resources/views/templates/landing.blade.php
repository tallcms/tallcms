{{-- Landing Page Template: Full-width, minimal chrome (navigation/footer hidden via layout) --}}
<div class="cms-content w-full">
    {{-- Main landing page content --}}
    <section id="content" data-content-width="full">
        {!! $renderedContent !!}
    </section>

    {{-- SPA Mode: Additional pages as sections (rare for landing pages, but supported) --}}
    @foreach($allPages as $pageData)
        <section id="{{ $pageData['anchor'] }}" data-content-width="full">
            {!! $pageData['content'] !!}
        </section>
    @endforeach
</div>
