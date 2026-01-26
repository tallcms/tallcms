---
title: "Site Settings"
slug: "site-settings"
audience: "site-owner"
category: "site-management"
order: 60
---

# Site Settings & SPA Mode

Configuring site-wide settings and understanding the SPA mode feature.

---

## Overview

TallCMS provides comprehensive site-wide settings accessible from **Admin > Settings > Site Settings**. These settings control branding, social media links, SEO defaults, and how your site operates.

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

All published pages are rendered as sections on the homepage with unique anchor IDs.

### 2. Menu Links Auto-Convert

Menu items pointing to pages automatically become anchor links:
- **Multi-page mode**: `/about` → navigates to separate page
- **SPA mode**: `#about-5` → smooth scrolls to section

### 3. Block CTA Buttons Auto-Convert

CTA buttons in Hero blocks, Call-to-Action blocks, etc. also convert automatically.

### 4. Smooth Scrolling

CSS smooth scrolling is automatically enabled.

---

## Key Differentiators

TallCMS implements SPA mode at the **core level**, not theme level:

- Works with ANY theme automatically - No theme modifications needed
- Consistent behavior regardless of theme
- One-click switching between modes
- Collision-free anchors using page IDs
- Full block compatibility
- SEO-friendly - Posts still render at their own URLs

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

Social icons automatically appear in the footer when URLs are configured.

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

---

## Contact Information

Configure contact details used in merge tags and contact forms:

| Setting | Merge Tag |
|---------|-----------|
| Contact Email | `{{contact_email}}` |
| Company Name | `{{company_name}}` |
| Company Address | `{{company_address}}` |
| Company Phone | `{{company_phone}}` |

---

## Common Pitfalls

**"SPA mode not working"**
Clear the browser cache. Ensure you saved the settings after changing site type.

**"Logo not showing"**
Upload a logo in **Site Settings > Branding**. Check the image path is accessible.

**"Social icons not appearing"**
Only platforms with configured URLs display icons. Enter full URLs including `https://`.

**"Maintenance mode not activating"**
Save the settings after enabling maintenance mode. Clear the config cache if needed.

---

## Next Steps

- [SEO settings](seo)
- [Menu management](menus)
- [Theme development](themes) - For custom site styling
