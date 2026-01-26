@php
    $columnsClass = match($columns ?? '3') {
        '1' => 'max-w-2xl mx-auto',
        '2' => 'sm:grid-cols-2 max-w-4xl mx-auto',
        '3' => 'sm:grid-cols-2 lg:grid-cols-3',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $styleClasses = $card_style ?? 'card bg-base-200 shadow-lg';
    $isQuoteMarks = str_contains($styleClasses, 'quote-marks');
    $styleClasses = str_replace('quote-marks', 'relative', $styleClasses);
    $isSingleLayout = ($layout ?? 'grid') === 'single';
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
@endphp

<section @if($anchor_id ?? null) id="{{ $anchor_id }}" @endif class="testimonials-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}">
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="{{ $text_alignment ?? 'text-center' }} mb-12 sm:mb-16">
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
            </div>
        @endif

        {{-- Testimonials --}}
        @if(!empty($testimonials))
            @if($isSingleLayout)
                {{-- Single Large Layout --}}
                <div class="max-w-4xl mx-auto">
                    @foreach($testimonials as $testimonial)
                        <div class="{{ $styleClasses }} {{ !$loop->last ? 'mb-8' : '' }}">
                            <div class="card-body">
                                @if($isQuoteMarks)
                                    <div class="absolute top-4 left-6 text-6xl text-primary/20 font-serif leading-none">"</div>
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
                                <blockquote class="text-xl sm:text-2xl leading-relaxed mb-6 text-base-content/80 {{ $isQuoteMarks ? 'relative z-10 pl-8' : '' }}">
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
                                        <div class="font-semibold text-base-content">
                                            {{ $testimonial['author_name'] }}
                                        </div>
                                        @if(!empty($testimonial['author_title']))
                                            <div class="text-sm text-base-content/70">
                                                {{ $testimonial['author_title'] }}
                                            </div>
                                        @endif
                                    </div>
                                    @if(($show_company_logo ?? false) && !empty($testimonial['company_logo']))
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($testimonial['company_logo']) }}"
                                            alt="Company logo"
                                            class="h-8 ml-auto object-contain opacity-60"
                                            loading="lazy"
                                        >
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Grid Layout --}}
                <div class="grid gap-6 sm:gap-8 {{ $columnsClass }}">
                    @foreach($testimonials as $testimonial)
                        <div class="{{ $styleClasses }}">
                            <div class="card-body">
                                @if($isQuoteMarks)
                                    <div class="absolute top-4 left-6 text-5xl text-primary/20 font-serif leading-none">"</div>
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
                                <blockquote class="text-base leading-relaxed mb-6 text-base-content/80 {{ $isQuoteMarks ? 'relative z-10 pl-6' : '' }}">
                                    {{ $testimonial['quote'] }}
                                </blockquote>

                                {{-- Author --}}
                                <div class="flex items-center gap-3 mt-auto">
                                    @if(!empty($testimonial['author_image']))
                                        <div class="avatar">
                                            <div class="w-10 rounded-full">
                                                <img
                                                    src="{{ Storage::disk(cms_media_disk())->url($testimonial['author_image']) }}"
                                                    alt="{{ $testimonial['author_name'] ?? '' }}"
                                                    loading="lazy"
                                                >
                                            </div>
                                        </div>
                                    @else
                                        <div class="avatar placeholder">
                                            <div class="w-10 rounded-full bg-primary/10 text-primary">
                                                <span class="text-sm font-semibold">
                                                    {{ substr($testimonial['author_name'] ?? 'A', 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-sm truncate text-base-content">
                                            {{ $testimonial['author_name'] }}
                                        </div>
                                        @if(!empty($testimonial['author_title']))
                                            <div class="text-xs truncate text-base-content/70">
                                                {{ $testimonial['author_title'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Company Logo --}}
                                @if(($show_company_logo ?? false) && !empty($testimonial['company_logo']))
                                    <div class="mt-4 pt-4 border-t border-base-300">
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($testimonial['company_logo']) }}"
                                            alt="Company logo"
                                            class="h-6 object-contain opacity-50"
                                            loading="lazy"
                                        >
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>
