---
title: "Page Templates & Sidebar Widgets"
slug: "templates"
audience: "site-owner"
category: "site-management"
order: 15
---

# Page Templates & Sidebar Widgets

> **What you'll learn:** How to use page templates for different layouts and configure sidebar widgets for enhanced navigation.

---

## Overview

TallCMS includes a template system that lets you choose different layouts for each page:

| Template | Description | Sidebar |
|----------|-------------|---------|
| **Default** | Standard centered content | No |
| **Full Width** | Edge-to-edge layout | No |
| **Sidebar (Left)** | Content with left sidebar | Yes |
| **Sidebar (Right)** | Content with right sidebar | Yes |
| **Documentation** | Prose styling with TOC sidebar | Yes |
| **Landing** | Minimal header/footer for marketing pages | No |

---

## Selecting a Template

1. Go to **Admin > Content > Pages**
2. Edit your page
3. In the **Settings** tab, find **Page Template**
4. Select your desired template
5. Click **Save**

The page immediately uses the new layout.

---

## Sidebar Templates

Templates with sidebars (Sidebar Left, Sidebar Right, Documentation) let you add widgets to the sidebar area.

### Configuring Sidebar Widgets

1. Select a sidebar template for your page
2. The **Sidebar Widgets** section appears
3. Click **Add Widget** to add widgets
4. Configure each widget's settings
5. Drag to reorder widgets
6. Click **Save**

**Leave widgets empty** to use the template's default widgets. For example, the Documentation template defaults to showing a Table of Contents.

---

## Available Widgets

### Recent Posts

Displays your latest blog posts in the sidebar.

| Setting | Description |
|---------|-------------|
| **Limit** | Number of posts to show (default: 5) |
| **Show thumbnails** | Display featured images |

### Categories

Lists post categories with optional post counts.

| Setting | Description |
|---------|-------------|
| **Show post count** | Display number of posts per category |

### Table of Contents

Auto-generates navigation from your page headings.

| Setting | Description |
|---------|-------------|
| **Max heading depth** | Deepest heading level to include (2-4) |

**How it works:**
- Scans your page content for `h2`, `h3`, and `h4` headings
- Automatically adds anchor IDs to headings
- Creates clickable links that scroll to each section
- Indents nested headings for visual hierarchy

**Best for:** Documentation, long-form articles, guides.

### Search

Adds a site search box to the sidebar.

### Custom HTML

Embed custom HTML content (admin-only).

| Setting | Description |
|---------|-------------|
| **HTML Content** | Your custom HTML code |

**Note:** For security, only administrators can add Custom HTML widgets. Content is sanitized before display.

---

## Template Details

### Default

The standard template with centered content and comfortable reading width. Use for most pages.

### Full Width

Content expands to fill available space. Use for:
- Image galleries
- Wide tables or data displays
- Landing pages with full-width blocks

### Sidebar (Left) / Sidebar (Right)

Two-column layout with a 320px sidebar. The sidebar is sticky and scrolls with the page. Use for:
- Blog index pages
- Category archives
- Pages needing navigation aids

### Documentation

Optimized for long-form technical content:
- Narrower sidebar (256px) for TOC
- Prose typography styling
- Scroll margin on headings for anchor links
- Defaults to Table of Contents widget

Use for:
- Documentation pages
- Tutorials
- Reference guides

### Landing

Hides the site header navigation and footer for a focused presentation. Use for:
- Marketing landing pages
- Sales pages
- Signup flows
- Single-purpose pages

---

## Mobile Behavior

On screens smaller than 1024px:
- Sidebars stack above the main content
- Widgets display in a single column
- Full navigation remains accessible

---

## Theme Customization

Themes can override templates or add new ones.

### Overriding a Template

Create a file at:
```
resources/views/vendor/tallcms/templates/{template-name}.blade.php
```

### Adding Theme Templates

Themes can register additional templates in `theme.json`:

```json
{
  "templates": {
    "portfolio": {
      "label": "Portfolio",
      "description": "Grid layout for portfolio items",
      "has_sidebar": false
    }
  }
}
```

Create the template at:
```
themes/{theme}/resources/views/templates/portfolio.blade.php
```

---

## Common Pitfalls

**"Sidebar widgets don't appear"**
Check that you selected a sidebar template (Sidebar Left, Sidebar Right, or Documentation).

**"Table of Contents is empty"**
The TOC only includes headings with `h2`, `h3`, or `h4` tags. Ensure your content has headings.

**"Custom HTML widget not available"**
This widget is restricted to administrators for security. Contact your site admin.

**"Landing page still shows navigation"**
Clear your browser cache and ensure the page is saved with the Landing template.

---

## Next Steps

- [Using content blocks](blocks)
- [SEO settings](seo)
- [Theme development](dev-themes)
