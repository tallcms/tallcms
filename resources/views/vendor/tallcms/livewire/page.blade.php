{{-- Check for special rendering modes --}}
@if($renderedContent === 'WELCOME_PAGE')
    @include('welcome.tallcms')
@elseif($renderedContent === 'POST_DETAIL')
    {{-- Render individual post detail view --}}
    @include('cms.posts.show', [
        'post' => $post,
        'config' => $postsBlockConfig ?? [],
        'parentSlug' => $parentSlug,
    ])
@else
    {{-- Block Canvas - All content is composed of blocks --}}
    <div class="cms-content w-full">
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
