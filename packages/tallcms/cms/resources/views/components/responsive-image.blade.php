@props(['media', 'size' => 'medium', 'class' => '', 'lazy' => true])

@php
    $hasVariant = $media->hasVariant($size);
    $variantUrl = $hasVariant ? $media->getVariantUrl($size) : null;
    $fallbackUrl = $media->url;
@endphp

<picture>
    @if($hasVariant)
        <source srcset="{{ $variantUrl }}" type="image/webp">
    @endif
    <img
        src="{{ $fallbackUrl }}"
        alt="{{ $media->alt_text ?? '' }}"
        @if($media->width) width="{{ $media->width }}" @endif
        @if($media->height) height="{{ $media->height }}" @endif
        @class([$class])
        @if($lazy) loading="lazy" @endif
    >
</picture>
