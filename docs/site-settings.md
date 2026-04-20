---
title: "Site Settings"
slug: "site-settings"
audience: "site-owner"
category: "site-management"
order: 60
---

# Site Settings

Configuring your site's identity, branding, contact details, and operational settings.

---

## Overview

TallCMS organizes settings into two levels:

| Level | Page | Purpose |
|-------|------|---------|
| **Site Settings** | Admin > Configuration > Site Settings | Settings for this specific site (identity, branding, contact, social, publishing, maintenance) |
| **Global Defaults** | Admin > Configuration > Global Defaults | Base values that all sites inherit unless overridden |

In **standalone mode** (single site), Site Settings is the primary configuration page. Global Defaults provides the inherited base values.

In **multisite mode**, each site has its own settings on its edit page. Global Defaults sets the baseline that new sites start with.

---

## Site Settings Page

Navigate to **Admin > Configuration > Site Settings** to edit your site's configuration. Settings are organized into tabs:

### General

- **Site Name** — The public brand name shown in browser tabs and throughout the site
- **Tagline** — Short phrase that describes your site
- **Description** — Used as fallback meta description
- **Site Type** — Multi-Page Website or Single-Page Application (SPA)
- **Domain** — The domain this site is served on (read-only in standalone)
- **Theme** — Visual theme for this site
- **Locale** — Primary language for this site

### Branding

- **Site Logo** — Displays in header navigation and footer. Supports PNG, JPG, SVG.
- **Favicon** — Browser tab icon. Supports .ico and .png (16x16 or 32x32).
- **"Powered by TallCMS" Badge** — Toggle the footer badge on or off.

### Contact

Contact details used in merge tags and contact forms:

| Setting | Merge Tag |
|---------|-----------|
| Contact Email | `{{contact_email}}` |
| Company Name | `{{company_name}}` |
| Company Address | `{{company_address}}` |
| Company Phone | `{{company_phone}}` |

### Social

Configure social media links. Social icons automatically appear in the footer when URLs are configured.

| Platform | Setting Key | Merge Tag |
|----------|-------------|-----------|
| Facebook | `social_facebook` | `{{social_facebook}}` |
| Twitter/X | `social_twitter` | `{{social_twitter}}` |
| Instagram | `social_instagram` | `{{social_instagram}}` |
| LinkedIn | `social_linkedin` | `{{social_linkedin}}` |
| YouTube | `social_youtube` | `{{social_youtube}}` |
| TikTok | `social_tiktok` | `{{social_tiktok}}` |
| Newsletter | `newsletter_signup_url` | `{{newsletter_signup}}` |

### Publishing

- **Review Workflow** — When enabled, authors must submit content for review before publishing. When disabled, all users with create permission can publish directly.

### Maintenance

- **Maintenance Mode** — Toggle on/off. Visitors see a 503 page; admins can still access the panel.
- **Maintenance Message** — Custom message shown to visitors during maintenance.

Bypass routes: Admin panel (`/admin/*`) and installer (`/install/*`).

---

## Global Defaults Page

Navigate to **Admin > Configuration > Global Defaults** to set the base values that all sites inherit.

Global Defaults has the same tabs as Site Settings (General, Branding, Contact, Social, Publishing, Maintenance) plus a **Languages** tab for i18n configuration.

### Languages (i18n)

- **Enable Multilingual Support** — When enabled, content can be translated into multiple languages.
- **Default Language** — The primary language for your site.
- **Hide Default Language in URLs** — Default language accessed at `/` instead of `/en/`.

Language settings are installation-wide and apply to all sites.

### How Inheritance Works

When a site has no override for a setting, it inherits the Global Default value. When you change a Global Default, all sites without an override for that setting automatically get the new value.

| Scenario | Result |
|----------|--------|
| Site field matches global default, save | No override created — site inherits |
| Site field differs from global default, save | Override created — site has its own value |
| Site field changed back to match global, save | Override removed — site inherits again |

---

## Site Type: Multi-Page vs SPA Mode

TallCMS offers two site operation modes:

| Mode | Description | Best For |
|------|-------------|----------|
| **Multi-Page** | Traditional website with separate URLs for each page | Blogs, large sites, SEO-focused sites |
| **Single-Page (SPA)** | All content on one page with smooth scroll navigation | Landing pages, portfolios, small business sites |

### How SPA Mode Works

When SPA mode is enabled:

1. **Homepage becomes a single scroll page** — All published pages render as sections with anchor IDs
2. **Menu links auto-convert** — Page links become anchor links (e.g., `#about-5`)
3. **Block CTA buttons auto-convert** — Hero and CTA buttons scroll instead of navigate
4. **Smooth scrolling** — Enabled automatically via CSS

SPA mode works at the core level, not theme level. No theme modifications required.

---

## Common Pitfalls

**"SPA mode not working"**
Clear the browser cache. Ensure you saved the settings after changing site type.

**"Logo not showing"**
Upload a logo in Site Settings > Branding tab. Check the image path is accessible. To delete a logo, click the delete button on the file upload and save.

**"Social icons not appearing"**
Only platforms with configured URLs display icons. Enter full URLs including `https://`.

**"Maintenance mode not activating"**
Save the settings after enabling. Clear the config cache if needed.

**"Changes to Global Defaults not showing on my site"**
If your site has an override for that setting, it won't inherit global changes. Check the site's settings — if a value was explicitly set, it takes precedence over the global default.

---

## Next Steps

- [SEO settings](seo)
- [Menu management](menus)
- [Multisite management](multisite) — Per-site settings in multisite mode
