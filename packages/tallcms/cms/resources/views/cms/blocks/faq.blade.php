@php
    $isAccordion = ($style ?? 'accordion') === 'accordion';
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
    $uniqueId = 'faq-' . uniqid();
@endphp

<section
    @if($anchor_id ?? null) id="{{ $anchor_id }}" @endif
    class="faq-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
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
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="{{ $text_alignment ?? 'text-center' }} mb-12">
                @if(!empty($heading))
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-base-content">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-4 text-lg text-base-content/70 max-w-2xl {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'mx-auto' : '' }}">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- FAQ Items --}}
        @if(!empty($items))
            <div class="join join-vertical w-full">
                @foreach($items as $index => $item)
                    @if($isAccordion)
                        {{-- Accordion Style using daisyUI collapse --}}
                        <div class="collapse collapse-arrow join-item border border-base-300 bg-base-200"
                             :class="{ 'collapse-open': isOpen({{ $index }}) }">
                            <input type="radio"
                                   name="{{ $uniqueId }}"
                                   @click="toggle({{ $index }})"
                                   :checked="isOpen({{ $index }})"
                            />
                            <div class="collapse-title text-lg font-semibold text-base-content">
                                {{ $item['question'] }}
                            </div>
                            <div class="collapse-content">
                                <p class="text-base-content/80 leading-relaxed pt-2">
                                    {!! nl2br(e($item['answer'])) !!}
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- List Style (Always Visible) --}}
                        <div class="card bg-base-200 mb-4">
                            <div class="card-body">
                                <h3 class="card-title text-base-content">
                                    {{ $item['question'] }}
                                </h3>
                                <p class="text-base-content/80 leading-relaxed">
                                    {!! nl2br(e($item['answer'])) !!}
                                </p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Schema.org FAQPage Structured Data --}}
    @if(($show_schema ?? true) && !empty($items))
        @php
            $schemaItems = array_map(fn($item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ], $items);
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $schemaItems,
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
</section>
