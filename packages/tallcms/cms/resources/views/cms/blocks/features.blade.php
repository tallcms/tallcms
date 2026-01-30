@php
    $columnsClass = match($columns ?? '3') {
        '2' => 'sm:grid-cols-2',
        '3' => 'sm:grid-cols-2 lg:grid-cols-3',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $iconContainerClass = match($icon_size ?? 'w-10 h-10') {
        'w-8 h-8' => 'w-12 h-12',
        'w-10 h-10' => 'w-16 h-16',
        'w-12 h-12' => 'w-20 h-20',
        default => 'w-16 h-16',
    };

    $isIconLeft = ($icon_position ?? 'top') === 'left';
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-500';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="features-block {{ $sectionPadding }} bg-base-100 {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="{{ $text_alignment ?? 'text-center' }} mb-12 sm:mb-16"
            >
                @if(!empty($heading))
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-base-content">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-4 text-lg sm:text-xl text-base-content/70 max-w-3xl {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'mx-auto' : '' }}">
                        {{ $subheading }}
                    </p>
                @endif
            </x-tallcms::animation-wrapper>
        @endif

        {{-- Features Grid --}}
        @if(!empty($features))
            <div class="grid gap-6 sm:gap-8 {{ $columnsClass }}">
                @foreach($features as $index => $feature)
                    @php
                        $hasLink = !empty($feature['link']);
                        $iconType = $feature['icon_type'] ?? 'heroicon';
                        $iconName = $feature['icon'] ?? '';
                        $isValidHeroicon = $iconType === 'heroicon'
                            && !empty($iconName)
                            && preg_match('/^heroicon-[oms]-[\w-]+$/', $iconName);
                        $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                    @endphp

                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                    >
                        @if($hasLink)
                            <a href="{{ e($feature['link']) }}"
                               class="{{ $card_style ?? 'card bg-base-100 shadow-md' }} {{ $text_alignment ?? 'text-center' }} transition-all duration-200 hover:shadow-xl hover:-translate-y-1 cursor-pointer {{ $isIconLeft ? 'card-side' : '' }} h-full">
                        @else
                            <div class="{{ $card_style ?? 'card bg-base-100 shadow-md' }} {{ $text_alignment ?? 'text-center' }} transition-all duration-200 {{ $isIconLeft ? 'card-side' : '' }} h-full">
                        @endif

                            <div class="card-body {{ $isIconLeft ? 'flex-row items-start gap-4' : '' }}">
                                {{-- Icon --}}
                                <div class="{{ $isIconLeft ? 'flex-shrink-0' : 'flex justify-center mb-4' }}">
                                    <div class="{{ $iconContainerClass }} rounded-xl bg-primary/10 flex items-center justify-center">
                                        @if($isValidHeroicon)
                                            <x-dynamic-component
                                                :component="$iconName"
                                                class="{{ $icon_size ?? 'w-10 h-10' }} text-primary"
                                            />
                                        @elseif($iconType === 'image' && !empty($feature['icon_image']))
                                            <img
                                                src="{{ Storage::disk(cms_media_disk())->url($feature['icon_image']) }}"
                                                alt="{{ $feature['title'] ?? '' }}"
                                                class="{{ $icon_size ?? 'w-10 h-10' }} object-contain"
                                                loading="lazy"
                                            >
                                        @elseif($iconType === 'emoji' && !empty($feature['emoji']))
                                            <span class="text-3xl">{{ $feature['emoji'] }}</span>
                                        @else
                                            <x-heroicon-o-star class="{{ $icon_size ?? 'w-10 h-10' }} text-primary" />
                                        @endif
                                    </div>
                                </div>

                                {{-- Content --}}
                                <div class="{{ $isIconLeft ? 'flex-1' : '' }}">
                                    @if(!empty($feature['title']))
                                        <h3 class="card-title text-lg font-semibold text-base-content {{ ($text_alignment ?? 'text-center') === 'text-center' && !$isIconLeft ? 'justify-center' : '' }}">
                                            {{ $feature['title'] }}
                                        </h3>
                                    @endif

                                    @if(!empty($feature['description']))
                                        <p class="text-sm leading-relaxed text-base-content/70 mt-2">
                                            {{ $feature['description'] }}
                                        </p>
                                    @endif

                                    @if($hasLink)
                                        <div class="card-actions {{ ($text_alignment ?? 'text-center') === 'text-center' && !$isIconLeft ? 'justify-center' : '' }} mt-4">
                                            <span class="link link-primary link-hover inline-flex items-center text-sm font-medium">
                                                Learn more
                                                <x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        @if($hasLink)
                            </a>
                        @else
                            </div>
                        @endif
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @endif
    </div>
</x-tallcms::animation-wrapper>
