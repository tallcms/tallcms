@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
    ])->join('; ') . ';';

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

    $sectionSpacing = ($first_section ?? false) ? 'pt-0' : 'pt-16 sm:pt-24';
@endphp

<section
    class="logos-block {{ $sectionSpacing }} pb-16 sm:pb-24"
    style="{{ $customProperties }}"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading))
            <div class="text-center mb-10">
                <p class="text-sm sm:text-base font-medium uppercase tracking-wider" style="color: var(--block-text-color);">
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
                            <div class="logo-placeholder flex items-center justify-center {{ $sizeClasses }} bg-gray-100 dark:bg-gray-800 rounded">
                                <span class="text-xs text-gray-400">{{ $logo['alt'] ?? 'Logo' }}</span>
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
                            <div class="logo-placeholder flex items-center justify-center {{ $sizeClasses }} bg-gray-100 dark:bg-gray-800 rounded px-4">
                                <span class="text-xs text-gray-400">{{ $logo['alt'] ?? 'Logo' }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>
