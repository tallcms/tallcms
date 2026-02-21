@props(['post', 'config' => [], 'parentSlug' => ''])

@php
    use Illuminate\Support\Facades\View;

    // Display settings from PostsBlock config
    $showDate = $config['show_date'] ?? true;
    $showAuthor = $config['show_author'] ?? true;
    $showImage = $config['show_image'] ?? true;
    $showExcerpt = $config['show_excerpt'] ?? true;

    // Save previous values to restore after rendering (avoid global bleed)
    $previousCmsPageSlug = View::shared('cmsPageSlug');
    $previousCmsPageContentWidth = View::shared('cmsPageContentWidth');

    // Share parent page slug with blocks rendered within post content
    // This ensures Posts blocks inside posts use the correct parent page slug
    View::share('cmsPageSlug', $parentSlug);

    // Share post content width so blocks can inherit it
    // Posts use max-w-4xl which maps to 'prose' width
    View::share('cmsPageContentWidth', 'prose');
@endphp

<div class="max-w-4xl mx-auto px-4 py-16">
    <article>
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-base-content mb-4">{{ $post->title }}</h1>

            @if($showDate || $showAuthor)
                <div class="flex items-center text-base-content/60 text-sm space-x-4">
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

        @if($showImage && $post->featured_image)
            <div class="mb-8">
                <img src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                     alt="{{ $post->title }}"
                     class="w-full rounded-lg">
            </div>
        @endif

        @if($showExcerpt && $post->excerpt)
            <div class="text-xl text-base-content/70 mb-8 leading-relaxed">
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

    @if(config('tallcms.comments.enabled', true) && ($config['show_comments'] ?? true) && $post->isPublished())
        <x-tallcms::comments :post="$post" />
    @endif
</div>

@php
    // Restore previous View::share values to avoid global bleed
    View::share('cmsPageSlug', $previousCmsPageSlug);
    View::share('cmsPageContentWidth', $previousCmsPageContentWidth);
@endphp
