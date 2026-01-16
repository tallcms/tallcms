{{-- Footer Vertical Menu Style (for footer columns) --}}
<ul class="space-y-2">
    @foreach($items as $item)
        @php $isActive = $item['is_active'] ?? false; @endphp
        @if($item['url'] && !in_array($item['type'], ['header', 'separator']))
            <li>
                <a href="{{ $item['url'] }}"
                   class="text-gray-600 hover:text-gray-900 transition-colors duration-200 {{ $isActive ? 'text-gray-900 font-medium' : '' }}"
                   @if($isActive) aria-current="page" @endif
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                    {{ $item['label'] }}
                </a>
            </li>
        @endif
    @endforeach
</ul>
