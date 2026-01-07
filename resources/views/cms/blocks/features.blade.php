@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
    ])->join('; ') . ';';

    $columnsClass = match($columns ?? '3') {
        '2' => 'sm:grid-cols-2',
        '3' => 'sm:grid-cols-2 lg:grid-cols-3',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $styleClass = match($style ?? 'cards') {
        'cards' => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg',
        'bordered' => 'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700',
        'minimal' => 'bg-transparent',
        default => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg',
    };

    $paddingClass = match($padding ?? 'medium') {
        'small' => 'p-4',
        'medium' => 'p-6',
        'large' => 'p-8',
        default => 'p-6',
    };

    $iconSizeClass = match($icon_size ?? 'medium') {
        'small' => 'w-8 h-8',
        'medium' => 'w-12 h-12',
        'large' => 'w-16 h-16',
        default => 'w-12 h-12',
    };

    $iconContainerClass = match($icon_size ?? 'medium') {
        'small' => 'w-12 h-12',
        'medium' => 'w-16 h-16',
        'large' => 'w-20 h-20',
        default => 'w-16 h-16',
    };

    $textAlignClass = match($text_alignment ?? 'center') {
        'left' => 'text-left',
        'center' => 'text-center',
        default => 'text-center',
    };

    $isIconLeft = ($icon_position ?? 'top') === 'left';
    $sectionSpacing = ($first_section ?? false) ? 'pt-0' : 'pt-16 sm:pt-24';
@endphp

<section
    class="features-block {{ $sectionSpacing }} pb-16 sm:pb-24"
    style="{{ $customProperties }}"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="{{ $textAlignClass }} mb-12 sm:mb-16">
                @if(!empty($heading))
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-4 text-lg sm:text-xl max-w-3xl {{ $textAlignClass === 'text-center' ? 'mx-auto' : '' }}" style="color: var(--block-text-color);">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Features Grid --}}
        @if(!empty($features))
            <div class="grid gap-6 sm:gap-8 {{ $columnsClass }}">
                @foreach($features as $feature)
                    @php
                        $hasLink = !empty($feature['link']);
                        $tag = $hasLink ? 'a' : 'div';
                        $linkAttrs = $hasLink ? 'href="' . e($feature['link']) . '"' : '';
                    @endphp

                    <{{ $tag }}
                        {{ $hasLink ? 'href=' . e($feature['link']) : '' }}
                        class="feature-card {{ $styleClass }} {{ $paddingClass }} {{ $textAlignClass }} transition-all duration-200 {{ $hasLink ? 'hover:shadow-xl hover:-translate-y-1 cursor-pointer' : '' }} {{ $isIconLeft ? 'flex items-start gap-4 !text-left' : '' }}"
                    >
                        {{-- Icon --}}
                        @php
                            $iconType = $feature['icon_type'] ?? 'heroicon';
                        @endphp

                        <div class="{{ $isIconLeft ? 'flex-shrink-0' : 'flex justify-center mb-4' }}">
                            <div class="{{ $iconContainerClass }} rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                @if($iconType === 'heroicon' && !empty($feature['icon']))
                                    @php
                                        $iconName = $feature['icon'];
                                        // Convert heroicon-o-name to heroicon-o name format for Blade component
                                        $iconComponent = str_replace('heroicon-', '', $iconName);
                                    @endphp
                                    <x-dynamic-component
                                        :component="$iconName"
                                        class="{{ $iconSizeClass }} text-primary-600 dark:text-primary-400"
                                    />
                                @elseif($iconType === 'image' && !empty($feature['icon_image']))
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($feature['icon_image']) }}"
                                        alt="{{ $feature['title'] ?? '' }}"
                                        class="{{ $iconSizeClass }} object-contain"
                                    >
                                @elseif($iconType === 'emoji' && !empty($feature['emoji']))
                                    <span class="text-3xl">{{ $feature['emoji'] }}</span>
                                @else
                                    {{-- Default icon if none specified --}}
                                    <x-heroicon-o-star class="{{ $iconSizeClass }} text-primary-600 dark:text-primary-400" />
                                @endif
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="{{ $isIconLeft ? 'flex-1' : '' }}">
                            @if(!empty($feature['title']))
                                <h3 class="text-lg font-semibold mb-2" style="color: var(--block-heading-color);">
                                    {{ $feature['title'] }}
                                </h3>
                            @endif

                            @if(!empty($feature['description']))
                                <p class="text-sm leading-relaxed" style="color: var(--block-text-color);">
                                    {{ $feature['description'] }}
                                </p>
                            @endif

                            @if($hasLink)
                                <span class="inline-flex items-center mt-3 text-sm font-medium text-primary-600 dark:text-primary-400 group-hover:text-primary-700">
                                    Learn more
                                    <x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                </span>
                            @endif
                        </div>
                    </{{ $tag }}>
                @endforeach
            </div>
        @endif
    </div>
</section>
