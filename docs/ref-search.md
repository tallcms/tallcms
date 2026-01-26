---
title: "Full-Text Search"
slug: "search"
audience: "developer"
category: "reference"
order: 50
time: 10
prerequisites:
  - "installation"
---

# Full-Text Search

> **What you'll learn:** How to configure and use full-text search for pages and posts in both the frontend and admin panel.

**Time:** ~10 minutes

---

## Overview

TallCMS includes built-in full-text search powered by Laravel Scout with the database driver. Search works in two places:

| Location | Description |
|----------|-------------|
| **Frontend** | `/search` page with live results |
| **Admin panel** | Global search in the Filament header |

---

## 1. Configure Scout

Add the Scout driver to your `.env` file:

```env
SCOUT_DRIVER=database
```

---

## 2. Set Search Options

Edit `config/tallcms.php` to customize search behavior:

```php
'search' => [
    'enabled' => env('TALLCMS_SEARCH_ENABLED', true),
    'min_query_length' => 2,
    'results_per_page' => 10,
    'max_results_per_type' => 50,
    'searchable_types' => ['pages', 'posts'],
],
```

| Option | Default | Description |
|--------|---------|-------------|
| `enabled` | `true` | Enable or disable search |
| `min_query_length` | `2` | Minimum characters to trigger search |
| `results_per_page` | `10` | Results shown per page |
| `max_results_per_type` | `50` | Maximum results per content type |
| `searchable_types` | `['pages', 'posts']` | Content types to include |

---

## 3. Run Migrations

Apply the search column migration:

```bash
php artisan migrate
```

This adds a `search_content` column to both `tallcms_pages` and `tallcms_posts` tables.

---

## 4. Build the Search Index

Populate the search index for existing content:

```bash
# Index all content
php artisan tallcms:search-index

# Index only pages
php artisan tallcms:search-index --model=page

# Index only posts
php artisan tallcms:search-index --model=post
```

Run this command after migrations, bulk imports, or manual database changes.

---

## How It Works

### Indexing Flow

When you save a page or post:

1. `SearchContentObserver` triggers on save
2. `ContentIndexer` extracts text from all translatable fields and content blocks
3. Plain text is stored in the `search_content` column
4. Scout searches this column using SQL `LIKE` queries

### What Gets Indexed

| Source | Fields |
|--------|--------|
| **Model fields** | title, excerpt, meta_title, meta_description |
| **Hero block** | heading, subheading, cta_text, microcopy |
| **Content block** | title, subtitle, body |
| **FAQ block** | heading, questions, answers |
| **Pricing block** | title, plan names, descriptions, features |
| **Features block** | title, feature titles and descriptions |
| **Team block** | title, member names, roles, bios |
| **Testimonials block** | title, quotes, authors |
| **Timeline block** | title, event titles and descriptions |
| **Stats block** | title, stat labels and descriptions |
| **CTA block** | title, description, button text |

---

## Frontend Search

### Search Page

Access the search page at `/search` (or `/{locale}/search` with i18n enabled).

Features:
- Live search with debounced input
- Filter by content type (All, Pages, Posts)
- Highlighted search terms in excerpts
- Pagination

### Search Input Component

Add a search input anywhere in your theme:

```blade
{{-- Basic usage --}}
<x-tallcms::search-input />

{{-- With custom placeholder --}}
<x-tallcms::search-input placeholder="Search articles..." />

{{-- With custom classes --}}
<x-tallcms::search-input class="max-w-md" />
```

### Helper Function

Use `tallcms_search_url()` to get the correct search URL:

```php
$url = tallcms_search_url();
// Returns:
// - /search (default)
// - /es/search (Spanish locale)
// - /cms/search (plugin mode)
// - /cms/es/search (both)
```

---

## Admin Search

Filament's global search (in the admin header) automatically searches pages and posts. Type in the search field to see results with:

- Content type badge (Page/Post)
- Status indicator (Draft/Published)
- Direct link to edit

---

## Multilingual Search

Search queries match content in **any locale**. For example, searching "hello" finds pages where:
- English content contains "hello"
- Even if you're viewing in Spanish

The displayed excerpt uses the current locale's content, which may not contain the matched term. This is expected behavior for multilingual sites.

---

## Add Custom Block Types

To make a new block type searchable, update `ContentIndexer::extractFromBlock()`:

```php
protected function extractFromBlock(string $blockId, array $attrs): string
{
    return match ($blockId) {
        // ... existing blocks ...
        'my_custom_block' => $this->join([
            $attrs['title'] ?? '',
            $attrs['content'] ?? '',
        ]),
        default => $this->handleUnknownBlock($blockId, $attrs),
    };
}
```

---

## Disable Search

To completely disable search, set the environment variable:

```env
TALLCMS_SEARCH_ENABLED=false
```

Or in config:

```php
'search' => [
    'enabled' => false,
],
```

---

## Common Pitfalls

**"No results returned"**
Check that content is published (frontend only shows published items) and run `php artisan tallcms:search-index` to rebuild the index.

**"Search not working"**
Verify `SCOUT_DRIVER=database` is set in `.env`. Run `php artisan config:clear` after changes.

**"PostgreSQL LIKE errors"**
Ensure you're using the latest code. Earlier versions incorrectly searched JSON columns which fails on PostgreSQL.

**"Query too short"**
Default minimum is 2 characters. Single character searches are ignored.

---

## Performance Notes

For large sites (10,000+ pages), consider:
- Using Meilisearch or Algolia instead of the database driver
- Adding database indexes on `search_content`
- Implementing search result caching

---

## Next Steps

- [Create your first page](first-page)
- [Block development](blocks)
- [Multilingual setup](i18n)
