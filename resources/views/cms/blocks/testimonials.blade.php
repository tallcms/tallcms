@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
    ])->join('; ') . ';';

    $textAlignClass = match($text_alignment ?? 'center') {
        'left' => 'text-left',
        'center' => 'text-center',
        default => 'text-center',
    };

    $columnsClass = match($columns ?? '3') {
        '1' => 'max-w-2xl mx-auto',
        '2' => 'sm:grid-cols-2 max-w-4xl mx-auto',
        '3' => 'sm:grid-cols-2 lg:grid-cols-3',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $styleClasses = match($style ?? 'cards') {
        'cards' => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 sm:p-8',
        'bordered' => 'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8',
        'minimal' => 'bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 sm:p-8',
        'quote-marks' => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 sm:p-8 relative',
        default => 'bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 sm:p-8',
    };

    $isQuoteMarks = ($style ?? 'cards') === 'quote-marks';
    $isSingleLayout = ($layout ?? 'grid') === 'single';
    $sectionSpacing = ($first_section ?? false) ? 'pt-0' : 'pt-16 sm:pt-24';
@endphp

<section
    class="testimonials-block {{ $sectionSpacing }} pb-16 sm:pb-24"
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

        {{-- Testimonials --}}
        @if(!empty($testimonials))
            @if($isSingleLayout)
                {{-- Single Large Layout --}}
                <div class="max-w-4xl mx-auto">
                    @foreach($testimonials as $testimonial)
                        <div class="{{ $styleClasses }} {{ !$loop->last ? 'mb-8' : '' }}">
                            @if($isQuoteMarks)
                                <div class="absolute top-4 left-6 text-6xl text-primary-200 dark:text-primary-800 font-serif leading-none">"</div>
                            @endif

                            {{-- Rating --}}
                            @if(($show_rating ?? true) && !empty($testimonial['rating']))
                                <div class="testimonial-rating flex items-center gap-1 mb-4 {{ $isQuoteMarks ? 'relative z-10' : '' }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= (int)$testimonial['rating'])
                                            <x-heroicon-s-star class="w-5 h-5 text-amber-400" />
                                        @else
                                            <x-heroicon-o-star class="w-5 h-5 text-gray-300 dark:text-gray-600" />
                                        @endif
                                    @endfor
                                </div>
                            @endif

                            {{-- Quote --}}
                            <blockquote class="testimonial-quote text-xl sm:text-2xl leading-relaxed mb-6 {{ $isQuoteMarks ? 'relative z-10 pl-8' : '' }}" style="color: var(--block-text-color);">
                                {{ $testimonial['quote'] }}
                            </blockquote>

                            {{-- Author --}}
                            <div class="flex items-center gap-4">
                                @if(!empty($testimonial['author_image']))
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($testimonial['author_image']) }}"
                                        alt="{{ $testimonial['author_name'] ?? '' }}"
                                        class="w-14 h-14 rounded-full object-cover"
                                    >
                                @else
                                    <div class="w-14 h-14 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                        <span class="text-xl font-semibold text-primary-600 dark:text-primary-400">
                                            {{ substr($testimonial['author_name'] ?? 'A', 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                                <div>
                                    <div class="testimonial-author font-semibold" style="color: var(--block-heading-color);">
                                        {{ $testimonial['author_name'] }}
                                    </div>
                                    @if(!empty($testimonial['author_title']))
                                        <div class="testimonial-role text-sm" style="color: var(--block-text-color);">
                                            {{ $testimonial['author_title'] }}
                                        </div>
                                    @endif
                                </div>
                                @if(($show_company_logo ?? false) && !empty($testimonial['company_logo']))
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($testimonial['company_logo']) }}"
                                        alt="Company logo"
                                        class="h-8 ml-auto object-contain opacity-60"
                                    >
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Grid Layout --}}
                <div class="grid gap-6 sm:gap-8 {{ $columnsClass }}">
                    @foreach($testimonials as $testimonial)
                        <div class="testimonial-card {{ $styleClasses }}">
                            @if($isQuoteMarks)
                                <div class="absolute top-4 left-6 text-5xl text-primary-200 dark:text-primary-800 font-serif leading-none">"</div>
                            @endif

                            {{-- Rating --}}
                            @if(($show_rating ?? true) && !empty($testimonial['rating']))
                                <div class="testimonial-rating flex items-center gap-0.5 mb-3 {{ $isQuoteMarks ? 'relative z-10' : '' }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= (int)$testimonial['rating'])
                                            <x-heroicon-s-star class="w-4 h-4 text-amber-400" />
                                        @else
                                            <x-heroicon-o-star class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                                        @endif
                                    @endfor
                                </div>
                            @endif

                            {{-- Quote --}}
                            <blockquote class="testimonial-quote text-base leading-relaxed mb-6 {{ $isQuoteMarks ? 'relative z-10 pl-6' : '' }}" style="color: var(--block-text-color);">
                                {{ $testimonial['quote'] }}
                            </blockquote>

                            {{-- Author --}}
                            <div class="flex items-center gap-3 mt-auto">
                                @if(!empty($testimonial['author_image']))
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($testimonial['author_image']) }}"
                                        alt="{{ $testimonial['author_name'] ?? '' }}"
                                        class="w-10 h-10 rounded-full object-cover"
                                    >
                                @else
                                    <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                        <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                            {{ substr($testimonial['author_name'] ?? 'A', 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="testimonial-author font-semibold text-sm truncate" style="color: var(--block-heading-color);">
                                        {{ $testimonial['author_name'] }}
                                    </div>
                                    @if(!empty($testimonial['author_title']))
                                        <div class="testimonial-role text-xs truncate" style="color: var(--block-text-color);">
                                            {{ $testimonial['author_title'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Company Logo --}}
                            @if(($show_company_logo ?? false) && !empty($testimonial['company_logo']))
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <img
                                        src="{{ Storage::disk(cms_media_disk())->url($testimonial['company_logo']) }}"
                                        alt="Company logo"
                                        class="h-6 object-contain opacity-50"
                                    >
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>
