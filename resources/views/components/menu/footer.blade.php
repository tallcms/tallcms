{{-- Footer Menu Style (minimal, compact) --}}
<ul class="menu menu-horizontal flex-wrap gap-2">
    @foreach($items as $item)
        @php $isActive = $item['is_active'] ?? false; @endphp
        @if($item['url'] && !in_array($item['type'], ['header', 'separator']))
            <li>
                <a href="{{ $item['url'] }}"
                   class="{{ $isActive ? 'active' : '' }}"
                   @if($isActive) aria-current="page" @endif
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                    {{ $item['label'] }}
                </a>
            </li>
        @elseif($item['type'] === 'separator')
            <li class="divider divider-horizontal"></li>
        @endif
    @endforeach
</ul>
