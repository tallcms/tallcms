{{-- Check for special rendering modes --}}
@if($renderedContent === 'WELCOME_PAGE')
    @include('welcome.tallcms')
@elseif($renderedContent === 'POST_DETAIL')
    {{-- Render individual post detail view --}}
    @include('cms.posts.show', [
        'post' => $post,
        'parentSlug' => $parentSlug,
    ])
@else
    {{-- Block Canvas - All content is composed of blocks --}}
    <div class="cms-content w-full">
        {!! $renderedContent !!}
    </div>
@endif