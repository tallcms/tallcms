<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sitemaps as $sitemap)
    <sitemap>
        <loc>{{ $sitemap['loc'] }}</loc>
        @if($sitemap['lastmod'])
            <lastmod>{{ $sitemap['lastmod'] instanceof \Carbon\Carbon ? $sitemap['lastmod']->toIso8601String() : $sitemap['lastmod'] }}</lastmod>
        @endif
    </sitemap>
@endforeach
</sitemapindex>
