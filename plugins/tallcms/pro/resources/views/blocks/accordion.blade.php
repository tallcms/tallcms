@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-border-color: ' . ($textPreset['border'] ?? '#e5e7eb'),
    ])->join('; ') . ';';

    $uniqueId = 'accordion-' . uniqid();

    $styleClasses = match($style ?? 'default') {
        'bordered' => 'border border-gray-200 dark:border-gray-700',
        'minimal' => 'border-b border-gray-200 dark:border-gray-700 last:border-b-0',
        default => 'bg-white dark:bg-gray-800 shadow-sm',
    };

    $containerClasses = match($style ?? 'default') {
        'bordered' => 'border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700 overflow-hidden',
        'minimal' => '',
        default => 'space-y-3',
    };
@endphp

<section
    class="pro-accordion-block py-12 sm:py-16"
    style="{{ $customProperties }}"
    x-data="{
        allowMultiple: {{ ($allow_multiple ?? false) ? 'true' : 'false' }},
        openItems: {{ ($first_open ?? true) ? '[0]' : '[]' }},
        toggle(index) {
            if (this.allowMultiple) {
                const idx = this.openItems.indexOf(index);
                if (idx > -1) {
                    this.openItems.splice(idx, 1);
                } else {
                    this.openItems.push(index);
                }
            } else {
                if (this.openItems.includes(index)) {
                    this.openItems = [];
                } else {
                    this.openItems = [index];
                }
            }
        },
        isOpen(index) {
            return this.openItems.includes(index);
        }
    }"
>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
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

        {{-- Accordion Items --}}
        @if(!empty($items))
            <div class="{{ $containerClasses }}">
                @foreach($items as $index => $item)
                    <div class="accordion-item {{ $style !== 'bordered' ? $styleClasses : '' }} {{ $style === 'default' ? 'rounded-lg overflow-hidden' : '' }}">
                        <button
                            type="button"
                            class="accordion-trigger w-full px-5 py-4 text-left flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 transition-colors {{ $style === 'minimal' ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                            @click="toggle({{ $index }})"
                            :aria-expanded="isOpen({{ $index }})"
                            aria-controls="{{ $uniqueId }}-content-{{ $index }}"
                        >
                            <span class="font-semibold text-base" style="color: var(--block-heading-color);">
                                {{ $item['title'] ?? '' }}
                            </span>
                            <span class="flex-shrink-0">
                                <svg
                                    class="w-5 h-5 transition-transform duration-200"
                                    style="color: var(--block-text-color);"
                                    :class="{ 'rotate-180': isOpen({{ $index }}) }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div
                            id="{{ $uniqueId }}-content-{{ $index }}"
                            x-show="isOpen({{ $index }})"
                            x-collapse
                            x-cloak
                        >
                            <div class="accordion-content px-5 pb-4 pt-0">
                                <div class="prose prose-sm dark:prose-invert max-w-none" style="color: var(--block-text-color);">
                                    {!! nl2br(e($item['content'] ?? '')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>No accordion items configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
