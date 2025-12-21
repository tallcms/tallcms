{{-- Footer Menu Style (minimal, compact) --}}
<ul class="flex flex-wrap items-center text-sm text-gray-500">
    @foreach($items as $item)
        @php $isActive = $item['is_active'] ?? false; @endphp
        @if($item['url'] && !in_array($item['type'], ['header', 'separator']))
            <li class="px-4 py-2">
                <a href="{{ $item['url'] }}"
                   class="transition-colors duration-200 {{ $isActive ? 'text-primary-600 font-medium' : 'hover:text-gray-700' }}"
                   @if($isActive) aria-current="page" @endif
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                    {{ $item['label'] }}
                </a>
            </li>
        @elseif($item['type'] === 'separator')
            <li class="w-px h-4 bg-gray-300 mx-2"></li>
        @endif
    @endforeach
</ul>
