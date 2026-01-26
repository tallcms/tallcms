---
title: "SEO Features"
slug: "seo"
audience: "site-owner"
category: "site-management"
order: 40
---

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

Sitemaps are cached for 1 hour by default. Clear the sitemap cache from **Admin > Settings > SEO Settings** or wait for the cache to expire.

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
- Categories
- Permalink

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

For blog posts, additional article-specific tags are included with publication dates, author, and categories.

### Twitter Cards

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Page Title">
<meta name="twitter:description" content="Page description...">
<meta name="twitter:image" content="https://example.com/image.jpg">
```

---

## Structured Data (JSON-LD)

TallCMS generates JSON-LD structured data for rich search results.

### Article Schema (Blog Posts)

Includes headline, description, image, dates, author, and publisher information.

### WebPage Schema (CMS Pages)

Includes page name, description, and URL.

### BreadcrumbList Schema

Provides navigation path for search engines.

### Testing Structured Data

Use Google's [Rich Results Test](https://search.google.com/test/rich-results) to validate your structured data.

---

## Page-Level SEO Settings

Each page and post has SEO fields in the **SEO** tab:

| Field | Description |
|-------|-------------|
| **Meta Title** | Custom title for search results (50-60 chars) |
| **Meta Description** | Brief description for search results (150-160 chars) |
| **Featured Image** | Image for social sharing (1200x630px recommended) |

---

## Plugin Mode Configuration

When using TallCMS as a plugin, SEO routes are configurable to avoid conflicts.

### Core SEO Routes (Default: Enabled)

These routes are always at the root level:
- `/robots.txt`
- `/sitemap.xml`
- `/sitemap-*.xml`

### Archive Routes (Default: Disabled)

Enable with `TALLCMS_ARCHIVE_ROUTES_ENABLED=true`:
- `/feed`
- `/category/{slug}`
- `/author/{slug}`

Use `TALLCMS_ARCHIVE_ROUTES_PREFIX=blog` to prefix these routes.

---

## Validation Tools

Test your SEO implementation:

- **Sitemap**: [XML Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html)
- **RSS Feed**: [W3C Feed Validator](https://validator.w3.org/feed/)
- **Open Graph**: [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- **Twitter Cards**: [Twitter Card Validator](https://cards-dev.twitter.com/validator)
- **Structured Data**: [Google Rich Results Test](https://search.google.com/test/rich-results)

---

## Common Pitfalls

**"Sitemap not updating"**
Clear the sitemap cache from **Admin > Settings > SEO Settings** or wait for the 1-hour cache to expire.

**"Social image not appearing"**
Upload a Featured Image in the SEO tab. Minimum size is 200x200px; recommended is 1200x630px.

**"robots.txt showing old content"**
Delete any static `public/robots.txt` file. TallCMS serves robots.txt dynamically.

---

## Next Steps

- [Page settings reference](page-settings)
- [Site settings](site-settings)
- [Managing pages and posts](pages-posts)
