---
title: "Multisite Management"
slug: "multisite"
audience: "site-owner"
category: "site-management"
order: 70
time: 15
prerequisites:
  - "installation"
  - "site-settings"
---

# Multisite Management

> **What you'll learn:** How to manage multiple websites from a single TallCMS installation, each with its own domain, theme, and settings.

**Time:** ~15 minutes

---

## Overview

The TallCMS Multisite plugin lets you run multiple distinct websites from one TallCMS installation. Each site gets its own:

- **Domain** (e.g., `blog.example.com`, `shop.example.com`)
- **Theme** with independent preset selection
- **Site settings** (name, tagline, contact info, branding, maintenance mode)
- **Pages and menus** scoped to that site

Posts and categories remain shared across all sites.

---

## Requirements

- TallCMS 3.6 or later
- The **TallCMS Multisite** plugin installed and activated with a valid license
- Each site domain must point to the same server (via DNS or local hosts file)

---

## 1. Activate the Plugin

1. Install the multisite plugin via **Admin > Plugins**
2. Navigate to **Admin > Plugin Licenses**
3. Enter your license key for **TallCMS Multisite** and click **Activate**
4. Run `php artisan migrate` to create the multisite tables

A default site is created automatically from your current domain and settings.

---

## 2. Create a Site

1. Navigate to **Admin > Multisite > Sites**
2. Click **Create**
3. Fill in:
   - **Name** — Display name for this site
   - **Domain** — The domain this site responds to (e.g., `shop.example.com`). Lowercase, no protocol or port.
   - **Locale** — Optional language override (or leave empty for the global locale)
   - **Active** — Toggle to enable/disable the site
   - **Default Site** — Only one site can be the default (used as fallback in admin)
4. Click **Create**

---

## 3. Switch Between Sites in the Admin

The **site switcher** appears at the top of the admin sidebar. Select a site to manage its content:

- **Pages** and **menus** filter to show only the selected site's content
- **Site Settings** reads and writes settings for the selected site
- **Theme Manager** shows and manages the selected site's theme
- **All Sites** mode shows content across all sites (create actions are disabled)

---

## 4. Assign a Theme to a Site

1. Select a site in the **site switcher**
2. Navigate to **Admin > Appearance > Themes**
3. The subheading shows which site you're managing (e.g., "Managing theme for: Shop (shop.example.com)")
4. Click **Activate** on any available theme
5. Optionally select a default **preset** for the site

Each site can use a different theme. The global theme (shown in **All Sites** mode) is the default for sites without an explicit theme.

---

## 5. Configure Per-Site Settings

1. Select a site in the **site switcher**
2. Navigate to **Admin > Settings > Site Settings**
3. The page shows which site you're editing (e.g., "Editing settings for: Shop (shop.example.com)")
4. Each field shows its current state:
   - **Site override** (blue pencil icon) — This site has a custom value
   - **Inherited from global** (gray globe icon) — Using the global default
5. Change the settings you want to customize for this site
6. Click **Save**

Only fields you actually change create per-site overrides. Untouched fields continue to inherit from the global defaults.

### Resetting a Setting to Global

To remove a per-site override and go back to the global default, click the **Reset to global** button (arrow icon) next to any overridden field. This deletes the override — it does not set the value to empty.

### Global-Only Settings

Some settings are always global and cannot be overridden per site:
- **Language settings** (i18n) — These affect URL routing which is installation-wide
- These fields appear locked with a "Global setting" label when a site is selected

### Three Override States

| State | Meaning |
|-------|---------|
| No override | Setting inherits the global default |
| Override with a value | Site has its own custom value |
| Override with empty value | Site explicitly wants this field blank (e.g., no contact phone) |

Clearing a field and saving is **not** the same as resetting to global. Clearing stores an explicit blank; resetting deletes the override.

---

## How Domain Resolution Works

When a visitor arrives at your server:

1. TallCMS looks up the request domain in the sites table
2. If a match is found, that site's theme, settings, and content are loaded
3. If no match is found, the visitor sees a 404 page

**Each domain maps to exactly one site.** Domain aliases (e.g., `www.example.com` and `example.com`) are not supported in v1 — register the canonical domain only.

---

## Content Scoping

| Content Type | Scoped Per-Site? | Notes |
|-------------|-----------------|-------|
| **Pages** | Yes | Each site has its own pages and homepage |
| **Menus** | Yes | Each site has its own navigation (same location names are allowed) |
| **Posts** | No | Posts are shared across all sites |
| **Categories** | No | Categories are shared across all sites |
| **Media** | No | Media library is shared |

---

## Common Pitfalls

**"404 when visiting my new site's domain"**
The domain must be configured in your DNS or local hosts file to point to the same server as your TallCMS installation. The domain must also be added to a site in **Admin > Multisite > Sites**.

**"Theme doesn't change on the frontend"**
Make sure you selected the correct site in the **site switcher** before activating the theme. Check the subheading on the Theme Manager page to confirm which site you're managing.

**"Pages from another site appear in my site"**
Pages without a site assignment may be orphaned. Navigate to **All Sites** mode and check for pages with no site. Edit them to assign the correct site.

**"Can't create pages in All Sites mode"**
This is intentional. Select a specific site in the switcher before creating pages or menus.

**"Settings I didn't change show as overrides"**
Only settings you actually modify are saved as overrides. If you see unexpected overrides, check the override indicators on the Site Settings page and use **Reset to global** to clear them.

**"Language settings won't change per site"**
Language (i18n) settings are global — they affect URL routing which is installation-wide. Per-site locale is set in the site's configuration (locale field), not in Site Settings.

---

## Next Steps

- [Theme development](themes) — Create custom themes for your sites
- [Plugin development](plugins) — Build plugins that work across all sites
- [Site settings](site-settings) — Detailed settings reference
