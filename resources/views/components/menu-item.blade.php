@props(['item', 'level' => 0])

@php
    $hasChildren = !empty($item['children']);
    $isDropdown = $hasChildren && $level === 0;
    $isActive = $item['is_active'] ?? false;
    $hasActiveChild = $item['has_active_child'] ?? false;

    $itemClasses = collect([
        'relative',
        'group' => $isDropdown,
        $item['css_class'] ?? null,
    ])->filter()->join(' ');

    // Active state classes
    $linkClasses = $level === 0
        ? ($isActive
            ? 'text-primary-600 font-semibold'
            : ($hasActiveChild ? 'text-primary-600' : 'text-gray-700 hover:text-gray-900'))
        : ($isActive
            ? 'text-primary-600 bg-primary-50 font-medium'
            : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50');
@endphp

<li class="{{ $itemClasses }}">
    @if($item['url'] && !in_array($item['type'], ['header', 'separator']))
        <a href="{{ $item['url'] }}"
           class="flex items-center px-3 py-2 rounded-md font-medium transition-colors duration-200 {{ $linkClasses }}"
           @if($isActive) aria-current="page" @endif
           @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
           
            @if($item['icon'])
                <i class="{{ $item['icon'] }} {{ $level === 0 ? 'mr-2' : 'mr-3 w-4 h-4' }}"></i>
            @endif
            
            <span>{{ $item['label'] }}</span>
            
            @if($hasChildren && $level === 0)
                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            @endif
        </a>
    @elseif($item['type'] === 'header')
        <span class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider 
                     {{ $level === 0 ? '' : 'border-b border-gray-100' }}">
            {{ $item['label'] }}
        </span>
    @elseif($item['type'] === 'separator')
        @if($level === 0)
            <div class="w-px h-6 bg-gray-300 mx-2"></div>
        @else
            <hr class="my-1 border-gray-200" />
        @endif
    @else
        {{-- Fallback for items without URLs --}}
        <span class="flex items-center px-3 py-2 text-sm font-medium text-gray-400 cursor-default">
            @if($item['icon'])
                <i class="{{ $item['icon'] }} {{ $level === 0 ? 'mr-2' : 'mr-3 w-4 h-4' }}"></i>
            @endif
            {{ $item['label'] }}
        </span>
    @endif

    @if($hasChildren)
        <ul class="{{ $level === 0 
            ? 'absolute left-0 top-full mt-1 w-56 bg-white rounded-md shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50'
            : 'ml-4 mt-1 space-y-1' 
        }}">
            @foreach($item['children'] as $child)
                <x-menu-item :item="$child" :level="$level + 1" />
            @endforeach
        </ul>
    @endif
</li>