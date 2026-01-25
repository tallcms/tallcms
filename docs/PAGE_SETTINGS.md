# Page Settings

TallCMS provides comprehensive page configuration options for content management, layout control, SEO optimization, and publishing workflow.

## Table of Contents

- [Accessing Page Settings](#accessing-page-settings)
- [Content Tab](#content-tab)
- [Settings Tab](#settings-tab)
  - [Publishing Options](#publishing-options)
  - [Page Hierarchy](#page-hierarchy)
  - [Breadcrumbs](#breadcrumbs)
  - [Content Width](#content-width)
  - [Custom Template](#custom-template)
- [SEO Tab](#seo-tab)
- [Revisions Tab](#revisions-tab)

---

## Accessing Page Settings

Navigate to **Admin > Pages** and click on any page to edit it. The page editor is organized into tabs:

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

- **Required**: Yes (for default locale when i18n enabled)
- **Max Length**: 255 characters
- **Auto-generates**: Slug from title on blur

### Slug

URL-friendly identifier used in the page URL (e.g., `/about-us`).

- **Required**: Yes (for default locale when i18n enabled)
- **Format**: Lowercase letters, numbers, hyphens only
- **Unique**: Must be unique across all pages
- **Reserved**: Cannot use locale codes as slugs (e.g., `en`, `fr`)

### Content

Rich content editor with custom blocks, merge tags, and text formatting.

See [CMS Rich Editor](CMS_RICH_EDITOR.md) for detailed documentation.

---

## Settings Tab

### Publishing Options

#### Status

Controls page visibility:

| Status | Description |
|--------|-------------|
| **Draft** | Work in progress, not publicly visible |
| **Pending Review** | Awaiting approval (requires `Approve:CmsPage` permission to publish) |
| **Published** | Live and publicly accessible |
| **Archived** | Hidden from public but preserved |

Authors can set Draft or Pending Review. Only users with approval permission can publish.

#### Publish Date

Schedule future publication or backdate content.

- **Empty**: Publishes immediately when status is set to Published
- **Future Date**: Content becomes visible at the specified date/time
- **Visible to**: Users with `Approve:CmsPage` permission only

#### Author

Assign page authorship. Defaults to the current user.

### Page Hierarchy

#### Set as Homepage

Designates this page as the site's homepage (accessible at `/`).

- Only one page can be the homepage at a time
- Setting a new homepage automatically removes the flag from the previous one

#### Parent Page

Creates a hierarchical page structure for nested navigation.

- Select a parent to create child pages
- Child pages inherit breadcrumb structure
- URL structure can reflect hierarchy (theme-dependent)

#### Sort Order

Numeric value controlling display order in navigation and listings.

- Lower numbers appear first
- Default: 0

---

### Breadcrumbs

Controls whether navigation breadcrumbs appear on the page.

| Setting | Default | Description |
|---------|---------|-------------|
| **Show Breadcrumbs** | `true` | Display breadcrumb navigation |

**Behavior:**
- Homepage never shows breadcrumbs (regardless of setting)
- Child pages show full path: Home > Parent > Current Page
- Breadcrumbs use page titles as labels
- Links are generated from page slugs

**Theme Integration:**

Themes should include the breadcrumbs component in their layout:

```blade
@if($page->show_breadcrumbs && !$page->is_homepage)
    <x-tallcms::breadcrumbs :page="$page" />
@endif
```

---

### Content Width

Controls the maximum width of inline content (text, headings, lists) typed directly in the Rich Editor.

| Option | CSS Class | Width | Best For |
|--------|-----------|-------|----------|
| **Narrow** | `max-w-2xl` | 672px | Forms, focused reading, documentation |
| **Standard** | `max-w-6xl` | 1152px | Most content (default) |
| **Wide** | `max-w-7xl` | 1280px | Image-heavy pages, portfolios |

#### How It Works

1. **Inline Content**: Paragraphs, headings, lists, and blockquotes typed directly in the editor follow the page width setting.

2. **Block Inheritance**: Most blocks default to "Inherit from Page", automatically matching your page width. This ensures visual consistency.

3. **Block Overrides**: Each block can override the page setting. Options include:
   - **Inherit from Page** - Match page width (default for most blocks)
   - **Narrow** (672px)
   - **Standard** (1152px)
   - **Wide** (1280px)
   - **Full Width** - Edge-to-edge (block-level only)

#### Block Width Presets

Different block types have sensible defaults:

| Default | Blocks |
|---------|--------|
| **Inherit** | Content Block, FAQ, CTA, Posts, Accordion, Tabs, Divider, Code Snippet, Comparison |
| **Wide** | Features, Pricing, Image Gallery, Team, Testimonials, Timeline, Stats, Logos |
| **Full** | Hero, Parallax (always full-bleed, no width option) |
| **Narrow** | Contact Form |

#### Example Use Cases

**Documentation Site** (Narrow):
- Set page to Narrow for optimal reading
- Code snippets and content blocks auto-inherit
- Feature comparison tables can override to Wide

**Marketing Landing Page** (Standard):
- Set page to Standard for balanced layout
- Hero blocks are full-width by design
- Testimonials and features use Wide preset

**Portfolio/Gallery** (Wide):
- Set page to Wide for image-heavy content
- Gallery blocks maximize available space

---

### Custom Template

Override the default page template with a custom Blade file.

- **Format**: Template name without `.blade.php` extension
- **Location**: `resources/views/` or theme views directory
- **Example**: `pages.custom-landing` loads `resources/views/pages/custom-landing.blade.php`

Leave empty to use the default page template.

---

## SEO Tab

See [SEO Features](SEO.md) for comprehensive documentation.

### Meta Title

Custom title for search engine results and browser tabs.

- **Max Length**: 60 characters (recommended: 50-60)
- **Fallback**: Uses page title if empty

### Meta Description

Brief description appearing in search engine results.

- **Max Length**: 160 characters (recommended: 150-160)
- **Purpose**: Improves click-through rates from search results

### Featured Image

Primary image for social media sharing and page headers.

- **Recommended Size**: 1200x630px (optimal for Facebook/LinkedIn)
- **Supported Formats**: JPEG, PNG, WebP
- **Aspect Ratios**: 16:9, 4:3, 1:1, 1.91:1, 2:1
- **Used For**: Open Graph, Twitter Cards, page headers (theme-dependent)

---

## Revisions Tab

View and restore previous versions of the page.

**Requirements:**
- Page must be saved at least once
- User must have `ViewRevisions:CmsPage` permission

**Features:**
- Timestamp and author for each revision
- Preview revision content
- Restore to any previous version
- Compare revisions (diff view)

See [Publishing Workflow](PUBLISHING_WORKFLOW.md) for revision management details.

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
// Get the Tailwind class for the page's content width
$page->getContentWidthClass(); // Returns 'max-w-2xl', 'max-w-6xl', or 'max-w-7xl'
```

### Querying Pages

```php
// Get all published pages
CmsPage::where('status', 'published')->get();

// Get homepage
CmsPage::where('is_homepage', true)->first();

// Get child pages of a parent
CmsPage::where('parent_id', $parentId)
    ->orderBy('sort_order')
    ->get();

// Get pages with breadcrumbs enabled
CmsPage::where('show_breadcrumbs', true)->get();
```
