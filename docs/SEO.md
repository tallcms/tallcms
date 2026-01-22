# TallCMS SEO Features

TallCMS includes comprehensive SEO features out of the box: XML sitemaps, robots.txt management, RSS feeds, Open Graph meta tags, Twitter Cards, and JSON-LD structured data.

## Table of Contents

- [XML Sitemap](#xml-sitemap)
- [robots.txt](#robotstxt)
- [RSS Feeds](#rss-feeds)
- [Archive Pages](#archive-pages)
- [Meta Tags](#meta-tags)
- [Structured Data (JSON-LD)](#structured-data-json-ld)
- [Plugin Mode Configuration](#plugin-mode-configuration)

---

## XML Sitemap

TallCMS automatically generates XML sitemaps following the [sitemaps.org protocol](https://www.sitemaps.org/protocol.html).

### Sitemap Structure

For sites with many pages, TallCMS uses a sitemap index that references multiple child sitemaps:

| URL | Description |
|-----|-------------|
| `/sitemap.xml` | Sitemap index (entry point) |
| `/sitemap-pages.xml` | All published CMS pages |
| `/sitemap-posts-{n}.xml` | Published posts (paginated, 1000 per file) |
| `/sitemap-categories.xml` | Category archive pages |
| `/sitemap-authors.xml` | Author archive pages |

### Example Sitemap Index

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap-pages.xml</loc>
        <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap-posts-1.xml</loc>
        <lastmod>2024-01-20T14:22:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap-categories.xml</loc>
        <lastmod>2024-01-18T09:15:00+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap-authors.xml</loc>
        <lastmod>2024-01-19T16:45:00+00:00</lastmod>
    </sitemap>
</sitemapindex>
```

### Sitemap Entry Details

Each URL entry includes:

- **loc**: Full URL to the page
- **lastmod**: Last modification date (ISO 8601 format)
- **changefreq**: Expected change frequency (`daily`, `weekly`, `monthly`)
- **priority**: Relative importance (0.0 to 1.0)

Priority is automatically calculated:
- Homepage: 1.0
- Featured posts: 0.9
- Regular posts: 0.7
- Pages: 0.8
- Archives: 0.5

### Caching

Sitemaps are cached for 1 hour by default. Cache is automatically invalidated when:
- Pages or posts are published/unpublished
- Categories are created/updated/deleted
- Author information changes

---

## robots.txt

TallCMS serves a dynamic `robots.txt` from the database, allowing admin configuration.

### Default Content

```
User-agent: *
Allow: /

Sitemap: https://yoursite.com/sitemap.xml
```

### Admin Configuration

Edit robots.txt content in **Admin > Settings > SEO Settings**:

- Custom directives (Disallow paths, Crawl-delay, etc.)
- Toggle automatic sitemap URL inclusion

### Important Notes

- The route serves `/robots.txt` dynamically - delete any static `public/robots.txt` file
- Sitemap URL is automatically appended when enabled in settings
- robots.txt is always served at the root level (no prefix)

---

## RSS Feeds

TallCMS provides RSS 2.0 feeds for blog content.

### Feed URLs

| URL | Description |
|-----|-------------|
| `/feed` | Main RSS feed (all published posts) |
| `/feed/category/{slug}` | Category-specific feed |

### Feed Content

Each feed item includes:
- Title
- Excerpt or full content (configurable)
- Author name
- Publication date
- Categories/tags
- Permalink

### Auto-Discovery

Add the RSS auto-discovery link to your layout's `<head>`:

```blade
<link rel="alternate" type="application/rss+xml"
      title="{{ config('app.name') }} RSS Feed"
      href="{{ url('/feed') }}">
```

### Configuration

RSS settings are available in **Admin > Settings > SEO Settings**:

- `seo_rss_enabled`: Enable/disable RSS feeds
- `seo_rss_limit`: Number of posts per feed (default: 20)
- `seo_rss_full_content`: Include full content vs excerpt only

---

## Archive Pages

TallCMS provides archive pages for browsing content by category or author.

### Category Archives

**URL**: `/category/{slug}`

Displays all published posts in a category with pagination.

### Author Archives

**URL**: `/author/{slug}`

Displays all published posts by an author with pagination.

### Archive Page Features

- Paginated post listings
- SEO-optimized meta tags
- Breadcrumb structured data
- Category/author information header

---

## Meta Tags

TallCMS automatically generates comprehensive meta tags for all pages.

### Open Graph Tags

```html
<meta property="og:title" content="Page Title">
<meta property="og:description" content="Page description...">
<meta property="og:url" content="https://example.com/page">
<meta property="og:type" content="article">
<meta property="og:image" content="https://example.com/image.jpg">
<meta property="og:site_name" content="Site Name">
```

For blog posts, additional article-specific tags:

```html
<meta property="article:published_time" content="2024-01-15T10:30:00+00:00">
<meta property="article:modified_time" content="2024-01-16T14:22:00+00:00">
<meta property="article:author" content="Author Name">
<meta property="article:section" content="Category Name">
<meta property="article:tag" content="Tag1">
<meta property="article:tag" content="Tag2">
```

### Twitter Cards

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Page Title">
<meta name="twitter:description" content="Page description...">
<meta name="twitter:image" content="https://example.com/image.jpg">
<meta name="twitter:label1" content="Written by">
<meta name="twitter:data1" content="Author Name">
<meta name="twitter:label2" content="Reading time">
<meta name="twitter:data2" content="5 min read">
```

### Using SEO Components in Themes

Include the SEO components in your theme's layout `<head>`:

```blade
<head>
    {{-- SEO Meta Tags --}}
    <x-tallcms::seo.meta-tags
        :title="$title ?? null"
        :description="$description ?? null"
        :image="isset($featuredImage) && $featuredImage ? Storage::disk(cms_media_disk())->url($featuredImage) : null"
        :type="$seoType ?? 'website'"
        :article="$seoArticle ?? null"
        :twitter="$seoTwitter ?? null"
        :profile="$seoProfile ?? null"
    />

    {{-- Structured Data --}}
    <x-tallcms::seo.structured-data
        :page="$seoPage ?? null"
        :post="$seoPost ?? null"
        :breadcrumbs="$seoBreadcrumbs ?? null"
        :includeWebsite="$seoIncludeWebsite ?? false"
    />

    <!-- Other head content -->
</head>
```

---

## Structured Data (JSON-LD)

TallCMS generates JSON-LD structured data for rich search results.

### Article Schema (Blog Posts)

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Post Title",
  "description": "Post excerpt...",
  "image": "https://example.com/featured-image.jpg",
  "datePublished": "2024-01-15T10:30:00+00:00",
  "dateModified": "2024-01-16T14:22:00+00:00",
  "author": {
    "@type": "Person",
    "name": "Author Name"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Site Name",
    "logo": {
      "@type": "ImageObject",
      "url": "https://example.com/logo.png"
    }
  }
}
```

### WebPage Schema (CMS Pages)

```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Page Title",
  "description": "Page description...",
  "url": "https://example.com/about"
}
```

### BreadcrumbList Schema

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://example.com"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Blog",
      "item": "https://example.com/blog"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Post Title"
    }
  ]
}
```

### Testing Structured Data

Use Google's [Rich Results Test](https://search.google.com/test/rich-results) to validate your structured data.

---

## Plugin Mode Configuration

When using TallCMS as a plugin in an existing Laravel/Filament application, SEO routes are configurable to avoid conflicts.

### Core SEO Routes (Default: Enabled)

These routes are **always registered at the root level** (no prefix) since search engines expect them at standard locations:

```
/robots.txt      → Dynamic robots.txt
/sitemap.xml     → Sitemap index
/sitemap-*.xml   → Child sitemaps
```

**Config**: `tallcms.plugin_mode.seo_routes_enabled`
**Environment**: `TALLCMS_SEO_ROUTES_ENABLED=true`

### Archive Routes (Default: Disabled in Plugin Mode)

These routes may conflict with your application's existing routes:

```
/feed                  → Main RSS feed
/feed/category/{slug}  → Category RSS feed
/category/{slug}       → Category archive page
/author/{slug}         → Author archive page
```

**Config**: `tallcms.plugin_mode.archive_routes_enabled`
**Environment**: `TALLCMS_ARCHIVE_ROUTES_ENABLED=false`

### Archive Routes Prefix

To avoid conflicts, you can prefix archive routes:

```env
TALLCMS_ARCHIVE_ROUTES_PREFIX=blog
```

This results in:
- `/blog/feed`
- `/blog/feed/category/{slug}`
- `/blog/category/{slug}`
- `/blog/author/{slug}`

### Configuration Summary

| Setting | Default | Description |
|---------|---------|-------------|
| `seo_routes_enabled` | `true` | Enable sitemap.xml, robots.txt (always at root) |
| `archive_routes_enabled` | `false` | Enable /feed, /category, /author routes |
| `archive_routes_prefix` | `''` | Optional prefix for archive routes |

### Example .env Configuration

```env
# Enable all SEO features
TALLCMS_SEO_ROUTES_ENABLED=true
TALLCMS_ARCHIVE_ROUTES_ENABLED=true

# Or prefix archive routes to avoid conflicts
TALLCMS_ARCHIVE_ROUTES_ENABLED=true
TALLCMS_ARCHIVE_ROUTES_PREFIX=blog
```

---

## Validation Tools

Test your SEO implementation with these tools:

- **Sitemap**: [XML Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html)
- **RSS Feed**: [W3C Feed Validator](https://validator.w3.org/feed/)
- **Open Graph**: [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- **Twitter Cards**: [Twitter Card Validator](https://cards-dev.twitter.com/validator)
- **Structured Data**: [Google Rich Results Test](https://search.google.com/test/rich-results)
- **General SEO**: [Google Search Console](https://search.google.com/search-console)
