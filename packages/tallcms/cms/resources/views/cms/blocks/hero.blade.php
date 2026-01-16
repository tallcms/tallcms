@php
    use TallCms\Cms\Services\BlockLinkResolver;

    $isPreview = $isPreview ?? false;
    $overlayOpacity = $overlay_opacity ?? 0.4;

    $buttonAlignClass = match($text_alignment ?? 'text-center') {
        'text-left' => 'justify-start',
        'text-right' => 'justify-end',
        default => 'justify-center',
    };

    // Convert data-color attributes to class attributes for daisyUI text colors
    $processedHeading = preg_replace(
        '/data-color="([^"]+)"/',
        'class="$1"',
        $heading ?? ''
    );
    $processedSubheading = preg_replace(
        '/data-color="([^"]+)"/',
        'class="$1"',
        $subheading ?? ''
    );
@endphp

<section class="hero {{ $height ?? 'min-h-[70vh]' }} {{ $isPreview ? '' : '-mt-20' }} relative overflow-hidden">
    {{-- Background Image or Gradient --}}
    @if(($background_image ?? null) && Storage::disk(cms_media_disk())->exists($background_image))
        <div class="absolute inset-0 z-0"
             style="background-image: url('{{ Storage::disk(cms_media_disk())->url($background_image) }}');
                    background-size: cover;
                    background-position: center;
                    @if($parallax_effect ?? true) background-attachment: fixed; @endif">
            <div class="absolute inset-0" style="background-color: rgba(0, 0, 0, {{ $overlayOpacity }});"></div>
        </div>
    @else
        <div class="absolute inset-0 z-0 bg-gradient-to-br from-primary to-secondary"></div>
    @endif

    {{-- Content --}}
    <div class="hero-content {{ $text_alignment ?? 'text-center' }} relative z-10 w-full px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:py-40">
        <div class="max-w-5xl {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'mx-auto' : '' }}">
            {{-- Main Heading --}}
            @if($heading ?? null)
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-tight mb-6 drop-shadow-lg">
                    {!! $processedHeading !!}
                </h1>
            @endif

            {{-- Subheading --}}
            @if($subheading ?? null)
                <div class="text-lg sm:text-xl lg:text-2xl text-white/85 leading-relaxed mb-10 {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'max-w-3xl mx-auto' : 'max-w-3xl' }}">
                    {!! $processedSubheading !!}
                </div>
            @endif

            {{-- Call to Action Buttons --}}
            @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()))
                <div class="flex flex-col sm:flex-row gap-4 {{ $buttonAlignClass }}">
                    {{-- Primary Button with Microcopy --}}
                    <div class="flex flex-col items-center gap-2">
                        <a href="{{ e($button_url) }}" class="{{ $button_classes ?? 'btn btn-primary btn-lg' }} gap-2">
                            {{ $button_text }}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        @if($button_microcopy ?? null)
                            <span class="text-sm text-white/70">{{ $button_microcopy }}</span>
                        @endif
                    </div>

                    {{-- Secondary Button with Microcopy --}}
                    @if(BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                        <div class="flex flex-col items-center gap-2">
                            <a href="{{ e($secondary_button_url) }}" class="{{ $secondary_button_classes ?? 'btn btn-ghost text-white btn-lg' }}">
                                {{ $secondary_button_text }}
                            </a>
                            @if($secondary_button_microcopy ?? null)
                                <span class="text-sm text-white/70">{{ $secondary_button_microcopy }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Scroll Indicator --}}
    @if(($height ?? 'min-h-[70vh]') === 'min-h-screen')
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    @endif
</section>
