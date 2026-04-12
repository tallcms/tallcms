---
title: "Filament Plugin Ecosystem"
slug: "filament-ecosystem"
audience: "all"
category: "reference"
order: 55
time: 10
prerequisites:
  - "installation"
---

# Filament Plugin Ecosystem

TallCMS is built on [Filament](https://filamentphp.com), a powerful application framework for Laravel. This means TallCMS benefits from **two plugin ecosystems** simultaneously:

1. **TallCMS plugins** — CMS-specific extensions (themes, content blocks, frontend routes)
2. **Filament plugins** — hundreds of community-built admin panel extensions that work out of the box

This is a significant advantage over traditional CMS platforms. When you install a Filament plugin, it integrates directly into your TallCMS admin panel alongside the CMS features — no adapters, no compatibility layers.

## Why This Matters

Most CMS platforms live in a walled garden: you can only use extensions built specifically for that CMS. TallCMS breaks this pattern. Because your admin panel **is** a Filament panel, any Filament plugin that works with a standard Filament app works with TallCMS.

This gives you access to battle-tested, actively maintained packages covering functionality that would take years for a single CMS ecosystem to replicate.

## What Filament Plugins Can Add

The [Filament plugin directory](https://filamentphp.com/plugins) includes hundreds of packages. Here are some categories that pair well with TallCMS:

### Forms & Fields

- **Spatie Media Library** — advanced media management with conversions and collections
- **Tiptap Editor** — alternative rich text editor with extended formatting
- **Phone Input** — international phone number fields with validation
- **Color Picker** — enhanced color selection for theme customization
- **Map Picker** — interactive map fields for location content

### Tables & Data

- **Excel Export/Import** — bulk data operations for content and users
- **Advanced Filter** — complex filtering for pages, posts, and media
- **Table Repeater** — inline table editing in forms

### Admin Experience

- **Spatie Activity Log** — audit trail for content changes
- **Exception Viewer** — monitor application errors from the admin panel
- **Environment Indicator** — visual indicator for staging vs. production
- **Peek Preview** — enhanced content preview capabilities
- **Translatable** — UI for managing multilingual content (complements TallCMS's built-in i18n)

### Authentication & Users

- **Breezy** — enhanced profile management with two-factor authentication
- **Socialite Login** — OAuth login via Google, GitHub, Twitter, etc.
- **Impersonate** — act as another user for support and debugging

### Notifications & Communication

- **FCM Notifications** — push notifications to mobile devices
- **Notification Pro** — advanced notification management and channels

### Developer Tools

- **Settings UI** — auto-generated settings pages from config
- **Fabricator** — page builder components (can complement TallCMS blocks)
- **Curator** — alternative media management with focal-point cropping

## How to Install a Filament Plugin

Most Filament plugins are standard Composer packages:

```bash
composer require vendor/filament-plugin-name
```

Then register the plugin in your panel provider:

```php
// app/Providers/Filament/AdminPanelProvider.php

use Vendor\PluginName\PluginNamePlugin;

return $panel
    ->plugins([
        TallCmsPlugin::make(),
        PluginNamePlugin::make(),  // Add the Filament plugin here
    ]);
```

That's it. The plugin's resources, pages, and widgets appear in your admin panel alongside TallCMS's content management features.

## TallCMS Plugins vs. Filament Plugins

| | TallCMS Plugins | Filament Plugins |
|---|---|---|
| **Install method** | Copy to `plugins/` directory | `composer require` |
| **Scope** | CMS-specific: blocks, themes, frontend routes | Admin panel: resources, fields, widgets, pages |
| **Discovery** | Auto-discovered via `plugin.json` | Registered in panel provider |
| **Frontend** | Can add public routes and themed pages | Admin panel only |
| **Licensing** | TallCMS marketplace (free or paid) | Packagist / vendor sites |
| **Theme overrides** | Views overridable by theme | Standard Filament theming |

The two systems complement each other. Use TallCMS plugins for CMS functionality (content blocks, frontend features, themes) and Filament plugins for admin panel enhancements (form fields, data management, developer tools).

## Example: Combining Both Ecosystems

A typical TallCMS installation might use:

**TallCMS plugins:**
- Multisite — manage multiple sites from one installation
- Registration — frontend user self-registration
- Pro — advanced content blocks and per-site settings

**Filament plugins:**
- Spatie Activity Log — track who edited which page and when
- Excel Export — bulk export posts for migration or backup
- Socialite Login — let users register via Google or GitHub
- Environment Indicator — see at a glance if you're editing staging or production

All of these coexist in the same admin panel, sharing the same authentication, permissions, and UI framework.

## Finding Compatible Plugins

Browse the full catalog at [filamentphp.com/plugins](https://filamentphp.com/plugins). When evaluating a plugin for TallCMS:

1. **Check the Filament version** — TallCMS uses Filament 5. Look for plugins that support v5 (or v4+ with Filament's upgrade compatibility).
2. **Check for model assumptions** — some plugins assume specific model structures. Plugins that work with any Eloquent model are the most compatible.
3. **Check for panel assumptions** — plugins that work with any Filament panel integrate seamlessly. Plugins that assume a specific panel ID may need minor configuration.

Most well-maintained Filament plugins follow these conventions and work without modification.
