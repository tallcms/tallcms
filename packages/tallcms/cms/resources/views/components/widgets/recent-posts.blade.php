@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $limit = (int) ($settings['limit'] ?: 5);
    $showImage = (bool) ($settings['show_image'] ?? true);

    $posts = \TallCms\Cms\Models\CmsPost::published()
        ->orderBy('published_at', 'desc')
        ->limit($limit)
        ->get();

    // Use the current page slug for post URLs
    $parentSlug = $page?->slug ?? '';
@endphp

@if($posts->isNotEmpty())
<div class="bg-base-100 rounded-lg p-4 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Recent Posts</h3>
    <ul class="space-y-4">
        @foreach($posts as $post)
            <li class="flex gap-3">
                @if($showImage && $post->featured_image)
                    <a href="{{ cms_post_url($post, $parentSlug) }}" class="flex-shrink-0">
                        <img
                            src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                            alt="{{ $post->title }}"
                            class="w-16 h-16 object-cover rounded-lg"
                        >
                    </a>
                @endif
                <div class="min-w-0">
                    <a href="{{ cms_post_url($post, $parentSlug) }}" class="link link-hover font-medium text-sm line-clamp-2">
                        {{ $post->title }}
                    </a>
                    <p class="text-xs text-base-content/60 mt-1">
                        {{ $post->published_at->format('M j, Y') }}
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif
