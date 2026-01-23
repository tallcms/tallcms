@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'url' => null,
    'article' => null,
    'twitter' => null,
    'profile' => null,
    'noindex' => false,
])

@php
    use TallCms\Cms\Models\SiteSetting;
    use TallCms\Cms\Services\SeoService;

    // Get site-wide defaults
    $siteName = SiteSetting::get('site_name', config('app.name'));
    $siteDescription = SiteSetting::get('site_description', '');
    $twitterSite = SeoService::getTwitterSite();
    $defaultImage = SeoService::getDefaultOgImage();

    // Resolve values with fallbacks
    $metaTitle = $title ?? $siteName;
    $metaDescription = $description ?? $siteDescription;
    $metaImage = $image ?? $defaultImage;
    $metaUrl = $url ?? request()->url();
    $metaType = $type;

    // Get RSS feed URL for auto-discovery
    // Only show if RSS is enabled in settings AND the feed route actually exists
    $rssEnabled = SiteSetting::get('seo_rss_enabled', true);
    $feedRouteExists = Route::has('tallcms.feed');
    $feedUrl = $feedRouteExists ? route('tallcms.feed') : null;
@endphp

{{-- Page Title --}}
<title>{{ $metaTitle }}</title>

{{-- Meta Description --}}
@if($metaDescription)
<meta name="description" content="{{ $metaDescription }}">
@endif

{{-- Robots --}}
@if($noindex)
<meta name="robots" content="noindex, nofollow">
@endif

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $metaUrl }}">

{{-- Open Graph --}}
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:type" content="{{ $metaType }}">
<meta property="og:url" content="{{ $metaUrl }}">
<meta property="og:site_name" content="{{ $siteName }}">
@if($metaDescription)
<meta property="og:description" content="{{ $metaDescription }}">
@endif
@if($metaImage)
<meta property="og:image" content="{{ $metaImage }}">
@endif

{{-- Article-specific OG tags --}}
@if($metaType === 'article' && $article)
@if($article['published_time'] ?? null)
<meta property="article:published_time" content="{{ $article['published_time'] }}">
@endif
@if($article['modified_time'] ?? null)
<meta property="article:modified_time" content="{{ $article['modified_time'] }}">
@endif
@if($article['author'] ?? null)
<meta property="article:author" content="{{ $article['author'] }}">
@endif
@if($article['section'] ?? null)
<meta property="article:section" content="{{ $article['section'] }}">
@endif
@foreach($article['tags'] ?? [] as $tag)
<meta property="article:tag" content="{{ $tag }}">
@endforeach
@endif

{{-- Profile-specific OG tags --}}
@if($metaType === 'profile' && $profile)
@if($profile['first_name'] ?? null)
<meta property="profile:first_name" content="{{ $profile['first_name'] }}">
@endif
@if($profile['last_name'] ?? null)
<meta property="profile:last_name" content="{{ $profile['last_name'] }}">
@endif
@endif

{{-- Twitter Cards --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
@if($metaDescription)
<meta name="twitter:description" content="{{ $metaDescription }}">
@endif
@if($metaImage)
<meta name="twitter:image" content="{{ $metaImage }}">
@endif
@if($twitterSite)
<meta name="twitter:site" content="{{ $twitterSite }}">
@endif

{{-- Twitter Label/Data pairs for articles --}}
@if($twitter)
@if($twitter['label1'] ?? null)
<meta name="twitter:label1" content="{{ $twitter['label1'] }}">
<meta name="twitter:data1" content="{{ $twitter['data1'] }}">
@endif
@if($twitter['label2'] ?? null)
<meta name="twitter:label2" content="{{ $twitter['label2'] }}">
<meta name="twitter:data2" content="{{ $twitter['data2'] }}">
@endif
@endif

{{-- RSS Feed Auto-Discovery (only if route exists and RSS enabled) --}}
@if($rssEnabled && $feedUrl)
<link rel="alternate" type="application/rss+xml" title="{{ $siteName }} RSS Feed" href="{{ $feedUrl }}">
@endif
