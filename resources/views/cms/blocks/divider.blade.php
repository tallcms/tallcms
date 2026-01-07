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
    $lineColor = $color ?? null;
    $iconName = $icon ?? 'heroicon-o-star';
    $isValidIcon = !empty($iconName) && preg_match('/^heroicon-[oms]-[\w-]+$/', $iconName);
@endphp

<div class="divider-block {{ $heightClass }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($dividerStyle === 'space')
            {{-- Space only - no visible element --}}
        @elseif($dividerStyle === 'line')
            {{-- Simple horizontal line --}}
            <hr
                class="divider-line {{ $widthClass }} {{ $lineStyleClass }} border-t-2 mx-auto"
                @if($lineColor)
                    style="border-color: {{ $lineColor }};"
                @else
                    style="border-color: var(--block-border-color, #e5e7eb);"
                @endif
            >
        @elseif($dividerStyle === 'line-icon')
            {{-- Line with centered icon --}}
            <div class="flex items-center justify-center {{ $widthClass }} mx-auto">
                <hr
                    class="divider-line flex-1 {{ $lineStyleClass }} border-t-2"
                    @if($lineColor)
                        style="border-color: {{ $lineColor }};"
                    @else
                        style="border-color: var(--block-border-color, #e5e7eb);"
                    @endif
                >
                <div class="divider-icon mx-4">
                    @if($isValidIcon)
                        <x-dynamic-component
                            :component="$iconName"
                            class="w-6 h-6"
                            @if($lineColor)
                                style="color: {{ $lineColor }};"
                            @else
                                style="color: var(--block-text-color, #6b7280);"
                            @endif
                        />
                    @else
                        <x-heroicon-o-star
                            class="w-6 h-6"
                            @if($lineColor)
                                style="color: {{ $lineColor }};"
                            @else
                                style="color: var(--block-text-color, #6b7280);"
                            @endif
                        />
                    @endif
                </div>
                <hr
                    class="divider-line flex-1 {{ $lineStyleClass }} border-t-2"
                    @if($lineColor)
                        style="border-color: {{ $lineColor }};"
                    @else
                        style="border-color: var(--block-border-color, #e5e7eb);"
                    @endif
                >
            </div>
        @endif
    </div>
</div>
