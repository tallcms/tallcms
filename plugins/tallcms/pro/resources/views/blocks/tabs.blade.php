@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-primary-hover: ' . ($textPreset['link_hover'] ?? '#1d4ed8'),
    ])->join('; ') . ';';

    $uniqueId = 'tabs-' . uniqid();
    $isVertical = ($layout ?? 'horizontal') === 'vertical';

    $alignmentClass = match($alignment ?? 'left') {
        'center' => 'justify-center',
        'right' => 'justify-end',
        'full' => 'w-full',
        default => 'justify-start',
    };
@endphp

<section
    class="pro-tabs-block py-12 sm:py-16"
    style="{{ $customProperties }}"
    x-data="{ activeTab: 0 }"
>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-10">
                @if(!empty($heading))
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-3 text-lg max-w-2xl mx-auto" style="color: var(--block-text-color);">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Tabs --}}
        @if(!empty($tabs))
            <div class="{{ $isVertical ? 'flex flex-col md:flex-row gap-6' : '' }}">
                {{-- Tab Navigation --}}
                <div
                    class="{{ $isVertical ? 'md:w-1/4 flex-shrink-0' : 'mb-6' }}"
                    role="tablist"
                    aria-label="Tabs"
                >
                    <div class="flex {{ $isVertical ? 'flex-col space-y-2' : 'flex-wrap gap-2 ' . $alignmentClass }}">
                        @foreach($tabs as $index => $tab)
                            <button
                                type="button"
                                role="tab"
                                :id="'{{ $uniqueId }}-tab-{{ $index }}'"
                                :aria-selected="activeTab === {{ $index }}"
                                :aria-controls="'{{ $uniqueId }}-panel-{{ $index }}'"
                                :tabindex="activeTab === {{ $index }} ? 0 : -1"
                                @click="activeTab = {{ $index }}"
                                @keydown.arrow-right.prevent="activeTab = (activeTab + 1) % {{ count($tabs) }}"
                                @keydown.arrow-left.prevent="activeTab = (activeTab - 1 + {{ count($tabs) }}) % {{ count($tabs) }}"
                                class="tab-button px-4 py-2.5 text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500
                                    @if($style === 'pills')
                                        rounded-lg
                                    @elseif($style === 'underline')
                                        border-b-2 rounded-none
                                    @else
                                        rounded-t-lg border border-b-0
                                    @endif
                                    {{ $alignment === 'full' && !$isVertical ? 'flex-1 text-center' : '' }}
                                "
                                :class="{
                                    @if($style === 'pills')
                                        'bg-primary-600 text-white shadow-sm': activeTab === {{ $index }},
                                        'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== {{ $index }}
                                    @elseif($style === 'underline')
                                        'border-primary-600 text-primary-600': activeTab === {{ $index }},
                                        'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-300': activeTab !== {{ $index }}
                                    @else
                                        'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white': activeTab === {{ $index }},
                                        'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== {{ $index }}
                                    @endif
                                }"
                            >
                                <span class="flex items-center gap-2 {{ $alignment === 'full' && !$isVertical ? 'justify-center' : '' }}">
                                    {{ $tab['title'] ?? '' }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Tab Panels --}}
                <div class="{{ $isVertical ? 'md:flex-1' : '' }} {{ $style === 'boxed' ? 'border border-gray-200 dark:border-gray-700 rounded-b-lg bg-white dark:bg-gray-800' : '' }}">
                    @foreach($tabs as $index => $tab)
                        <div
                            role="tabpanel"
                            :id="'{{ $uniqueId }}-panel-{{ $index }}'"
                            :aria-labelledby="'{{ $uniqueId }}-tab-{{ $index }}'"
                            x-show="activeTab === {{ $index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform translate-y-1"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            class="tab-panel {{ $style === 'boxed' ? 'p-6' : 'pt-4' }}"
                        >
                            <div class="prose prose-sm dark:prose-invert max-w-none" style="color: var(--block-text-color);">
                                {!! $tab['content'] ?? '' !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>No tabs configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
