{{-- Check for special rendering modes --}}
@if($renderedContent === 'WELCOME_PAGE')
    @include('welcome.tallcms')
@elseif($renderedContent === 'POST_DETAIL')
    @if($templateConfig['has_sidebar'] ?? false)
        {{-- Template has sidebar: embed post detail to inherit sidebar/widgets --}}
        @include($templateView, [
            'page' => $page,
            'renderedContent' => view('tallcms::partials.post-detail', [
                'post' => $post,
                'config' => $postsBlockConfig ?? [],
                'parentSlug' => $parentSlug,
                'embedded' => true,
            ])->render(),
            'allPages' => [],
            'sidebarWidgets' => $sidebarWidgets ?? [],
            'templateConfig' => $templateConfig ?? [],
        ])
    @else
        {{-- No sidebar: render standalone post view --}}
        @include('cms.posts.show', [
            'post' => $post,
            'config' => $postsBlockConfig ?? [],
            'parentSlug' => $parentSlug,
        ])
    @endif
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
