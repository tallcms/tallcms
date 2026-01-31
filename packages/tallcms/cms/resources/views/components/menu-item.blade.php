@props(['item', 'level' => 0])

@php
    $hasChildren = !empty($item['children']);
    $isActive = $item['is_active'] ?? false;
    $hasActiveChild = $item['has_active_child'] ?? false;
@endphp

<li class="{{ $item['css_class'] ?? '' }}">
    @if($item['type'] === 'header')
        <li class="menu-title">{{ $item['label'] }}</li>
    @elseif($item['type'] === 'separator')
        <li></li>
    @elseif($hasChildren)
        <details {{ $hasActiveChild ? 'open' : '' }}>
            <summary class="{{ $isActive || $hasActiveChild ? 'active' : '' }}">
                @if($item['icon'])
                    <i class="{{ $item['icon'] }}"></i>
                @endif
                {{ $item['label'] }}
            </summary>
            <ul class="bg-base-100 rounded-t-none p-2 min-w-max z-50">
                @foreach($item['children'] as $child)
                    @include('tallcms::components.menu-item', ['item' => $child, 'level' => $level + 1])
                @endforeach
            </ul>
        </details>
    @elseif($item['url'])
        <a href="{{ $item['url'] }}"
           class="{{ $isActive ? 'active' : '' }}"
           @if($isActive) aria-current="page" @endif
           @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
            @if($item['icon'])
                <i class="{{ $item['icon'] }}"></i>
            @endif
            {{ $item['label'] }}
        </a>
    @else
        <span class="menu-disabled">
            @if($item['icon'])
                <i class="{{ $item['icon'] }}"></i>
            @endif
            {{ $item['label'] }}
        </span>
    @endif
</li>
