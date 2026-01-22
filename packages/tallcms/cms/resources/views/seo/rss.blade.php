<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ $feedTitle }}</title>
        <link>{{ $feedLink }}</link>
        <description>{{ $feedDescription }}</description>
        <language>{{ app()->getLocale() }}</language>
        <lastBuildDate>{{ $lastBuildDate->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ $feedUrl }}" rel="self" type="application/rss+xml"/>
        <generator>TallCMS</generator>

        @foreach($posts as $post)
            @php
                $postUrl = \TallCms\Cms\Services\SeoService::getPostUrl($post);

                // Get description - use excerpt, or render content and extract plain text
                $description = $post->excerpt;
                if (empty($description) && $post->content) {
                    $rendered = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($post->content)
                        ->customBlocks(\TallCms\Cms\Services\CustomBlockDiscoveryService::getBlocksArray())
                        ->toUnsafeHtml();
                    $description = Str::limit(strip_tags($rendered), 300);
                }
            @endphp
            <item>
                <title><![CDATA[{{ $post->title }}]]></title>
                <link>{{ $postUrl }}</link>
                <guid isPermaLink="true">{{ $postUrl }}</guid>
                <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>

                @if($post->author)
                    <dc:creator><![CDATA[{{ $post->author->name }}]]></dc:creator>
                @endif

                @foreach($post->categories as $category)
                    <category><![CDATA[{{ $category->name }}]]></category>
                @endforeach

                <description><![CDATA[{{ $description }}]]></description>

                @if($includeFullContent && $post->content)
                    @php
                        $renderedContent = \Filament\Forms\Components\RichEditor\RichContentRenderer::make($post->content)
                            ->customBlocks(\TallCms\Cms\Services\CustomBlockDiscoveryService::getBlocksArray())
                            ->toUnsafeHtml();
                    @endphp
                    <content:encoded><![CDATA[{!! $renderedContent !!}]]></content:encoded>
                @endif

                @if($post->featured_image)
                    <enclosure url="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}" type="image/jpeg"/>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
