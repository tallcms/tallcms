@php
    $heightClass = match($height ?? 'medium') {
        'small' => 'min-h-[300px]',
        'medium' => 'min-h-[500px]',
        'large' => 'min-h-[700px]',
        'full' => 'min-h-screen',
        default => 'min-h-[500px]',
    };

    // text_alignment now comes as a class like 'text-center'
    $textAlign = $text_alignment ?? 'text-center';
    $itemsClass = match($textAlign) {
        'text-left' => 'items-start',
        'text-right' => 'items-end',
        default => 'items-center',
    };

    $justifyClass = match($textAlign) {
        'text-left' => 'justify-start',
        'text-right' => 'justify-end',
        default => 'justify-center',
    };

    $overlayOpacityDecimal = ((int)($overlay_opacity ?? 50)) / 100;
    $overlayColorRgba = $overlay_color ?? '#000000';

    // Convert hex to rgba for overlay
    $hex = ltrim($overlayColorRgba, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $overlayRgba = "rgba({$r}, {$g}, {$b}, {$overlayOpacityDecimal})";

    $hasContent = !empty($heading) || !empty($subheading) || !empty($cta_text);
    $imageUrl = !empty($image) ? Storage::disk(cms_media_disk())->url($image) : null;
@endphp

<section @if($anchor_id ?? null) id="{{ $anchor_id }}" @endif class="parallax-block relative overflow-hidden {{ $heightClass }} {{ $css_classes ?? '' }}">
    {{-- Background with CSS-only parallax --}}
    @if($imageUrl)
        <div
            class="parallax-bg absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('{{ $imageUrl }}');"
        ></div>
    @else
        {{-- Placeholder gradient for preview --}}
        <div class="absolute inset-0 bg-gradient-to-br from-neutral to-neutral-focus"></div>
    @endif

    {{-- Overlay --}}
    @if($overlayOpacityDecimal > 0)
        <div
            class="absolute inset-0"
            style="background-color: {{ $overlayRgba }};"
        ></div>
    @endif

    {{-- Content --}}
    @if($hasContent)
        <div class="relative z-10 h-full flex flex-col {{ $justifyClass }} {{ $itemsClass }} {{ $textAlign }} px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="max-w-4xl {{ $textAlign === 'text-center' ? 'mx-auto' : ($textAlign === 'text-right' ? 'ml-auto' : '') }}">
                @if(!empty($heading))
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4 drop-shadow-lg">
                        {{ $heading }}
                    </h2>
                @endif

                @if(!empty($subheading))
                    <p class="text-lg sm:text-xl text-white/90 mb-8 max-w-2xl {{ $textAlign === 'text-center' ? 'mx-auto' : '' }} drop-shadow">
                        {{ $subheading }}
                    </p>
                @endif

                @if(!empty($cta_text) && !empty($cta_url))
                    <a
                        href="{{ e($cta_url) }}"
                        class="btn btn-neutral bg-white text-gray-900 hover:bg-gray-100 border-white gap-2"
                    >
                        {{ $cta_text }}
                        <x-heroicon-m-arrow-right class="w-5 h-5" />
                    </a>
                @endif
            </div>
        </div>
    @endif
</section>

<style>
    /* CSS-only parallax effect */
    .parallax-block .parallax-bg {
        background-attachment: fixed;
        will-change: transform;
    }

    /* Disable parallax for reduced motion preference */
    @media (prefers-reduced-motion: reduce) {
        .parallax-block .parallax-bg {
            background-attachment: scroll;
        }
    }

    /* Disable parallax on mobile (performance + iOS issues) */
    @media (max-width: 768px) {
        .parallax-block .parallax-bg {
            background-attachment: scroll;
        }
    }

    /* Disable parallax on touch devices */
    @media (hover: none) {
        .parallax-block .parallax-bg {
            background-attachment: scroll;
        }
    }
</style>
