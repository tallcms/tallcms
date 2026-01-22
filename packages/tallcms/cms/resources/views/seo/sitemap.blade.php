<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if($url['lastmod'] ?? null)
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        @if($url['changefreq'] ?? null)
            <changefreq>{{ $url['changefreq'] }}</changefreq>
        @endif
        @if($url['priority'] ?? null)
            <priority>{{ $url['priority'] }}</priority>
        @endif
    </url>
@endforeach
</urlset>
