---
title: "Mega Menu Plugin"
slug: "mega-menu"
audience: "site-owner"
category: "site-management"
order: 35
time: 15
prerequisites:
  - "quick-menus"
---

# Mega Menu Plugin

> **What you'll learn:** How to create professional mega menus with advanced features like badges, CTAs, visibility rules, and multiple header templates.

**Time:** ~15 minutes

---

## Overview

The Mega Menu plugin transforms your standard navigation into a feature-rich header with:

- **Header Templates** - 4 layout options (default, centered-logo, split-menu, hamburger-only)
- **Mega Menu Dropdowns** - Multi-column dropdowns with categories
- **Badges & CTAs** - Highlight items with badges and call-to-action buttons
- **Visibility Rules** - Show/hide items based on user role or login status
- **Top Bar** - Announcement bar above the header
- **Sticky Behavior** - Always visible, smart hide, or shrink on scroll
- **Mobile Drawer** - Responsive mobile menu with theme switcher
- **Theme Switcher** - Let visitors choose their preferred color scheme

---

## Installation

The Mega Menu plugin is installed in `plugins/tallcms/mega-menu`. Verify it's active:

```bash
php artisan plugin:list
```

Run migrations if needed:

```bash
php artisan plugin:migrate tallcms/mega-menu
```

---

## Quick Start

### 1. Open Menu Settings

Navigate to **Admin > Mega Menu > Menu Settings**.

### 2. Select a Menu

Choose the menu you want to enhance (typically "header" location).

### 3. Choose a Template

Select your preferred header layout:

| Template | Description |
|----------|-------------|
| **Default** | Logo left, menu items right |
| **Centered Logo** | Logo centered, menu split on both sides |
| **Split Menu** | Menu items balanced around centered logo |
| **Hamburger Only** | Logo centered, hamburger menu always visible |

### 4. Configure Settings

Adjust colors, fonts, sticky behavior, and enable features like search or top bar.

### 5. Save and Preview

Click **Save** and visit your frontend to see the mega menu in action.

---

## Header Templates

### Default

Classic layout with logo on the left and navigation on the right. Best for most websites.

### Centered Logo

Logo takes center stage with menu items split on both sides. Great for brand-focused sites.

### Split Menu

Similar to centered logo but with more equal distribution of menu items. Works well with many navigation items.

### Hamburger Only

Minimalist design showing only the logo and hamburger button. All navigation moves to the mobile drawer. Ideal for image-heavy or portfolio sites.

---

## Menu Item Enhancements

Navigate to **Admin > Mega Menu > Mega Menu Builder** to enhance individual menu items.

### Badges

Add attention-grabbing labels to menu items:

1. Select a menu item
2. Enable **Show Badge**
3. Enter **Badge Text** (e.g., "New", "Sale", "Beta")
4. Choose **Badge Color**

### CTA Buttons

Turn menu items into prominent call-to-action buttons:

1. Select a menu item
2. Enable **CTA Button**
3. Set **Background Color** and **Text Color**

CTA buttons stand out from regular menu items and are perfect for "Sign Up", "Get Started", or "Contact" links.

### Descriptions

Add descriptive text below menu item labels:

1. Select a menu item
2. Enter **Description** text

Descriptions appear in dropdown menus and help users understand what each link offers.

### Mega Menu Mode

Create multi-column dropdown menus:

1. Select a parent menu item (one with children)
2. Enable **Mega Menu**
3. Configure **Columns** (auto-detected from child structure)

Child items with their own children become column headers.

---

## Visibility Rules

Control who sees each menu item:

| Rule | Shows To |
|------|----------|
| **Everyone** | All visitors |
| **Guests Only** | Logged-out users |
| **Authenticated Only** | Logged-in users |
| **Specific Roles** | Users with selected roles |

### Example: Members-Only Link

1. Select a menu item
2. Set **Visibility** to "Authenticated Only"
3. The item only appears for logged-in users

### Example: Role-Based Access

1. Select a menu item
2. Set **Visibility** to "Specific Roles"
3. Select roles (e.g., "Premium Member", "Admin")
4. Only users with those roles see the item

---

## Top Bar (Announcement Bar)

Add a banner above your header for announcements, promotions, or contact info.

