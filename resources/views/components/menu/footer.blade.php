{{-- Footer Menu Style (minimal, compact) --}}
<ul class="flex flex-wrap items-center space-x-6 text-sm text-gray-500">
    @foreach($items as $item)
        @if($item['url'] && !in_array($item['type'], ['header', 'separator']))
            <li>
                <a href="{{ $item['url'] }}" 
                   class="hover:text-gray-700 transition-colors duration-200"
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                    {{ $item['label'] }}
                </a>
            </li>
        @elseif($item['type'] === 'separator')
            <li class="w-px h-4 bg-gray-300"></li>
        @endif
    @endforeach
</ul>