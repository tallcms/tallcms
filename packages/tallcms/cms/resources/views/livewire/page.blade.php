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

                @php
                    // Get display settings from PostsBlock (shared via View::share)
                    $showDate = View::shared('postsBlockShowDate', true);
                    $showAuthor = View::shared('postsBlockShowAuthor', true);
                @endphp
                @if($showDate || $showAuthor)
                    <div class="flex items-center text-gray-600 text-sm space-x-4">
                        @if($showDate && $post->published_at)
                            <time datetime="{{ $post->published_at->toISOString() }}">
                                {{ $post->published_at->format('F j, Y') }}
                            </time>
                        @endif

                        @if($showAuthor && $post->author)
                            <span>by {{ $post->author->name ?? $post->author }}</span>
                        @endif
                    </div>
                @endif
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

            @php
                $content = $post->content;
                $contentStr = is_string($content) ? $content : json_encode($content);

                // Check if content needs block rendering:
                // - Tiptap JSON (has "type":"doc")
                // - HTML with embedded customBlock divs (from admin editor)
                $needsBlockRendering = str_contains($contentStr, '"type":"doc"')
                    || str_contains($contentStr, 'data-type="customBlock"')
                    || str_contains($contentStr, "data-type='customBlock'");

                if ($needsBlockRendering) {
                    // Use RichContentRenderer for Tiptap JSON or HTML with blocks
                    $postContent = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($content)
                        ->customBlocks(\TallCms\Cms\Services\CustomBlockDiscoveryService::getBlocksArray())
                        ->toUnsafeHtml();
                } else {
                    // Raw HTML - output directly
                    $postContent = is_string($content) ? $content : '';
                }

                // Always add IDs to headings that don't have them (for TOC anchor links)
                // This handles both: RichContentRenderer stripping IDs, and admin edits removing IDs
                $postContent = preg_replace_callback(
                    '/<(h[2-4])([^>]*)>([^<]+)<\/h[2-4]>/i',
                    function ($matches) {
                        $tag = $matches[1];
                        $attrs = $matches[2];
                        $text = $matches[3];

                        // Skip if already has an id attribute
                        if (preg_match('/\bid\s*=/i', $attrs)) {
                            return $matches[0];
                        }

                        $id = \Illuminate\Support\Str::slug($text);
                        return "<{$tag} id=\"{$id}\"{$attrs}>{$text}</{$tag}>";
                    },
                    $postContent
                );

                // Fix internal anchor links - RichEditor adds target="_blank" and rel attributes
                // to all links, but internal anchors (#...) should not have these
                $postContent = preg_replace_callback(
                    '/<a([^>]*href="#[^"]*"[^>]*)>/i',
                    function ($matches) {
                        $attrs = $matches[1];
                        // Remove target="_blank" and rel="..." from internal anchor links
                        $attrs = preg_replace('/\s*target="_blank"/i', '', $attrs);
                        $attrs = preg_replace('/\s*rel="[^"]*"/i', '', $attrs);
                        return "<a{$attrs}>";
                    },
                    $postContent
                );

                $postContent = \TallCms\Cms\Services\MergeTagService::replaceTags($postContent, $post);
            @endphp
            {{-- Prose for text styling; blocks use not-prose class to exclude themselves --}}
            <div class="prose prose-lg max-w-none">
                {!! $postContent !!}
            </div>
        </article>
    </div>
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
