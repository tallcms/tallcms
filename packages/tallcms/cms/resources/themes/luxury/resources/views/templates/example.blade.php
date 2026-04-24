{{-- Example Custom Template --}}
{{-- Copy this file and customize to create your own page templates --}}
{{-- Templates are auto-discovered from themes/{slug}/resources/views/templates/ --}}

<div class="cms-content w-full">
    {{-- Custom header section --}}
    <div class="bg-base-200 py-8">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-3xl font-bold">{{ $page->title }}</h1>
        </div>
    </div>

    {{-- Main content --}}
    <section id="content" class="max-w-4xl mx-auto px-4 py-8" data-content-width="{{ $page->content_width ?? 'standard' }}">
        {!! $renderedContent !!}
    </section>

    {{-- SPA Mode: Additional pages as sections --}}
    @foreach($allPages as $pageData)
        <section id="{{ $pageData['anchor'] }}" data-content-width="{{ $pageData['content_width'] ?? 'standard' }}">
            {!! $pageData['content'] !!}
        </section>
    @endforeach
</div>