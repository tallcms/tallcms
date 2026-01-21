# Site Settings & SPA Mode

> **User & Developer Documentation** - Configuring site-wide settings and understanding the SPA mode feature

## Table of Contents

1. [Overview](#overview)
2. [Site Type: Multi-Page vs SPA Mode](#site-type-multi-page-vs-spa-mode)
3. [How SPA Mode Works](#how-spa-mode-works)
4. [Key Differentiators](#key-differentiators)
5. [Branding Settings](#branding-settings)
6. [Social Media Integration](#social-media-integration)
7. [Maintenance Mode](#maintenance-mode)
8. [Technical Implementation](#technical-implementation)

---

## Overview

TallCMS provides comprehensive site-wide settings accessible from **Admin > Settings > Site Settings**. These settings control branding, social media links, SEO defaults, and importantly, how your site operates as either a traditional multi-page website or a modern single-page application (SPA).

---

## Site Type: Multi-Page vs SPA Mode

TallCMS offers two site operation modes:

| Mode | Description | Best For |
|------|-------------|----------|
| **Multi-Page** | Traditional website with separate URLs for each page | Blogs, large sites, SEO-focused sites |
| **Single-Page (SPA)** | All content on one page with smooth scroll navigation | Landing pages, portfolios, small business sites |

### Switching Modes

1. Navigate to **Admin > Settings > Site Settings**
2. Find the **Site Type** dropdown under General settings
3. Select either "Multi-Page Website" or "Single-Page Application (SPA)"
4. Save changes

**No theme modifications required** - the change takes effect immediately.

---

## How SPA Mode Works

When SPA mode is enabled:

### 1. Homepage Becomes a Single Scroll Page
All published pages are rendered as sections on the homepage:

```html
<section id="top">
    <!-- Homepage content -->
</section>
<section id="about-5">
    <!-- About page content -->
</section>
<section id="services-12">
    <!-- Services page content -->
</section>
<section id="contact-18">
    <!-- Contact page content -->
</section>
```

### 2. Automatic Anchor ID Generation
Each page section receives a unique, collision-free anchor ID:

| Page Slug | Page ID | Anchor ID |
|-----------|---------|-----------|
| `about` | 5 | `about-5` |
| `about/team` | 42 | `about-team-42` |
| `services` | 12 | `services-12` |

The format is: `{slug-with-hyphens}-{page_id}`

### 3. Menu Links Auto-Convert
Menu items pointing to pages automatically become anchor links:

- **Multi-page mode**: `/about` → navigates to separate page
- **SPA mode**: `#about-5` → smooth scrolls to section

### 4. Block CTA Buttons Auto-Convert
CTA buttons in Hero blocks, Call-to-Action blocks, etc. also convert:

```php
// Multi-page mode
<a href="/services">Learn More</a>

// SPA mode (automatic)
<a href="#services-12">Learn More</a>
```

### 5. Smooth Scrolling
CSS smooth scrolling is automatically enabled:

```css
html { scroll-behavior: smooth; }
```

---

## Key Differentiators

### Why TallCMS SPA Mode is Special

Most CMS platforms require SPA functionality to be implemented at the **theme level**, meaning:

- ❌ Each theme must implement SPA logic separately
- ❌ Switching themes breaks SPA functionality
- ❌ Theme developers must understand SPA architecture
- ❌ Inconsistent behavior across themes

**TallCMS implements SPA mode at the core level:**

- ✅ **Works with ANY theme automatically** - No theme modifications needed
- ✅ **Consistent behavior** - Same SPA experience regardless of theme
- ✅ **One-click switching** - Toggle between modes instantly
- ✅ **Collision-free anchors** - Page IDs prevent duplicate anchor conflicts
- ✅ **Full block compatibility** - All CTA buttons, menus, and links adapt automatically
- ✅ **SEO-friendly** - Posts still render at their own URLs for proper indexing

### Comparison with Other CMS Platforms

| Feature | TallCMS | WordPress | Other CMSs |
|---------|---------|-----------|------------|
| SPA Mode | Core-level | Theme-dependent | Theme-dependent |
| Theme Compatibility | All themes | Specific themes only | Specific themes only |
| One-Click Toggle | ✅ | ❌ | ❌ |
| Automatic Link Conversion | ✅ | ❌ | ❌ |
| Block CTA Adaptation | ✅ | ❌ | ❌ |

---

## Branding Settings

### Logo
Upload your site logo from **Site Settings > Branding**:

- Displays in header navigation
- Displays in footer
- Falls back to site name text if not set
- Supports cloud storage (S3, DigitalOcean Spaces, etc.)

### Favicon
Upload a favicon for browser tabs and bookmarks.

### Site Name & Tagline
- **Site Name**: Used in header, footer, meta tags, and merge tags
- **Site Tagline**: Displayed in footer and available via `{{site_tagline}}` merge tag

---

## Social Media Integration

Configure social media links from **Site Settings > Social Media**:

| Platform | Setting Key | Merge Tag |
|----------|-------------|-----------|
| Facebook | `social_facebook` | `{{social_facebook}}` |
| Twitter/X | `social_twitter` | `{{social_twitter}}` |
| Instagram | `social_instagram` | `{{social_instagram}}` |
| LinkedIn | `social_linkedin` | `{{social_linkedin}}` |
| YouTube | `social_youtube` | `{{social_youtube}}` |
| TikTok | `social_tiktok` | `{{social_tiktok}}` |
| Newsletter | `newsletter_signup_url` | `{{newsletter_signup}}` |

### Theme Display
Social icons automatically appear in the footer when URLs are configured. Icons:
- Use DaisyUI button components for consistent light/dark mode support
- Only display for platforms with configured URLs
- Include proper accessibility labels

---

## Maintenance Mode

Enable maintenance mode to show a 503 page to visitors while you work on your site.

### Settings
- **Maintenance Mode**: Toggle on/off
- **Maintenance Message**: Custom message shown to visitors

### Bypass Routes
The following routes bypass maintenance mode:
- Admin panel (`/admin/*`)
- Installer (`/install/*`)

### Technical Note
Maintenance mode checks for `installer.lock` in both `base_path()` and `storage_path()` locations for compatibility with different installation methods.

---

## Technical Implementation

### For Developers

#### Helper Function
```php
// Convert page slug to SPA anchor ID
$anchor = tallcms_slug_to_anchor($page->slug, $page->id);
// 'about/team' + 42 → 'about-team-42'
```

#### Key Files
| File | Purpose |
|------|---------|
| `CmsPageRenderer.php` | Loads all pages for SPA mode, renders sections |
| `MenuUrlResolver.php` | Converts page URLs to anchor links in SPA mode |
| `BlockLinkResolver.php` | Converts block CTA URLs to anchors in SPA mode |
| `helpers.php` | Contains `tallcms_slug_to_anchor()` function |

#### SPA Mode Detection
```php
use TallCms\Cms\Models\SiteSetting;

$siteType = SiteSetting::get('site_type', 'multi-page');

if ($siteType === 'single-page') {
    // SPA mode logic
}
```

#### Page Ordering in SPA Mode
Pages are ordered hierarchically:
1. Parent pages by `sort_order`
2. Child pages grouped after their parent by `sort_order`

#### Posts in SPA Mode
Blog posts continue to render at their own URLs (`/blog/post-slug`) even in SPA mode. This ensures:
- Proper SEO indexing for blog content
- Shareable post URLs
- Category/archive functionality

---

## Migration Notes

### Enabling SPA Mode on Existing Sites

When switching from multi-page to SPA mode:

1. **Menu links update automatically** - No manual changes needed
2. **Block CTAs update automatically** - Hero buttons, CTA blocks adapt
3. **Custom anchor links need updating** - Any manually created `#anchor` links in content should be updated to include the page ID suffix (e.g., `#about` → `#about-5`)

### Finding Page IDs

To find a page's ID for manual anchor links:
1. Go to **Admin > Pages**
2. Edit the target page
3. The ID is visible in the URL: `/admin/pages/5/edit` → ID is `5`

Or use the anchor format: `#{slug}-{id}` (e.g., `#about-5`, `#contact-us-18`)
