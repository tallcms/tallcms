{{-- Mobile Menu Style (optimized for touch with larger tap targets) --}}
<ul class="menu menu-lg w-full">
    @foreach($items as $item)
        @php
            $hasChildren = !empty($item['children']);
            $isActive = $item['is_active'] ?? false;
            $hasActiveChild = $item['has_active_child'] ?? false;
        @endphp

        @if($item['type'] === 'header')
            <li class="menu-title">{{ $item['label'] }}</li>
        @elseif($item['type'] === 'separator')
            <li></li>
        @elseif($hasChildren)
            <li>
                <details {{ $hasActiveChild ? 'open' : '' }}>
                    <summary class="{{ $isActive || $hasActiveChild ? 'active' : '' }}">
                        @if($item['icon'])
                            <i class="{{ $item['icon'] }}"></i>
                        @endif
                        {{ $item['label'] }}
                    </summary>
                    <ul>
                        @foreach($item['children'] as $child)
                            @php $childActive = $child['is_active'] ?? false; @endphp
                            @if($child['url'] && !in_array($child['type'], ['header', 'separator']))
                                <li>
                                    <a href="{{ $child['url'] }}"
                                       class="{{ $childActive ? 'active' : '' }}"
                                       @if($childActive) aria-current="page" @endif
                                       @if($child['target'] === '_blank') target="_blank" rel="noopener" @endif>
                                        @if($child['icon'])
                                            <i class="{{ $child['icon'] }}"></i>
                                        @endif
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </details>
            </li>
        @elseif($item['url'])
            <li class="{{ $item['css_class'] ?? '' }}">
                <a href="{{ $item['url'] }}"
                   class="{{ $isActive ? 'active' : '' }}"
                   @if($isActive) aria-current="page" @endif
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                    @if($item['icon'])
                        <i class="{{ $item['icon'] }}"></i>
                    @endif
                    {{ $item['label'] }}
                </a>
            </li>
        @endif
    @endforeach
</ul>
