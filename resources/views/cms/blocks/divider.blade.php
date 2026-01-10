@php
    $heightClass = match($height ?? 'medium') {
        'small' => 'py-4',
        'medium' => 'py-8',
        'large' => 'py-12',
        'xl' => 'py-16 sm:py-24',
        default => 'py-8',
    };

    $widthClass = match($width ?? 'medium') {
        'full' => 'w-full',
        'wide' => 'w-3/4',
        'medium' => 'w-1/2',
        'narrow' => 'w-1/4',
        default => 'w-1/2',
    };

    $lineStyleClass = match($line_style ?? 'solid') {
        'solid' => 'border-solid',
        'dashed' => 'border-dashed',
        'dotted' => 'border-dotted',
        default => 'border-solid',
    };

    $dividerStyle = $style ?? 'line';
    $iconName = $icon ?? 'heroicon-o-star';
    $isValidIcon = !empty($iconName) && preg_match('/^heroicon-[oms]-[\w-]+$/', $iconName);

    // Color class - daisyUI semantic or custom
    $colorClass = match($color ?? 'base') {
        'primary' => 'border-primary text-primary',
        'secondary' => 'border-secondary text-secondary',
        'accent' => 'border-accent text-accent',
        'neutral' => 'border-neutral text-neutral',
        default => 'border-base-300 text-base-content/50',
    };
@endphp

<div class="divider-block {{ $heightClass }} bg-base-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($dividerStyle === 'space')
            {{-- Space only - no visible element --}}
        @elseif($dividerStyle === 'line')
            {{-- Simple horizontal line using daisyUI divider --}}
            <div class="divider {{ $widthClass }} mx-auto {{ $colorClass }}"></div>
        @elseif($dividerStyle === 'line-icon')
            {{-- Line with centered icon --}}
            <div class="flex items-center justify-center {{ $widthClass }} mx-auto">
                <hr class="flex-1 {{ $lineStyleClass }} border-t-2 {{ $colorClass }}">
                <div class="mx-4">
                    @if($isValidIcon)
                        <x-dynamic-component
                            :component="$iconName"
                            class="w-6 h-6 {{ $colorClass }}"
                        />
                    @else
                        <x-heroicon-o-star class="w-6 h-6 {{ $colorClass }}" />
                    @endif
                </div>
                <hr class="flex-1 {{ $lineStyleClass }} border-t-2 {{ $colorClass }}">
            </div>
        @endif
    </div>
</div>
