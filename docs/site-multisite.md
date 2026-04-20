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

> **What you'll learn:** How to manage multiple websites from a single TallCMS installation, each with its own domain, theme, content, and settings.

**Time:** ~15 minutes

---

## Overview

The TallCMS Multisite plugin lets you run multiple distinct websites from one TallCMS installation. Each site gets its own:

- **Domain** (e.g., `blog.example.com`, `shop.example.com`)
- **Theme** with independent preset selection
- **Site settings** (name, tagline, contact info, branding, maintenance mode)
- **Pages and menus** scoped to that site

Posts and categories are user-owned and reusable across sites (surfaced through content blocks).

---

## Requirements

- TallCMS 4.0.0 or later
- The **TallCMS Multisite** plugin installed and activated with a valid license
- Each site domain must point to the same server (via DNS A/AAAA record or CNAME)

---

## 1. Activate the Plugin

1. Install the multisite plugin via **Admin > Plugins**
2. Navigate to **Admin > Plugin Licenses**
3. Enter your license key for **TallCMS Multisite** and click **Activate**
4. Run `php artisan migrate` to create the multisite tables

A default site is created automatically from your current domain.

---

## 2. Create a Site

1. Navigate to **Admin > Sites > Sites**
2. Click **Create**
3. Fill in:
   - **Name** — Display name for this site
   - **Domain** — The domain this site responds to (e.g., `shop.example.com`). Lowercase, no protocol or port.
   - **Locale** — Optional language override (or leave empty for the global locale)
   - **Active** — Toggle to enable/disable the site
4. Click **Create**

After creating the site, you'll need to [verify the domain](#6-verify-a-custom-domain) before TLS certificates are issued.

---

## 3. Manage Site Content

In multisite mode, **Pages** and **Menus** are accessed through the Site resource:

1. Navigate to **Admin > Sites > Sites**
2. Click **Edit** on the site you want to manage
3. Use the **Pages** and **Menus** relation tabs to view, create, and edit content for that site

The **Filter by Site** dropdown in the admin sidebar lets you filter content lists by site for quick access.

---

## 4. Edit Site Settings

Each site has its own settings, managed directly on the site's edit page:

1. Navigate to **Admin > Sites > Sites**
2. Click **Edit** on the site
3. Use the settings tabs (General, Branding, Contact, Social, Publishing, Maintenance) to configure the site

Settings inherit from **Global Defaults** (Admin > Configuration > Global Defaults) unless overridden. When you save a site, only values that differ from the global defaults are stored as overrides. If you change a value back to match the global default, the override is automatically removed and the site resumes inheriting.

---

## 5. Assign a Theme to a Site

1. Select a site in the **Filter by Site** dropdown
2. Navigate to **Admin > Appearance > Themes**
3. The subheading shows which site you're managing
4. Click **Activate** on any available theme
5. Optionally select a default **preset** for the site

Each site can use a different theme. The global theme is the default for sites without an explicit theme.

---

## 6. Multisite Settings

Navigate to **Admin > Configuration > Multisite Settings** to configure installation-wide multisite options.

### Default Site

Select which site serves as the fallback for the admin panel and local development. Only one site can be the default.

### Domain Verification

Configure how custom domains are verified:

- **Server IP Addresses** — Enter the IP addresses your server resolves to (one per line). Supports both IPv4 and IPv6.
- **CNAME Target** — The domain users should point a CNAME record to (e.g., `sites.yoursaas.com`).

Set at least one for domain verification to work.

### Re-verification

Verified domains are periodically re-checked to detect DNS changes:

- **Re-verify Every (Days)** — Minimum 7 days. Set to 0 to disable.
- **Batch Size** — Maximum domains checked per hourly run (default 50, max 500).

Re-verification runs hourly in small batches. Failed re-checks enter a **Stale** grace period; a second consecutive failure escalates to **Failed** and revokes TLS eligibility.

---

## 7. Verify a Custom Domain

Custom domains must be verified before TLS certificates are issued. Managed subdomains (e.g., `*.yoursaas.com`) are auto-trusted and skip this step.

1. Navigate to the site's edit page (**Admin > Sites > Sites > Edit**)
2. The **Status** tab shows DNS setup instructions
3. Add the appropriate DNS record at your domain registrar:
   - **CNAME record** pointing to the configured target domain, or
   - **A/AAAA record** pointing to the configured server IP
4. Click the **Verify Domain** button in the page header
5. If DNS is configured correctly, the status changes to **Verified** and TLS provisioning is triggered

### Verification Statuses

