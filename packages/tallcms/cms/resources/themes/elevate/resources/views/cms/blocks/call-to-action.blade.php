@php
    use TallCms\Cms\Services\BlockLinkResolver;

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-500';

    // Alignment helper classes
    $alignmentClasses = match($text_alignment ?? 'text-center') {
        'text-left' => 'text-left items-start',
        'text-right' => 'text-right items-end',
        default => 'text-center items-center',
    };

    $buttonAlignClass = match($text_alignment ?? 'text-center') {
        'text-left' => 'justify-start',
        'text-right' => 'justify-end',
        default => 'justify-center',
    };

    // Detect dark background for text color adaptation
    // Light backgrounds: base-100, base-200, base-300, info, success, warning
    // Everything else (primary, secondary, accent, neutral, error, gradients, custom) treated as dark
    $bgClass = $background ?? 'bg-gradient-to-br from-primary to-secondary';
    $lightBgs = ['bg-base-100', 'bg-base-200', 'bg-base-300', 'bg-info', 'bg-success', 'bg-warning'];
    $isDarkBg = !in_array($bgClass, $lightBgs);
    $textClass = $isDarkBg ? 'text-primary-content' : 'text-base-content';
    $textMutedClass = $isDarkBg ? 'text-primary-content/80' : 'text-base-content/80';
    $microcopyClass = $isDarkBg ? 'text-primary-content/60' : 'text-base-content/60';

    // Button defaults adapt to background
    $defaultBtnClass = $isDarkBg ? 'btn btn-neutral rounded-full' : 'btn btn-primary rounded-full';
    $defaultSecBtnClass = $isDarkBg ? 'btn btn-ghost text-primary-content rounded-full' : 'btn btn-ghost rounded-full';
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :duration="$animationDuration"
    :id="$anchor_id ?? null"
    class="relative overflow-hidden {{ $padding ?? 'py-16' }} {{ $bgClass }} {{ $css_classes ?? '' }}"
>
    {{-- Decorative glow (only on dark backgrounds) --}}
    @if($isDarkBg)
        <div class="glow-brand w-[500px] h-[500px] -top-20 left-1/2 -translate-x-1/2 absolute rounded-full pointer-events-none" style="opacity: 0.15"></div>
    @endif

    <div class="{{ $contentWidthClass ?? 'max-w-6xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }} relative z-10">
        {{-- Boxed inner container on dark backgrounds, plain on light --}}
        <div class="{{ $isDarkBg ? 'max-w-4xl mx-auto rounded-2xl bg-white/10 backdrop-blur-sm p-10 sm:p-14' : 'max-w-4xl mx-auto' }}">
            <div class="flex flex-col {{ $alignmentClasses }}">
                @if($title ?? null)
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight {{ $textClass }} mb-6">
                        {{ $title }}
                    </h2>
                @endif

                @if($description ?? null)
                    <p class="text-lg leading-relaxed {{ $textMutedClass }} {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'max-w-2xl' : '' }} mb-10">
                        {{ $description }}
                    </p>
                @endif

                @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()) || BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                    <div class="flex flex-wrap gap-6 {{ $buttonAlignClass }}">
                        @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()))
                            <div class="flex flex-col items-center gap-2">
                                <a href="{{ e($button_url) }}" class="{{ $button_classes ?? $defaultBtnClass }}">
                                    {{ $button_text }}
                                </a>
                                @if($button_microcopy ?? null)
                                    <span class="text-sm {{ $microcopyClass }}">{{ $button_microcopy }}</span>
                                @endif
                            </div>
                        @endif
                        @if(BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                            <div class="flex flex-col items-center gap-2">
                                <a href="{{ e($secondary_button_url) }}" class="{{ $secondary_button_classes ?? $defaultSecBtnClass }}">
                                    {{ $secondary_button_text }}
                                </a>
                                @if($secondary_button_microcopy ?? null)
                                    <span class="text-sm {{ $microcopyClass }}">{{ $secondary_button_microcopy }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-tallcms::animation-wrapper>
