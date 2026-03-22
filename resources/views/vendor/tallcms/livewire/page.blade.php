{{-- Check for special rendering modes --}}
@if($renderedContent === 'WELCOME_PAGE')
    @include('welcome.tallcms')
@elseif($renderedContent === 'POST_DETAIL')
    {{-- Render post detail within the parent page's template (inherits sidebar/widgets) --}}
    @include($templateView ?? 'tallcms::templates.default', [
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
    {{-- Include dynamic template --}}
    @include($templateView ?? 'tallcms::templates.default', [
        'page' => $page,
        'renderedContent' => $renderedContent,
        'allPages' => $allPages,
        'sidebarWidgets' => $sidebarWidgets ?? [],
        'templateConfig' => $templateConfig ?? [],
    ])
@endif
