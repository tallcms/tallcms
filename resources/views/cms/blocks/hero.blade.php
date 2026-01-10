@php
    use App\Services\BlockLinkResolver;

    // Height mapping
    $heightClasses = [
        'small' => 'min-h-[50vh]',
        'medium' => 'min-h-[70vh]',
        'large' => 'min-h-[90vh]',
        'full' => 'min-h-screen'
    ];

    // Text alignment mapping
    $alignmentClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right'
    ];

    // Button style mapping to daisyUI classes
    $primaryButtonClass = match($primary_button_preset ?? 'white') {
        'white' => 'btn btn-neutral bg-white text-gray-900 hover:bg-gray-100 border-white',
        'primary' => 'btn btn-primary',
        'secondary' => 'btn btn-secondary',
        'accent' => 'btn btn-accent',
        'ghost' => 'btn btn-ghost text-white border-white',
        default => 'btn btn-neutral bg-white text-gray-900 hover:bg-gray-100',
    };

    $secondaryButtonClass = match($secondary_button_preset ?? 'outline-white') {
        'outline-white' => 'btn btn-outline text-white border-white hover:bg-white hover:text-gray-900',
        'outline-primary' => 'btn btn-outline btn-primary',
        'ghost' => 'btn btn-ghost text-white',
        default => 'btn btn-outline text-white border-white hover:bg-white hover:text-gray-900',
    };

    $heightClass = $heightClasses[$height ?? 'medium'] ?? 'min-h-[70vh]';
    $alignmentClass = $alignmentClasses[$text_alignment ?? 'center'] ?? 'text-center';
    $overlayOpacity = ($overlay_opacity ?? 40) / 100;
    $isPreview = $isPreview ?? false;

    $buttonAlignClass = match($text_alignment ?? 'center') {
        'left' => 'justify-start',
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-center',
    };
@endphp

<section class="hero {{ $heightClass }} {{ $isPreview ? '' : '-mt-20' }} relative overflow-hidden">
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
    <div class="hero-content {{ $alignmentClass }} relative z-10 w-full px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:py-40">
        <div class="max-w-5xl {{ $alignmentClass === 'text-center' ? 'mx-auto' : '' }}">
            {{-- Main Heading --}}
            @if($heading ?? null)
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-tight mb-6 drop-shadow-lg">
                    {{ $heading }}
                </h1>
            @endif

            {{-- Subheading --}}
            @if($subheading ?? null)
                <p class="text-lg sm:text-xl lg:text-2xl text-white/85 leading-relaxed mb-10 {{ $alignmentClass === 'text-center' ? 'max-w-3xl mx-auto' : 'max-w-3xl' }}">
                    {{ $subheading }}
                </p>
            @endif

            {{-- Call to Action Buttons --}}
            @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()))
                <div class="flex flex-col sm:flex-row gap-4 {{ $buttonAlignClass }}">
                    {{-- Primary Button --}}
                    <a href="{{ e($button_url) }}" class="{{ $primaryButtonClass }} btn-lg gap-2">
                        {{ $button_text }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>

                    {{-- Secondary Button --}}
                    @if(BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                        <a href="{{ e($secondary_button_url) }}" class="{{ $secondaryButtonClass }} btn-lg">
                            {{ $secondary_button_text }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Scroll Indicator --}}
    @if(($height ?? 'medium') === 'full')
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    @endif
</section>
