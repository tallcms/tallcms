@php
    // Button style mapping to daisyUI classes
    $buttonClass = match($button_preset ?? 'primary') {
        'primary' => 'btn btn-primary',
        'secondary' => 'btn btn-secondary',
        'accent' => 'btn btn-accent',
        'success' => 'btn btn-success',
        'warning' => 'btn btn-warning',
        'danger' => 'btn btn-error',
        'neutral' => 'btn btn-neutral',
        'ghost' => 'btn btn-ghost',
        default => 'btn btn-primary',
    };

    // Secondary button style mapping
    $hasSecondaryButton = !empty($secondary_button_text) && !empty($secondary_button_url) && $secondary_button_url !== '#';
    $secondaryButtonClass = match($secondary_button_preset ?? 'outline-primary') {
        'outline-primary' => 'btn btn-outline btn-primary',
        'outline-secondary' => 'btn btn-outline btn-secondary',
        'outline-neutral' => 'btn btn-outline',
        'filled-secondary' => 'btn btn-secondary',
        'filled-neutral' => 'btn btn-neutral',
        'ghost' => 'btn btn-ghost',
        default => 'btn btn-outline btn-primary',
    };

    // Text alignment mapping
    $alignmentClasses = [
        'left' => 'text-left items-start',
        'center' => 'text-center items-center',
        'right' => 'text-right items-end'
    ];

    // Padding size mapping
    $paddingClass = match($padding ?? 'medium') {
        'small' => 'py-8 px-4',
        'medium' => 'py-12 sm:py-16 px-4 sm:px-6 lg:px-8',
        'large' => 'py-16 sm:py-20 px-4 sm:px-6 lg:px-8',
        'xl' => 'py-20 sm:py-28 px-4 sm:px-6 lg:px-8',
        default => 'py-12 sm:py-16 px-4 sm:px-6 lg:px-8',
    };

    $alignmentClass = $alignmentClasses[$text_alignment ?? 'center'] ?? 'text-center items-center';

    // Background - support both semantic and custom colors
    $bgClass = match($background_style ?? 'color') {
        'gradient' => '', // Gradient needs inline style
        default => 'bg-base-200',
    };

    $bgStyle = ($background_style ?? 'color') === 'gradient'
        ? "background: linear-gradient(135deg, " . ($gradient_from ?? 'oklch(var(--p))') . " 0%, " . ($gradient_to ?? 'oklch(var(--s))') . " 100%);"
        : '';

    // Text colors based on background
    $textClass = ($background_style ?? 'color') === 'gradient'
        ? 'text-primary-content'
        : 'text-base-content';

    $buttonAlignClass = match($text_alignment ?? 'center') {
        'left' => 'justify-start',
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-center',
    };
@endphp

<section class="{{ $paddingClass }} {{ $bgClass }}" @if($bgStyle) style="{{ $bgStyle }}" @endif>
    <div class="mx-auto max-w-4xl flex flex-col {{ $alignmentClass }}">
        @if($title ?? null)
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight {{ $textClass }} mb-6">
                {{ $title }}
            </h2>
        @endif

        @if($description ?? null)
            <p class="text-lg leading-relaxed {{ $textClass }}/80 {{ ($text_alignment ?? 'center') === 'center' ? 'max-w-2xl' : '' }} mb-10">
                {{ $description }}
            </p>
        @endif

        @if(($button_text ?? null) && ($button_url ?? null) && $button_url !== '#' || $hasSecondaryButton)
            <div class="flex flex-wrap gap-4 {{ $buttonAlignClass }}">
                @if(($button_text ?? null) && ($button_url ?? null) && $button_url !== '#')
                    <a href="{{ e($button_url) }}" class="{{ $buttonClass }} btn-lg gap-2">
                        {{ $button_text }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                @endif
                @if($hasSecondaryButton)
                    <a href="{{ e($secondary_button_url) }}" class="{{ $secondaryButtonClass }} btn-lg">
                        {{ $secondary_button_text }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