| Status | Meaning |
|--------|---------|
| **Pending** | Domain added but not yet verified |
| **Verified** | DNS confirmed, TLS eligible |
| **Stale** | Re-verification failed once (grace period) |
| **Failed** | Two consecutive re-verification failures, TLS revoked |

---

## What's Per-Site vs Global

### Content

| Feature | Scope | Notes |
|---------|-------|-------|
| **Pages** | Per-site | Each site has its own pages with independent slugs and homepage |
| **Menus** | Per-site | Each site has its own navigation (same location names allowed per site) |
| **Posts** | User-owned | Shared library; surfaced on sites through content blocks |
| **Categories** | User-owned | Shared taxonomy used by posts |
| **Media library** | User-owned | Shared uploads; surfaced on sites through media blocks |

### Settings

| Setting Group | Scope | Notes |
|---------------|-------|-------|
| **Site name, tagline, description** | Per-site | Each site has its own identity |
| **Contact info** (email, phone, address) | Per-site | Each site can have different contact details |
| **Social media links** | Per-site | Each site can link to different profiles |
| **Branding** (logo, favicon) | Per-site | Each site can have its own logo and favicon |
| **Maintenance mode** | Per-site | Put one site in maintenance without affecting others |
| **Publishing workflow** | Per-site | Enable/disable review workflow per site |
| **"Powered by" badge** | Per-site | Show or hide per site |
| **SEO** (RSS, sitemap) | Global | Installation-wide feed/index settings |
| **Embed code** (head, body) | Global | Installation-wide code injection |
| **Language settings** (i18n) | Global | URL routing is installation-wide |

### System

| Feature | Scope | Notes |
|---------|-------|-------|
| **Plugins** | Global | All installed plugins available to all sites |
| **Plugin licenses** | Global | One license covers the installation |
| **User accounts** | Global | Admins manage all sites from one login |
| **Roles & permissions** | Global | Permission system applies installation-wide |

---

## Navigation in Multisite Mode

When multisite is active, the admin navigation adapts:

- **Pages** and **Menus** are removed from top-level navigation — access them through each site's edit page
- **Filter by Site** dropdown in the sidebar filters content views
- **Sites** resource appears under the Sites navigation group
- **Global Defaults** and **Site Settings** are separate pages under Configuration

This makes content ownership explicit: you always know which site you're editing.

---

## Site Ownership

Each site has an **owner** — the user who created it. Ownership controls visibility:

- **Super-admins** see all sites and can assign ownership to any user
- **Regular users** see only their own sites
- When you create a site, you automatically become its owner
- The "All Sites" view is only available to super-admins

---

## Site Plans & Quotas

The multisite plugin includes a plan/tier system to control how many sites each user can create.

### Managing Plans

1. Navigate to **Admin > Sites > Site Plans**
2. Create plans with a name, slug, and max sites (leave empty for unlimited)
3. Toggle **Default Plan** to set which plan new users receive automatically

### Over-Quota Behavior

If you downgrade a user's plan and they already have more sites than the new limit:

- **Existing sites are preserved** — no sites are deleted
- **New site creation is blocked** until they reduce their site count
- The user sees a quota warning on their sites list

Super-admins are never quota-limited.

---

## Common Pitfalls

**"404 when visiting my new site's domain"**
The domain must be configured in DNS to point to your server. The domain must also be added to a site and the site must be active.

**"Verify Domain says 'not configured'"**
Go to **Admin > Configuration > Multisite Settings** and enter your server IP or CNAME target.

**"Domain shows as Stale or Failed"**
The domain's DNS records no longer point to the expected target. Update DNS and click **Verify Domain** to re-check.

**"Theme doesn't change on the frontend"**
Select the correct site in the **Filter by Site** dropdown before activating the theme. Check the subheading on the Theme Manager page.

**"Changes to Global Defaults not showing on a site"**
The site has an override for that setting. Edit the site and change the value back to match the global default — the override will be removed and the site will inherit again.

**"Authors can't publish pages"**
Check the **Publishing** tab on the site's settings. If the Review Workflow toggle is on, authors can only save drafts. Turn it off to let all users publish directly.

**"Can't find Pages or Menus in the navigation"**
In multisite mode, Pages and Menus are accessed through the Site resource. Navigate to **Admin > Sites > Sites**, edit a site, then use the Pages and Menus tabs.

---

## Next Steps

- [Theme development](themes) — Create custom themes for your sites
- [Plugin development](plugins) — Build plugins compatible with multisite
- [Site settings](site-settings) — Detailed settings reference
