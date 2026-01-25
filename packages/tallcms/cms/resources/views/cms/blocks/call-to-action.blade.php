@php
    $hasSecondaryButton = !empty($secondary_button_text) && !empty($secondary_button_url) && $secondary_button_url !== '#';
    $hasButton = !empty($button_text) && !empty($button_url) && $button_url !== '#';

    // Alignment helper classes
    $alignmentClasses = match($text_alignment ?? 'text-center') {
        'text-left' => 'text-left items-start',
        'text-right' => 'text-right items-end',
        default => 'text-center items-center',
    };

    $buttonAlignClass = match($text_alignment ?? 'text-center') {
        'text-left' => 'justify-start',
        'text-right' => 'justify-end',
        default => 'justify-center',
    };
@endphp

<section @if($anchor_id ?? null) id="{{ $anchor_id }}" @endif class="{{ $padding ?? 'py-16' }} px-4 sm:px-6 lg:px-8 {{ $background ?? 'bg-base-200' }} {{ $css_classes ?? '' }}">
    <div class="mx-auto max-w-4xl flex flex-col {{ $alignmentClasses }}">
        @if($title ?? null)
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-base-content mb-6">
                {{ $title }}
            </h2>
        @endif

        @if($description ?? null)
            <p class="text-lg leading-relaxed text-base-content/80 {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'max-w-2xl' : '' }} mb-10">
                {{ $description }}
            </p>
        @endif

        @if($hasButton || $hasSecondaryButton)
            <div class="flex flex-wrap gap-6 {{ $buttonAlignClass }}">
                @if($hasButton)
                    <div class="flex flex-col items-center gap-2">
                        <a href="{{ e($button_url) }}" class="{{ $button_classes ?? 'btn btn-primary' }} gap-2">
                            {{ $button_text }}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        @if($button_microcopy ?? null)
                            <span class="text-sm text-base-content/60">{{ $button_microcopy }}</span>
                        @endif
                    </div>
                @endif
                @if($hasSecondaryButton)
                    <div class="flex flex-col items-center gap-2">
                        <a href="{{ e($secondary_button_url) }}" class="{{ $secondary_button_classes ?? 'btn btn-ghost' }}">
                            {{ $secondary_button_text }}
                        </a>
                        @if($secondary_button_microcopy ?? null)
                            <span class="text-sm text-base-content/60">{{ $secondary_button_microcopy }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
