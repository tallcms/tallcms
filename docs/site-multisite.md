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

- TallCMS 3.10.3 or later
- The **TallCMS Multisite** plugin installed and activated with a valid license
- Each site domain must point to the same server (via DNS A/AAAA record or CNAME)

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
4. Click **Create**

After creating the site, you'll need to [verify the domain](#6-verify-a-custom-domain) before TLS certificates are issued.

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

## 5. Multisite Settings

Navigate to **Admin > Configuration > Multisite Settings** to configure installation-wide multisite options.

### Default Site

Select which site serves as the fallback for the admin panel and local development. Only one site can be the default.

### Domain Verification

Configure how custom domains are verified:

- **Server IP Addresses** — Enter the IP addresses your server resolves to (one per line). Supports both IPv4 and IPv6.
- **CNAME Target** — The domain users should point a CNAME record to (e.g., `sites.yoursaas.com`).

Set at least one for domain verification to work. Users see DNS setup instructions based on what you configure.

### Re-verification

Verified domains are periodically re-checked to detect DNS changes:

- **Re-verify Every (Days)** — Minimum 7 days. Set to 0 to disable.
- **Batch Size** — Maximum domains checked per hourly run (default 50, max 500). Lower values reduce DNS load on large installations.

Re-verification runs hourly in small batches. If a domain fails re-verification, it enters a **Stale** grace period. A second consecutive failure escalates to **Failed**, which revokes TLS eligibility.

---

## 6. Verify a Custom Domain

Custom domains must be verified before TLS certificates are issued. Managed subdomains (e.g., `*.yoursaas.com`) are auto-trusted and skip this step.

1. Navigate to the site's edit page (**Admin > Multisite > Sites > Edit**)
2. The **DNS Setup** section shows instructions for configuring your domain's DNS records
3. Add the appropriate DNS record at your domain registrar:
   - **CNAME record** pointing to the configured target domain, or
   - **A/AAAA record** pointing to the configured server IP
4. Click the **Verify Domain** button in the page header
5. If DNS is configured correctly, the status changes to **Verified** and TLS provisioning is triggered automatically

### Verification Statuses

| Status | Meaning |
|--------|---------|
| **Pending** | Domain added but not yet verified |
| **Verified** | DNS confirmed, TLS eligible |
| **Stale** | Re-verification failed once (grace period) |
| **Failed** | Two consecutive re-verification failures, TLS revoked |

You can click **Verify Domain** again at any time to re-check. Changing a site's domain resets the status to **Pending**.

---

## 7. Configure Per-Site Settings

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
2. If a match is found and the site is active, its theme, settings, and content are loaded
3. If no match is found, the visitor sees a 404 page

**Each domain maps to exactly one site.** Domain aliases (e.g., `www.example.com` and `example.com`) are not supported — register the canonical domain only.

### TLS Certificates

TLS certificates are provisioned automatically when a domain is verified. The reverse proxy (e.g., Caddy) requests a certificate on first HTTPS handshake. A domain is TLS-eligible when:

- It is a **managed subdomain** (`*.yoursaas.com`), which is auto-trusted, or
- It is a **custom domain** with verification status **Verified**

---

## What's Per-Site vs Global

### Content

| Feature | Scope | Notes |
|---------|-------|-------|
| **Pages** | Per-site | Each site has its own pages with independent slugs and homepage |
| **Menus** | Per-site | Each site has its own navigation (same location names like `header` allowed per site) |
| **Posts** | Global | Shared across all sites — a blog post is visible on every site |
| **Categories** | Global | Shared taxonomy used by posts on all sites |
| **Media library** | Global | Uploaded images and files available to all sites |
| **Comments** | Global | Tied to posts, which are global |

### Appearance

| Feature | Scope | Notes |
|---------|-------|-------|
| **Active theme** | Per-site | Each site can use a different theme, managed in Theme Manager |
| **Theme preset** | Per-site | Default daisyUI preset (light, dark, etc.) can differ per site |
| **Installed themes** | Global | All themes on disk are available to all sites |
| **Theme assets** | Global | CSS/JS/images are published once, shared by all sites using that theme |

### Settings

| Setting Group | Scope | Notes |
|---------------|-------|-------|
| **Site name, tagline, description** | Per-site | Each site has its own identity |
| **Contact info** (email, phone, address) | Per-site | Each site can have different contact details |
| **Social media links** | Per-site | Each site can link to different profiles |
| **Branding** (logo, favicon) | Per-site | Each site can have its own logo and favicon |
| **Maintenance mode** | Per-site | Put one site in maintenance without affecting others |
| **SEO** (robots, sitemap, OG image) | Per-site | Each site can have different SEO settings |
| **Code injection** (head, body) | Per-site | Analytics, tracking scripts per site |
| **Language settings** (i18n) | Global | URL routing is installation-wide; per-site locale is set on the site itself |
| **"Powered by" badge** | Per-site | Show or hide per site |

### System

| Feature | Scope | Notes |
|---------|-------|-------|
| **Plugins** | Global | All installed plugins are available to all sites |
| **Plugin licenses** | Global | One license covers the installation |
| **User accounts** | Global | Admins manage all sites from one login |
| **Roles & permissions** | Global | Permission system applies installation-wide |
| **Search index** | Global | Scout indexes content across all sites |
| **API** | Global | REST API serves content from all sites |

---

## Site Ownership

Each site has an **owner** — the user who created it. Ownership controls who can see and manage the site:

- **Super-admins** see all sites and can assign ownership to any user
- **Regular users** see only their own sites
- When you create a site, you automatically become its owner
- The "All Sites" view is only available to super-admins

This means multiple users can work in the same TallCMS installation, each managing their own set of sites without seeing each other's content.

---

## Common Pitfalls

**"404 when visiting my new site's domain"**
The domain must be configured in your DNS to point to the same server as your TallCMS installation (A/AAAA or CNAME record). The domain must also be added to a site in **Admin > Multisite > Sites** and the site must be active.

**"Verify Domain says 'not configured'"**
Go to **Admin > Configuration > Multisite Settings** and enter your server IP address or CNAME target. At least one must be set for verification to work.

**"Domain shows as Stale or Failed"**
The domain's DNS records no longer point to the expected target. Update the DNS records and click **Verify Domain** to re-check. Failed domains lose TLS eligibility until re-verified.

**"TLS certificate not issued after verification"**
TLS provisioning is triggered automatically but runs as a background job. Check that your queue worker is running. The reverse proxy must also be configured to auto-provision certificates (e.g., Caddy with automatic HTTPS).

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
