@php
    $columnsClass = match($columns ?? '3') {
        '1' => 'max-w-2xl mx-auto',
        '2' => 'sm:grid-cols-2 max-w-4xl mx-auto',
        '3' => 'sm:grid-cols-2 lg:grid-cols-3',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $isQuoteMarks = str_contains($card_style ?? '', 'quote-marks');
    // Strip quote-marks marker from class list (it's a behavior flag, not a CSS class)
    $styleClasses = str_replace('quote-marks', '', $card_style ?? 'card bg-base-200/50');
    $styleClasses = trim(preg_replace('/\s+/', ' ', $styleClasses));
    $isSingleLayout = ($layout ?? 'grid') === 'single';
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    // Determine if we have exactly 3 testimonials in grid mode for featured-first layout
    $isThreeColFeatured = !$isSingleLayout && ($columns ?? '3') === '3' && count($testimonials ?? []) === 3;

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
    class="testimonials-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
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

        {{-- Testimonials --}}
        @if(!empty($testimonials))
            @if($isSingleLayout)
                {{-- Single Large Layout --}}
                <div class="max-w-4xl mx-auto">
                    @foreach($testimonials as $index => $testimonial)
                        @php
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                            class="{{ $styleClasses }} rounded-2xl p-6 sm:p-8 {{ $isQuoteMarks ? 'relative' : '' }} {{ !$loop->last ? 'mb-8' : '' }}"
                        >
                            {{-- Company Logo --}}
                            @if(($show_company_logo ?? false) && !empty($testimonial['company_logo']))
                                <div class="mb-4">
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($testimonial['company_logo']) }}"
                                        alt="Company logo"
                                        class="h-8 object-contain opacity-40 hover:opacity-100 transition-opacity"
                                        loading="lazy"
                                    >
                                </div>
                            @endif

                            @if($isQuoteMarks)
                                <div class="absolute top-4 left-6 text-5xl text-gradient-primary font-serif leading-none">"</div>
                            @endif

                            {{-- Rating --}}
                            @if(($show_rating ?? true) && !empty($testimonial['rating']))
                                <div class="rating rating-sm mb-4 {{ $isQuoteMarks ? 'relative z-10' : '' }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input
                                            type="radio"
                                            class="mask mask-star-2 bg-warning"
                                            disabled
                                            {{ $i === (int)$testimonial['rating'] ? 'checked' : '' }}
                                        />
                                    @endfor
                                </div>
                            @endif

                            {{-- Quote --}}
                            <blockquote class="text-xl leading-relaxed mb-6 text-base-content/80 {{ $isQuoteMarks ? 'relative z-10 pl-8' : '' }}">
                                {{ $testimonial['quote'] }}
                            </blockquote>

                            {{-- Author --}}
                            <div class="flex items-center gap-4">
                                @if(!empty($testimonial['author_image']))
                                    <div class="avatar">
                                        <div class="w-14 rounded-full">
                                            <img
                                                src="{{ Storage::disk(cms_media_disk())->url($testimonial['author_image']) }}"
                                                alt="{{ $testimonial['author_name'] ?? '' }}"
                                                loading="lazy"
                                            >
                                        </div>
                                    </div>
                                @else
                                    <div class="avatar placeholder">
                                        <div class="w-14 rounded-full bg-primary/10 text-primary">
                                            <span class="text-xl font-semibold">
                                                {{ substr($testimonial['author_name'] ?? 'A', 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold text-base text-base-content">
                                        {{ $testimonial['author_name'] }}
                                    </div>
                                    @if(!empty($testimonial['author_title']))
                                        <div class="text-sm text-base-content/50">
                                            {{ $testimonial['author_title'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>
            @else
                {{-- Grid Layout --}}
                <div class="grid gap-8 {{ $columnsClass }}">
                    @foreach($testimonials as $index => $testimonial)
                        @php
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                            $isFeaturedFirst = $isThreeColFeatured && $index === 0;
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                            class="{{ $styleClasses }} rounded-2xl p-6 sm:p-8 {{ $isQuoteMarks ? 'relative' : '' }} {{ $isFeaturedFirst ? 'md:col-span-2' : '' }}"
                        >
                            {{-- Company Logo --}}
                            @if(($show_company_logo ?? false) && !empty($testimonial['company_logo']))
                                <div class="mb-3">
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($testimonial['company_logo']) }}"
                                        alt="Company logo"
                                        class="h-8 object-contain opacity-40 hover:opacity-100 transition-opacity"
                                        loading="lazy"
                                    >
                                </div>
                            @endif

                            @if($isQuoteMarks)
                                <div class="absolute top-4 left-6 text-5xl text-gradient-primary font-serif leading-none">"</div>
                            @endif

                            {{-- Rating --}}
                            @if(($show_rating ?? true) && !empty($testimonial['rating']))
                                <div class="rating rating-sm mb-3 {{ $isQuoteMarks ? 'relative z-10' : '' }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <input
                                            type="radio"
                                            class="mask mask-star-2 bg-warning"
                                            disabled
                                            {{ $i === (int)$testimonial['rating'] ? 'checked' : '' }}
                                        />
                                    @endfor
                                </div>
                            @endif

                            {{-- Quote --}}
                            <blockquote class="{{ $isFeaturedFirst ? 'text-xl' : 'text-lg' }} leading-relaxed mb-6 text-base-content/80 {{ $isQuoteMarks ? 'relative z-10 pl-6' : '' }}">
                                {{ $testimonial['quote'] }}
                            </blockquote>

                            {{-- Author --}}
                            <div class="flex items-center gap-3 mt-auto">
                                @if(!empty($testimonial['author_image']))
                                    <div class="avatar">
                                        <div class="w-12 h-12 rounded-full">
                                            <img
                                                src="{{ Storage::disk(cms_media_disk())->url($testimonial['author_image']) }}"
                                                alt="{{ $testimonial['author_name'] ?? '' }}"
                                                loading="lazy"
                                            >
                                        </div>
                                    </div>
                                @else
                                    <div class="avatar placeholder">
                                        <div class="w-12 h-12 rounded-full bg-primary/10 text-primary">
                                            <span class="text-sm font-semibold">
                                                {{ substr($testimonial['author_name'] ?? 'A', 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-base truncate text-base-content">
                                        {{ $testimonial['author_name'] }}
                                    </div>
                                    @if(!empty($testimonial['author_title']))
                                        <div class="text-sm truncate text-base-content/50">
                                            {{ $testimonial['author_title'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-tallcms::animation-wrapper>
