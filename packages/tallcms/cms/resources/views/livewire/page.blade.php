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
    <div class="max-w-4xl mx-auto px-4 py-16">
        <article>
            <header class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $post->title }}</h1>

                <div class="flex items-center text-gray-600 text-sm space-x-4">
                    @if($post->published_at)
                        <time datetime="{{ $post->published_at->toISOString() }}">
                            {{ $post->published_at->format('F j, Y') }}
                        </time>
                    @endif

                    @if($post->author)
                        <span>by {{ $post->author->name ?? $post->author }}</span>
                    @endif
                </div>
            </header>

            @if($post->featured_image)
                <div class="mb-8">
                    <img src="{{ Storage::url($post->featured_image) }}"
                         alt="{{ $post->title }}"
                         class="w-full rounded-lg">
                </div>
            @endif

            @if($post->excerpt)
                <div class="text-xl text-gray-600 mb-8 leading-relaxed">
                    {{ $post->excerpt }}
                </div>
            @endif

            <div class="prose prose-lg max-w-none">
                @php
                    $content = $post->content;
                    // Check if content is raw HTML (string) or Tiptap JSON (array)
                    if (is_string($content)) {
                        $postContent = $content;
                    } elseif (is_array($content)) {
                        $postContent = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($content)
                            ->customBlocks(\TallCms\Cms\Services\CustomBlockDiscoveryService::getBlocksArray())
                            ->toUnsafeHtml();
                    } else {
                        $postContent = '';
                    }
                    $postContent = \TallCms\Cms\Services\MergeTagService::replaceTags($postContent, $post);
                @endphp
                {!! $postContent !!}
            </div>
        </article>
    </div>
@else
    {{-- Block Canvas --}}
    <div class="cms-content w-full">
        {{-- Homepage content --}}
        <section id="top">
            {!! $renderedContent !!}
        </section>

        {{-- SPA Mode: Other pages as sections --}}
        @foreach($allPages as $pageData)
            <section id="{{ $pageData['anchor'] }}">
                {!! $pageData['content'] !!}
            </section>
        @endforeach
    </div>
@endif
