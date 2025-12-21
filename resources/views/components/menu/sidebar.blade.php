{{-- Sidebar Menu Style (vertical with collapsible sections) --}}
<nav class="space-y-4">
    @foreach($items as $item)
        @php
            $hasChildren = !empty($item['children']);
            $isActive = $item['is_active'] ?? false;
            $hasActiveChild = $item['has_active_child'] ?? false;
        @endphp

        @if($item['type'] === 'header')
            <div class="px-3 py-2">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    {{ $item['label'] }}
                </h3>
            </div>
        @elseif($item['type'] === 'separator')
            <hr class="border-gray-200" />
        @elseif($hasChildren)
            {{-- Collapsible menu section - auto-open if has active child --}}
            <div x-data="{ open: {{ $hasActiveChild ? 'true' : 'false' }} }" class="space-y-1">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200
                               {{ $hasActiveChild ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100' }}">
                    <span class="flex items-center">
                        @if($item['icon'])
                            <i class="{{ $item['icon'] }} mr-3 w-5 h-5 {{ $hasActiveChild ? 'text-primary-500' : 'text-gray-400' }}"></i>
                        @endif
                        {{ $item['label'] }}
                    </span>
                    <svg class="w-4 h-4 {{ $hasActiveChild ? 'text-primary-500' : 'text-gray-400' }} transition-transform duration-200"
                         :class="{ 'rotate-180': open }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 transform -translate-y-1"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-cloak
                     class="ml-6 space-y-1">
                    @foreach($item['children'] as $child)
                        @php $childActive = $child['is_active'] ?? false; @endphp
                        @if($child['url'] && !in_array($child['type'], ['header', 'separator']))
                            <a href="{{ $child['url'] }}"
                               class="flex items-center px-3 py-2 text-sm rounded-md transition-colors duration-200
                                      {{ $childActive ? 'text-primary-600 bg-primary-50 font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                               @if($childActive) aria-current="page" @endif
                               @if($child['target'] === '_blank') target="_blank" rel="noopener" @endif>
                                @if($child['icon'])
                                    <i class="{{ $child['icon'] }} mr-2 w-4 h-4 {{ $childActive ? 'text-primary-500' : 'text-gray-400' }}"></i>
                                @endif
                                {{ $child['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @elseif($item['url'])
            {{-- Regular menu item --}}
            <a href="{{ $item['url'] }}"
               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ $item['css_class'] ?? '' }}
                      {{ $isActive ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100' }}"
               @if($isActive) aria-current="page" @endif
               @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                @if($item['icon'])
                    <i class="{{ $item['icon'] }} mr-3 w-5 h-5 {{ $isActive ? 'text-primary-500' : 'text-gray-400' }}"></i>
                @endif
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
