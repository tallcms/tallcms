---
title: "Menu Management"
slug: "menus"
audience: "site-owner"
category: "site-management"
order: 50
---

# Menu Management

TallCMS includes a flexible menu system for managing site navigation. Create menus for different locations (header, footer, sidebar, mobile) with support for nested items, page links, external URLs, and multi-language labels.

## Features

- **Multiple Locations** - Header, footer, sidebar, mobile, or custom locations
- **Nested Items** - Up to 5 levels of hierarchy with drag-and-drop reordering
- **Item Types** - Page links, external URLs, custom paths, headers, separators
- **Active State** - Automatic highlighting of current page in navigation
- **SPA Mode** - Automatic anchor link conversion for single-page sites
- **Translations** - Multi-language menu labels

## Admin Interface

### Creating Menus

Navigate to **Content > Menus** in the admin panel.

1. Click **New Menu**
2. Fill in the form:
   - **Name** - Internal identifier (e.g., "Main Navigation")
   - **Location** - Where the menu appears (header, footer, sidebar, mobile)
   - **Description** - Optional notes for administrators
   - **Active** - Enable/disable the menu
3. Click **Create**

### Menu Locations

| Location | Purpose | Recommended Style |
|----------|---------|-------------------|
| `header` | Main site navigation | `horizontal` |
| `footer` | Footer links | `footer` or `footer-vertical` |
| `sidebar` | Sidebar navigation | `sidebar` |
| `mobile` | Mobile menu (falls back to header) | `mobile` |

### Managing Menu Items

Click **Manage Items** on any menu to open the item manager.

#### Adding Items

1. Click **New Item**
2. Configure the item:
   - **Label** - Display text (translatable)
   - **Type** - Item type (see below)
   - **Page** - Select a CMS page (for page type)
   - **URL** - Enter URL (for external/custom types)
   - **Active** - Enable/disable item

#### Item Types

| Type | Description | URL Source |
|------|-------------|------------|
| **Page** | Link to a CMS page | Auto-generated from page slug |
| **External** | Link to external website | Full URL (https://...) |
| **Custom** | Custom URL or path | Relative path (/contact) or anchor (#section) |
| **Header** | Section heading (no link) | None |
| **Separator** | Visual divider | None |

#### Reordering Items

- **Drag and drop** items to reorder
- **Nest items** by dragging onto a parent item
- Maximum nesting depth: 5 levels

### Preview

Click **Preview** on a menu to see its structure in a modal, including:
- Menu metadata (name, location, status)
- Visual tree of all items
- Linked page names
- Active/inactive status indicators

---

## Menu Item Properties

Each menu item has these configurable properties:

| Property | Description |
|----------|-------------|
| **Label** | Display text for the menu item |
| **Type** | Link type (page, external, custom, header, separator) |
| **URL/Page** | Target URL or linked page |
| **Target** | `_self` (same tab) or `_blank` (new tab) |
| **Icon** | Optional Heroicon class |
| **CSS Class** | Custom CSS classes |
| **Active** | Enable/disable the item |

---

## Active State

The menu system automatically detects and highlights active items:

- Current page link receives `text-primary-600` color
- Parent items with active children are also highlighted
- All active links include `aria-current="page"` for accessibility

---

## SPA Mode Integration

When **Single-Page Mode** is enabled in Site Settings, menu links automatically convert to anchor links.

| Regular Mode | SPA Mode |
|--------------|----------|
| `/about` | `#about-5` |
| `/services` | `#services-12` |
| `/contact` | `#contact-8` |

The anchor format is `{slug}-{page_id}` to avoid collisions.

### Custom Anchors

For custom menu items, you can manually set anchors:
- **Type**: Custom
- **URL**: `#my-section`

These are preserved as-is in both modes.

---

## Multi-Language Support

Menu labels support translations when internationalization is enabled.

### Editing Translations

1. Open **Manage Items** for a menu
2. Use the **locale switcher** in the header
3. Edit labels in each language
4. Labels fall back to default language if not translated

---

## Best Practices

### Menu Organization

1. **One menu per location** - Avoid multiple menus for the same location
2. **Limit nesting** - Keep hierarchy to 2-3 levels for usability
3. **Use headers** - Group related items with header type
4. **Mobile consideration** - Test nested menus on mobile devices

### Performance

1. **Limit items** - Large menus impact page load
2. **Cache menus** in production (automatic via Laravel's query cache)

### Accessibility

1. **Descriptive labels** - Use clear, concise text
2. **Keyboard navigation** - Built-in styles support keyboard nav
3. **ARIA attributes** - `aria-current="page"` added automatically

---

## Troubleshooting

### Menu Not Appearing

1. Verify menu is set to **Active**
2. Check **location** matches your theme's expected location
3. Ensure menu has **active items**
4. Clear view cache: `php artisan view:clear`

### Items Not Showing

1. Check items are set to **Active**
2. For page links, verify the page is **Published**
3. Check parent items are active (inactive parents hide children)

### Wrong URL Generated

1. For page links, check page slug in admin
2. For custom links, ensure URL starts with `/` or `http`
3. In SPA mode, verify page has correct ID

### Active State Not Working

1. Ensure URLs match exactly (trailing slashes matter)
2. Check for query string differences
3. For SPA mode, verify anchor format

---

## Next Steps

- [Site settings](site-settings) - SPA mode configuration
- [Theme development](themes) - Custom menu styles
- [Internationalization](i18n) - Multi-language setup
