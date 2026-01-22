@props([
    'type' => 'webpage',
    'data' => null,
    'page' => null,
    'post' => null,
    'breadcrumbs' => null,
    'includeWebsite' => false,
])

@php
    use TallCms\Cms\Services\SeoService;

    $schemas = [];

    // Add WebSite schema for homepage
    if ($includeWebsite) {
        $schemas[] = SeoService::getWebsiteJsonLd();
    }

    // Add page-specific schema
    if ($page) {
        $schemas[] = SeoService::getPageJsonLd($page);
    }

    // Add post-specific schema (Article/BlogPosting)
    if ($post) {
        $schemas[] = SeoService::getPostJsonLd($post);
    }

    // Add custom data if provided
    if ($data) {
        $schemas[] = $data;
    }

    // Add breadcrumbs if provided
    if ($breadcrumbs && count($breadcrumbs) > 0) {
        $schemas[] = SeoService::getBreadcrumbJsonLd($breadcrumbs);
    }
@endphp

@foreach($schemas as $schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endforeach
