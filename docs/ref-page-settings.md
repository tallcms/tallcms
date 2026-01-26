---
title: "Page Settings Reference"
slug: "page-settings"
audience: "all"
category: "reference"
order: 10
---

# Page Settings Reference

Complete reference for all page configuration options in TallCMS.

## Page Editor Tabs

| Tab | Purpose |
|-----|---------|
| **Content** | Title, slug, and rich content editor |
| **Settings** | Publishing, layout, and display options |
| **SEO** | Search engine optimization settings |
| **Revisions** | Version history (permission-based) |

---

## Content Tab

### Title

The page title displayed in navigation, breadcrumbs, and browser tabs.

- **Required**: Yes
- **Max Length**: 255 characters
- **Auto-generates**: Slug from title on blur

### Slug

URL-friendly identifier used in the page URL (e.g., `/about-us`).

- **Required**: Yes
- **Format**: Lowercase letters, numbers, hyphens only
- **Unique**: Must be unique across all pages
- **Reserved**: Cannot use locale codes as slugs (e.g., `en`, `fr`)

### Content

Rich content editor with custom blocks, merge tags, and text formatting.

---

## Settings Tab

### Publishing Options

#### Status

| Status | Description |
|--------|-------------|
| **Draft** | Work in progress, not publicly visible |
| **Pending Review** | Awaiting approval |
| **Published** | Live and publicly accessible |
| **Archived** | Hidden from public but preserved |

#### Publish Date

- **Empty**: Publishes immediately when status is Published
- **Future Date**: Content becomes visible at specified date/time

#### Author

Assign page authorship. Defaults to current user.

### Page Hierarchy

#### Set as Homepage

Designates this page as the site's homepage (accessible at `/`).

- Only one page can be the homepage
- Setting a new homepage removes the flag from the previous one

#### Parent Page

Creates hierarchical page structure for nested navigation.

- Child pages inherit breadcrumb structure
- URL structure can reflect hierarchy (theme-dependent)

#### Sort Order

Numeric value controlling display order in navigation.

- Lower numbers appear first
- Default: 0

### Breadcrumbs

| Setting | Default | Description |
|---------|---------|-------------|
| **Show Breadcrumbs** | `true` | Display breadcrumb navigation |

- Homepage never shows breadcrumbs
- Child pages show full path: Home > Parent > Current

### Content Width

Controls maximum width of inline content.

| Option | CSS Class | Width | Best For |
|--------|-----------|-------|----------|
| **Narrow** | `max-w-2xl` | 672px | Forms, documentation |
| **Standard** | `max-w-6xl` | 1152px | Most content |
| **Wide** | `max-w-7xl` | 1280px | Image-heavy pages |

#### Block Width Behavior

- **Most blocks**: Inherit from page
- **Wide default**: Features, Pricing, Gallery, Team, Stats
- **Full width**: Hero, Parallax (no width option)
- **Narrow default**: Contact Form

### Custom Template

Override the default page template with a custom Blade file.

- **Format**: Template name without `.blade.php`
- **Location**: `resources/views/` or theme views directory
- **Example**: `pages.custom-landing`

---

## SEO Tab

### Meta Title

Custom title for search engine results.

- **Max Length**: 60 characters
- **Recommended**: 50-60 characters
- **Fallback**: Uses page title if empty

### Meta Description

Brief description for search engine results.

- **Max Length**: 160 characters
- **Recommended**: 150-160 characters

### Featured Image

Primary image for social media sharing.

- **Recommended Size**: 1200x630px
- **Supported Formats**: JPEG, PNG, WebP
- **Used For**: Open Graph, Twitter Cards

---

## Revisions Tab

View and restore previous versions.

**Requirements:**
- Page must be saved at least once
- User must have `ViewRevisions:CmsPage` permission

**Features:**
- Timestamp and author for each revision
- Preview revision content
- Restore to any previous version
- Compare revisions (diff view)

---

## Programmatic Access

### Accessing Page Settings

```php
use TallCms\Cms\Models\CmsPage;

$page = CmsPage::find($id);

// Publishing
$page->status;           // 'draft', 'pending', 'published', 'archived'
$page->published_at;     // Carbon instance or null
$page->is_homepage;      // boolean

// Hierarchy
$page->parent_id;        // int or null
$page->parent;           // CmsPage model or null
$page->children;         // Collection of child pages
$page->sort_order;       // int

// Layout
$page->content_width;    // 'narrow', 'standard', 'wide'
$page->show_breadcrumbs; // boolean
$page->template;         // string or null

// SEO
$page->meta_title;       // string or null
$page->meta_description; // string or null
$page->featured_image;   // string (path) or null
```

### Content Width Helper

```php
$page->getContentWidthClass(); // 'max-w-2xl', 'max-w-6xl', or 'max-w-7xl'
```

### Querying Pages

```php
// Published pages
CmsPage::where('status', 'published')->get();

// Homepage
CmsPage::where('is_homepage', true)->first();

// Child pages
CmsPage::where('parent_id', $parentId)
    ->orderBy('sort_order')
    ->get();
```

---

## Next Steps

- [Publishing workflow](publishing)
- [SEO settings](seo)
- [Managing pages](pages-posts)
