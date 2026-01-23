@props(['model' => null])
@php
    // Check if i18n is enabled
    if (!tallcms_i18n_enabled()) {
        return;
    }

    $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
    $default = $registry->getDefaultLocale();
    $locales = $registry->getLocales();

    // Get alternate URLs for the model
    $alternateUrls = [];
    if ($model && method_exists($model, 'getTranslation')) {
        $alternateUrls = tallcms_alternate_urls($model);
    } else {
        // Fallback: use current page URL with locale prefix
        foreach ($locales as $code => $locale) {
            $alternateUrls[$code] = tallcms_localized_url(tallcms_current_slug(), $code);
        }
    }
@endphp

@if(count($alternateUrls) > 1)
    @foreach($alternateUrls as $locale => $url)
        {{-- Convert internal format (es_mx) to BCP-47 (es-MX) for hreflang --}}
        @php $bcp47 = \TallCms\Cms\Services\LocaleRegistry::toBcp47($locale); @endphp
        <link rel="alternate" hreflang="{{ $bcp47 }}" href="{{ url($url) }}" />
    @endforeach
    {{-- x-default points to default locale version --}}
    @if(isset($alternateUrls[$default]))
        <link rel="alternate" hreflang="x-default" href="{{ url($alternateUrls[$default]) }}" />
    @endif
@endif
