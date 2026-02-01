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
    {{-- Include dynamic template --}}
    @include($templateView ?? 'tallcms::templates.default', [
        'page' => $page,
        'renderedContent' => $renderedContent,
        'allPages' => $allPages,
        'sidebarWidgets' => $sidebarWidgets ?? [],
        'templateConfig' => $templateConfig ?? [],
    ])
@endif
