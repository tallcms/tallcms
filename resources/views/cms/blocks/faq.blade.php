@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
    ])->join('; ') . ';';

    $textAlignClass = match($text_alignment ?? 'center') {
        'left' => 'text-left',
        'center' => 'text-center',
        default => 'text-center',
    };

    $isAccordion = ($style ?? 'accordion') === 'accordion';
    $sectionSpacing = ($first_section ?? false) ? 'pt-0' : 'pt-16 sm:pt-24';
    $uniqueId = 'faq-' . uniqid();
@endphp

<section
    class="faq-block {{ $sectionSpacing }} pb-16 sm:pb-24"
    style="{{ $customProperties }}"
    @if($isAccordion)
        x-data="{
            activeItem: {{ ($first_open ?? false) ? '0' : 'null' }},
            allowMultiple: {{ ($allow_multiple ?? false) ? 'true' : 'false' }},
            openItems: {{ ($first_open ?? false) ? '[0]' : '[]' }},
            toggle(index) {
                if (this.allowMultiple) {
                    const idx = this.openItems.indexOf(index);
                    if (idx > -1) {
                        this.openItems.splice(idx, 1);
                    } else {
                        this.openItems.push(index);
                    }
                } else {
                    this.activeItem = this.activeItem === index ? null : index;
                }
            },
            isOpen(index) {
                return this.allowMultiple ? this.openItems.includes(index) : this.activeItem === index;
            }
        }"
    @endif
>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="{{ $textAlignClass }} mb-12">
                @if(!empty($heading))
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-4 text-lg max-w-2xl {{ $textAlignClass === 'text-center' ? 'mx-auto' : '' }}" style="color: var(--block-text-color);">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- FAQ Items --}}
        @if(!empty($items))
            <div class="space-y-4">
                @foreach($items as $index => $item)
                    @if($isAccordion)
                        {{-- Accordion Style --}}
                        <div class="faq-item rounded-lg bg-white dark:bg-gray-800 overflow-hidden">
                            <button
                                type="button"
                                class="faq-question w-full px-6 py-4 text-left flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                @click="toggle({{ $index }})"
                                :aria-expanded="isOpen({{ $index }})"
                                aria-controls="{{ $uniqueId }}-answer-{{ $index }}"
                            >
                                <span class="font-semibold text-base sm:text-lg" style="color: var(--block-heading-color);">
                                    {{ $item['question'] }}
                                </span>
                                <span class="flex-shrink-0 ml-2">
                                    <x-heroicon-o-chevron-down
                                        class="w-5 h-5 transition-transform duration-200"
                                        style="color: var(--block-text-color);"
                                        x-bind:class="{ 'rotate-180': isOpen({{ $index }}) }"
                                    />
                                </span>
                            </button>
                            <div
                                id="{{ $uniqueId }}-answer-{{ $index }}"
                                x-show="isOpen({{ $index }})"
                                x-collapse
                                x-cloak
                            >
                                <div class="faq-answer px-6 pb-4 pt-0">
                                    <p class="leading-relaxed" style="color: var(--block-text-color);">
                                        {!! nl2br(e($item['answer'])) !!}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- List Style (Always Visible) --}}
                        <div class="faq-item rounded-lg bg-white dark:bg-gray-800 p-6">
                            <h3 class="faq-question font-semibold text-base sm:text-lg mb-3" style="color: var(--block-heading-color);">
                                {{ $item['question'] }}
                            </h3>
                            <p class="faq-answer leading-relaxed" style="color: var(--block-text-color);">
                                {!! nl2br(e($item['answer'])) !!}
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Schema.org FAQPage Structured Data --}}
    @if(($show_schema ?? true) && !empty($items))
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "FAQPage",
                "mainEntity": [
                    @foreach($items as $index => $item)
                    {
                        "@type": "Question",
                        "name": @json($item['question']),
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": @json($item['answer'])
                        }
                    }@if(!$loop->last),@endif
                    @endforeach
                ]
            }
        </script>
    @endif
</section>
