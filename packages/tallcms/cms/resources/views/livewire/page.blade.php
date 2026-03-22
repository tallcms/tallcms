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
    @if($templateConfig['has_sidebar'] ?? false)
        {{-- Template has sidebar: embed post detail to inherit sidebar/widgets --}}
        @include($templateView, [
            'page' => $page,
            'renderedContent' => view('tallcms::partials.post-detail', ['post' => $post, 'config' => $postsBlockConfig, 'parentSlug' => $parentSlug, 'embedded' => true])->render(),
            'allPages' => [],
            'sidebarWidgets' => $sidebarWidgets ?? [],
            'templateConfig' => $templateConfig ?? [],
        ])
    @else
        {{-- No sidebar: render standalone post detail --}}
        @include('tallcms::partials.post-detail', ['post' => $post, 'config' => $postsBlockConfig, 'parentSlug' => $parentSlug])
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
