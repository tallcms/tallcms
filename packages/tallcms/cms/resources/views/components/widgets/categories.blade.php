@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $showCount = (bool) ($settings['show_count'] ?? true);

    $categories = \TallCms\Cms\Models\CmsCategory::query()
        ->when($showCount, fn($q) => $q->withCount('posts'))
        ->orderBy('name')
        ->get();
@endphp

@if($categories->isNotEmpty())
<div class="bg-base-100 rounded-lg p-4 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Categories</h3>
    <ul class="space-y-2">
        @foreach($categories as $category)
            <li class="flex justify-between items-center">
                <a href="{{ Route::has('tallcms.category.show') ? route('tallcms.category.show', $category->slug) : url('/category/' . $category->slug) }}" class="link link-hover text-sm">
                    {{ $category->name }}
                </a>
                @if($showCount)
                    <span class="badge badge-ghost badge-sm">{{ $category->posts_count }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif
