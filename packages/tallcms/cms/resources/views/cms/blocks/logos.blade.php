@php
    $isGrid = ($layout ?? 'grid') === 'grid';

    $columnsClass = match($columns ?? '5') {
        '4' => 'grid-cols-2 sm:grid-cols-4',
        '5' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
        '6' => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
        default => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
    };

    $sizeClasses = match($size ?? 'medium') {
        'small' => 'h-8 max-w-[100px]',
        'medium' => 'h-10 max-w-[140px]',
        'large' => 'h-14 max-w-[180px]',
        default => 'h-10 max-w-[140px]',
    };

    $grayscaleClass = ($grayscale ?? true) ? 'grayscale opacity-60' : '';
    $hoverColorClass = (($grayscale ?? true) && ($hover_color ?? true)) ? 'hover:grayscale-0 hover:opacity-100' : '';

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :duration="$animationDuration"
    :id="$anchor_id ?? null"
    class="logos-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Section Header --}}
        @if(!empty($heading))
            <div class="text-center mb-10">
                <p class="text-sm sm:text-base font-medium uppercase tracking-wider text-base-content/70">
                    {{ $heading }}
                </p>
            </div>
        @endif

        {{-- Logos --}}
        @if(!empty($logos))
            @if($isGrid)
                {{-- Grid Layout --}}
                <div class="grid {{ $columnsClass }} gap-8 items-center justify-items-center">
                    @foreach($logos as $logo)
                        @if(!empty($logo['image']))
                            @if(!empty($logo['url']))
                                <a
                                    href="{{ $logo['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="logo-item flex items-center justify-center transition-all duration-300 {{ $grayscaleClass }} {{ $hoverColorClass }}"
                                >
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($logo['image']) }}"
                                        alt="{{ $logo['alt'] ?? '' }}"
                                        class="object-contain {{ $sizeClasses }}"
                                    >
                                </a>
                            @else
                                <div class="logo-item flex items-center justify-center transition-all duration-300 {{ $grayscaleClass }} {{ $hoverColorClass }}">
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($logo['image']) }}"
                                        alt="{{ $logo['alt'] ?? '' }}"
                                        class="object-contain {{ $sizeClasses }}"
                                    >
                                </div>
                            @endif
                        @else
                            {{-- Placeholder for preview --}}
                            <div class="logo-placeholder flex items-center justify-center {{ $sizeClasses }} bg-base-200 rounded">
                                <span class="text-xs text-base-content/50">{{ $logo['alt'] ?? 'Logo' }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                {{-- Inline Layout --}}
                <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-12">
                    @foreach($logos as $logo)
                        @if(!empty($logo['image']))
                            @if(!empty($logo['url']))
                                <a
                                    href="{{ $logo['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="logo-item flex items-center justify-center transition-all duration-300 {{ $grayscaleClass }} {{ $hoverColorClass }}"
                                >
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($logo['image']) }}"
                                        alt="{{ $logo['alt'] ?? '' }}"
                                        class="object-contain {{ $sizeClasses }}"
                                    >
                                </a>
                            @else
                                <div class="logo-item flex items-center justify-center transition-all duration-300 {{ $grayscaleClass }} {{ $hoverColorClass }}">
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($logo['image']) }}"
                                        alt="{{ $logo['alt'] ?? '' }}"
                                        class="object-contain {{ $sizeClasses }}"
                                    >
                                </div>
                            @endif
                        @else
                            {{-- Placeholder for preview --}}
                            <div class="logo-placeholder flex items-center justify-center {{ $sizeClasses }} bg-base-200 rounded px-4">
                                <span class="text-xs text-base-content/50">{{ $logo['alt'] ?? 'Logo' }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-tallcms::animation-wrapper>
