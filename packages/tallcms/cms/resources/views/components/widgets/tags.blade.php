@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $style = $settings['style'] ?? 'cloud';

    // Get unique tags from all published posts
    $tags = \TallCms\Cms\Models\CmsPost::published()
        ->whereNotNull('tags')
        ->get()
        ->pluck('tags')
        ->flatten()
        ->filter()
        ->countBy()
        ->sortDesc();
@endphp

@if($tags->isNotEmpty())
<div class="bg-base-100 rounded-lg p-4 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Tags</h3>

    @if($style === 'cloud')
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag => $count)
                <span class="badge badge-outline badge-sm hover:badge-primary cursor-pointer transition-colors">
                    {{ $tag }}
                </span>
            @endforeach
        </div>
    @else
        <ul class="space-y-2">
            @foreach($tags as $tag => $count)
                <li class="flex justify-between items-center">
                    <span class="text-sm">{{ $tag }}</span>
                    <span class="badge badge-ghost badge-sm">{{ $count }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endif
