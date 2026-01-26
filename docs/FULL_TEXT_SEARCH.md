# Full-Text Search

TallCMS includes built-in full-text search powered by Laravel Scout with the database driver. This provides search functionality for both the frontend (public search page) and admin panel (Filament global search) without requiring external services.

## Overview

| Feature | Description |
|---------|-------------|
| **Engine** | Laravel Scout with Database driver |
| **Searchable Content** | Pages and Posts (title, excerpt, meta fields, content blocks) |
| **Multilingual** | Searches across all locales |
| **Frontend** | `/search` page with live results |
| **Admin** | Filament global search in header |

## Requirements

Add the following to your `.env` file:

```env
SCOUT_DRIVER=database
```

## Configuration

Search settings are in `config/tallcms.php`:

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
| `enabled` | `true` | Enable/disable search feature |
| `min_query_length` | `2` | Minimum characters required to search |
| `results_per_page` | `10` | Results per page on frontend |
| `max_results_per_type` | `50` | Maximum results per content type |
| `searchable_types` | `['pages', 'posts']` | Content types to search |

## How It Works

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Page/Post saved                                            │
│       │                                                     │
│       ▼                                                     │
│  SearchContentObserver triggered                            │
│       │                                                     │
│       ▼                                                     │
│  ContentIndexer extracts text from:                         │
│  - Title, excerpt, meta fields (all locales)                │
│  - Tiptap content blocks (all locales)                      │
│       │                                                     │
│       ▼                                                     │
│  search_content column updated (plain text)                 │
│       │                                                     │
│       ▼                                                     │
│  Scout::search() queries with LIKE                          │
└─────────────────────────────────────────────────────────────┘
```

### Denormalized Search Column

The `search_content` column stores pre-extracted plain text from all translatable fields and content blocks. This approach:

- **Works on all databases** - PostgreSQL, MySQL, SQLite
- **Avoids JSON query issues** - No LIKE on JSON columns
- **Supports multilingual** - Content from all locales concatenated
- **Fast indexing** - Text extracted once on save

Content from different locales is separated by ` ||| ` to prevent false matches on locale keys.

### Indexed Content

The ContentIndexer extracts text from:

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

## Frontend Search

### Search Page

The search page is available at `/search` (or `/{locale}/search` with i18n enabled).

Features:
- Live search with debounced input
- Filter by content type (All, Pages, Posts)
- Highlighted search terms in excerpts
- Pagination
- Respects i18n and plugin mode prefixes

### Search Input Component

Add a search input anywhere using the Blade component:

```blade
{{-- Basic usage --}}
<x-tallcms::search-input />

{{-- With custom placeholder --}}
<x-tallcms::search-input placeholder="Search articles..." />

{{-- With custom classes --}}
<x-tallcms::search-input class="max-w-md" />
```

### Theme Integration

The TallDaisy theme includes a header search component with:
- Desktop: Inline expandable search input
- Mobile: Search icon that opens a modal

To add search to your theme's header:

```blade
@if(config('tallcms.search.enabled', true))
<form action="{{ tallcms_search_url() }}" method="GET">
    <input type="search" name="q" placeholder="Search..." />
</form>
@endif
```

### Helper Function

Use `tallcms_search_url()` to get the correct search URL respecting i18n and plugin mode:

```php
// Returns correct URL based on current locale
$url = tallcms_search_url();
// Examples:
// - /search (default)
// - /es/search (Spanish locale)
// - /cms/search (plugin mode with prefix)
// - /cms/es/search (both)
```

## Admin Search

Filament's global search (in the admin header) automatically searches Pages and Posts using the same `search_content` column.

Features:
- Type-ahead search results
- Shows content type badge (Page/Post)
- Shows status (Draft/Published)
- Direct link to edit page
- Limited to 20 results per resource

## Commands

### Rebuild Search Index

After migration or bulk imports, rebuild the search content:

```bash
# Rebuild all content
php artisan tallcms:search-index

# Rebuild only pages
php artisan tallcms:search-index --model=page

# Rebuild only posts
php artisan tallcms:search-index --model=post
```

This command:
1. Iterates through all pages/posts in chunks
2. Extracts text from all locales
3. Updates the `search_content` column directly

## Multilingual Search

### Cross-Locale Matching

Search queries match content in **any locale**. For example, searching "hello" may find a page where:
- English content contains "hello"
- But you're viewing in Spanish

The displayed excerpt uses the current locale's content, which may not contain the matched term.

### How It Works

1. Content from all locales is concatenated into `search_content`
2. Scout searches this combined text
3. Results display using the model's translatable fields in the current locale

This trade-off prioritizes finding relevant content over strict locale filtering.

## Customization

### Adding New Block Types

To make a new block type searchable, update `ContentIndexer::extractFromBlock()`:

```php
// In ContentIndexer.php
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

### Disabling Search

To completely disable search:

```env
TALLCMS_SEARCH_ENABLED=false
```

Or in config:

```php
'search' => [
    'enabled' => false,
],
```

### Limiting Searchable Types

To search only pages (not posts):

```php
'search' => [
    'searchable_types' => ['pages'],
],
```

## Database Migration

The search feature adds a `search_content` column to both tables:

```php
Schema::table('tallcms_pages', function (Blueprint $table) {
    $table->longText('search_content')->nullable();
});

Schema::table('tallcms_posts', function (Blueprint $table) {
    $table->longText('search_content')->nullable();
});
```

Run migrations after updating:

```bash
php artisan migrate
php artisan tallcms:search-index
```

## Troubleshooting

### Search Not Working

1. **Check Scout driver:**
   ```bash
   php artisan tinker
   >>> config('scout.driver')
   # Should return "database"
   ```

2. **Check search is enabled:**
   ```bash
   >>> config('tallcms.search.enabled')
   # Should return true
   ```

3. **Rebuild search index:**
   ```bash
   php artisan tallcms:search-index
   ```

### No Results Returned

1. **Check content is published:**
   - Only published pages/posts appear in frontend search
   - Admin search shows all content

2. **Check search_content column:**
   ```bash
   php artisan tinker
   >>> \TallCms\Cms\Models\CmsPage::first()->search_content
   # Should show extracted text
   ```

3. **Verify minimum query length:**
   - Default is 2 characters
   - Single character searches are ignored

### PostgreSQL Issues

If using PostgreSQL, ensure you're on the latest code. Earlier versions incorrectly searched JSON columns which fails on PostgreSQL. The fix limits search to the `search_content` text column only.

## Performance Considerations

- **Indexing:** Text extraction happens on every save via observer
- **Query:** Uses SQL LIKE with wildcards (not optimal for huge datasets)
- **Pagination:** Results capped at `max_results_per_type` per content type

For large sites (10,000+ pages), consider:
- Using Meilisearch or Algolia instead of database driver
- Adding database indexes on `search_content`
- Implementing search result caching
