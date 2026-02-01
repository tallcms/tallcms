@php
    $heightClass = match($height ?? 'medium') {
        'small' => 'py-4',
        'medium' => 'py-8',
        'large' => 'py-12',
        'xl' => 'py-16 sm:py-24',
        default => 'py-8',
    };

    $dividerStyle = $style ?? 'line';
    $iconName = $icon ?? null;
    $isValidIcon = !empty($iconName) && preg_match('/^heroicon-[oms]-[\w-]+$/', $iconName);

    // DaisyUI divider color classes
    $colorClass = match($color ?? 'default') {
        'primary' => 'divider-primary',
        'secondary' => 'divider-secondary',
        'accent' => 'divider-accent',
        'neutral' => 'divider-neutral',
        'success' => 'divider-success',
        'warning' => 'divider-warning',
        'info' => 'divider-info',
        'error' => 'divider-error',
        default => '',
    };

    // Text/icon position
    $positionClass = match($position ?? 'center') {
        'start' => 'divider-start',
        'end' => 'divider-end',
        default => '',
    };

    // Build anchor ID attribute (avoid @if inside tag to prevent Blade comment injection)
    $anchorIdAttr = !empty($anchor_id) ? 'id="' . e($anchor_id) . '"' : '';
@endphp

<div {!! $anchorIdAttr !!} class="divider-block {{ $heightClass }} {{ $css_classes ?? '' }}">
    <div class="flex w-full flex-col {{ $contentWidthClass ?? 'max-w-6xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        @if($dividerStyle === 'space')
            {{-- Space only - no visible element --}}
        @elseif($dividerStyle === 'line')
            {{-- Simple horizontal line --}}
            <div class="divider {{ $colorClass }}"></div>
        @elseif($dividerStyle === 'line-text')
            {{-- Line with centered text --}}
            <div class="divider {{ $colorClass }} {{ $positionClass }}">{{ $text ?? 'OR' }}</div>
        @elseif($dividerStyle === 'line-icon')
            {{-- Line with centered icon --}}
            <div class="divider {{ $colorClass }} {{ $positionClass }}">
                @if($isValidIcon)
                    <x-dynamic-component :component="$iconName" class="w-5 h-5" />
                @endif
            </div>
        @endif
    </div>
</div>