### Enable Top Bar

1. Go to **Menu Settings**
2. Enable **Top Bar**
3. Configure:
   - **Background Color**
   - **Text Color**
   - **Left Content** (HTML allowed)
   - **Right Content** (HTML allowed)
   - **Dismissible** (allow users to close it)

### Example Content

**Left Content:**
```html
Free shipping on orders over $50! <a href="/shipping">Learn more</a>
```

**Right Content:**
```html
Call us: <a href="tel:+1234567890">(123) 456-7890</a>
```

---

## Sticky Behavior

Control how the header behaves on scroll:

| Option | Behavior |
|--------|----------|
| **None** | Header scrolls with page |
| **Always** | Header stays fixed at top |
| **Smart** | Header hides on scroll down, shows on scroll up |
| **Shrink** | Header reduces height when scrolling |

### Sticky Settings

- **Sticky Offset** - Distance from top when sticky (pixels)
- **Sticky Background** - Background color when sticky
- **Sticky Logo Height** - Smaller logo when sticky

---

## Mobile Menu

The mobile menu appears below the configured **Mobile Breakpoint** (default: 768px).

### Mobile Menu Styles

| Style | Description |
|-------|-------------|
| **Drawer Left** | Slides in from the left |
| **Drawer Right** | Slides in from the right |
| **Fullscreen** | Covers entire viewport |

### Mobile Features

- **Show Search** - Include search bar in mobile menu
- **Theme Switcher** - Appears in mobile drawer footer (when theme supports it)

---

## Search Integration

Enable site search in your header:

1. Go to **Menu Settings**
2. Enable **Search**
3. Choose **Search Style**:
   - **Expandable** - Search icon expands to input field
   - **Modal** - Search icon opens a modal dialog

Search uses TallCMS's built-in search functionality.

---

## Theme Switcher

When your theme supports multiple daisyUI presets, the mega menu automatically includes a theme switcher button. Users can:

- Click the palette icon in the header
- Choose from available color themes
- Their preference is saved in the browser

**Note:** Theme switcher visibility is controlled by your theme's configuration, not the mega menu settings. See [Theme Switcher Development](theme-switcher) for details.

---

## Styling Options

### Colors

| Setting | Description |
|---------|-------------|
| **Background Color** | Header background |
| **Text Color** | Default text color |
| **Hover Color** | Color on hover/active |
| **Dropdown Background** | Dropdown panel background |

### Typography

- **Font Family** - Google Font or system font
- **Height** - Header height in pixels
- **Logo Max Height** - Maximum logo height

### Effects

- **Shadow** - None, Small, Medium, Large
- **Border Bottom** - CSS border value (e.g., `1px solid #e5e7eb`)

---

## Render Modes

The mega menu operates in two modes:

### Mode 1: Enhanced Items

Menu items are enhanced with mega menu features (badges, CTAs, visibility) but rendered within your theme's existing header structure.

**Use when:** You want to keep your theme's header design but add mega menu features.

### Mode 2: Full Header

The mega menu takes complete control of the header area, including layout, styling, and all features.

**Use when:** You want the full mega menu experience with templates, top bar, and sticky behavior.

Select your preferred mode in **Menu Settings > Render Mode**.

---

## Common Pitfalls

**"Mega menu not appearing"**
Ensure your theme checks for mega menu. Themes must include:
```blade
@if(mega_menu_header_active('header'))
    <x-mega-menu::header location="header" />
@else
    {{-- Default header --}}
@endif
```

**"Styles look broken"**
Rebuild your theme to include mega menu CSS classes:
```bash
cd themes/your-theme && npm run build
```

**"Mobile menu not working"**
Check **Mobile Breakpoint** setting. Default is 768px. Increase if your menu items need more space.

**"Visibility rules not working"**
Clear Laravel cache after changing visibility:
```bash
php artisan cache:clear
```

**"Theme switcher not showing"**
Theme switcher requires your theme to have multiple daisyUI presets configured. Single-preset themes don't show the switcher.

---

## Next Steps

- [Menu management](menus) - Full menu guide
- [Theme switcher development](theme-switcher) - Enable theme switching
- [Site settings](site-settings) - Configure logo and branding
