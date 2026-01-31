{{-- Check for special rendering modes --}}
@if($renderedContent === 'WELCOME_PAGE')
    {{-- Welcome page for new installations --}}
    <div class="py-24 px-4 text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to TallCMS</h1>
        <p class="text-lg text-gray-600 mb-8">Your CMS is ready. Create your first page in the admin panel.</p>
        <a href="{{ url(config('tallcms.filament.panel_path', 'admin')) }}"
           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
            Go to Admin Panel
        </a>
    </div>
@elseif($renderedContent === 'POST_DETAIL')
    {{-- Render individual post detail view --}}
    @include('tallcms::partials.post-detail', ['post' => $post, 'config' => $postsBlockConfig])
@else
    {{-- Block Canvas --}}
    <div class="cms-content w-full">
        {{-- Homepage content --}}
        <section id="top" data-content-width="{{ $page->content_width ?? 'standard' }}">
            {!! $renderedContent !!}
        </section>

        {{-- SPA Mode: Other pages as sections --}}
        @foreach($allPages as $pageData)
            <section id="{{ $pageData['anchor'] }}" data-content-width="{{ $pageData['content_width'] ?? 'standard' }}">
                {!! $pageData['content'] !!}
            </section>
        @endforeach
    </div>
@endif
