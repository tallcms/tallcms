---
title: "Redirect Manager"
slug: "redirects"
audience: "all"
category: "reference"
order: 56
time: 10
prerequisites:
  - "installation"
  - "plugins"
---

# Redirect Manager

> **What you'll learn:** How to create and manage URL redirects, choose the right status codes, and monitor redirect usage.

The Redirect Manager plugin lets you create and manage URL redirects from the admin panel. Use it when you rename pages, restructure your site, migrate from another platform, or need to fix broken incoming links.

## Installation

The Redirect Manager is a free plugin. Install it via **Admin > System > Plugins** (ZIP upload) or copy it into `plugins/tallcms/redirect-manager/`.

```bash
php artisan cache:clear
php artisan migrate
```

Source: [github.com/tallcms/redirect-manager-plugin](https://github.com/tallcms/redirect-manager-plugin)

## Creating a Redirect

1. Navigate to **Admin > Configuration > Redirects**
2. Click **New Redirect**
3. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Source Path** | The old URL path visitors hit | `/old-blog-post` |
| **Destination URL** | Where to send them | `/new-blog-post` |
| **Status Code** | 301 (permanent) or 302 (temporary) | 301 |
| **Active** | Enable or disable without deleting | On |
| **Note** | Optional reminder for yourself | "Renamed in April 2026" |

4. Click **Create**

The redirect takes effect immediately — the cache is auto-invalidated.

## When to Use 301 vs 302

| Status | Meaning | Use When |
|--------|---------|----------|
| **301 Permanent** | The page has moved forever | Renamed pages, site restructures, domain migrations |
| **302 Temporary** | The page is temporarily elsewhere | Maintenance, A/B tests, seasonal content |

**For SEO, prefer 301.** Search engines transfer link equity (ranking power) from the old URL to the new one. A 302 tells search engines to keep indexing the old URL.

## Path Matching Rules

Understanding how paths are matched prevents surprises:

**Exact match only.** `/old-page` matches requests to `/old-page` but not `/old-page/subpage` or `/old-page-extra`.

**Trailing slashes are normalized.** `/old-page/` and `/old-page` are treated as the same path. You cannot create separate redirects for both — the plugin normalizes them automatically.

**Query strings are ignored.** A redirect on `/old-page` matches requests to `/old-page?utm_source=email&ref=newsletter`. The query string is not forwarded to the destination.

**Case-sensitive.** `/About-Us` and `/about-us` are different paths. Create separate redirects if both are in use.

**GET and HEAD requests only.** Form submissions (POST), API calls (PUT/DELETE), and other non-GET methods are never redirected.

## Redirecting to External URLs

The destination can be a full URL, not just a path:

| Source Path | Destination URL | Result |
|-------------|----------------|--------|
| `/old-page` | `/new-page` | Redirects within your site |
| `/partner` | `https://partner-site.com/landing` | Redirects to external site |

## Monitoring Redirects

Each redirect tracks:

- **Hit Count** — how many times it has been triggered
- **Last Hit** — when it was last triggered

Use these to identify which redirects are still active and which can be cleaned up. A redirect with zero hits after several months is probably safe to delete.

## Bulk Operations

Select multiple redirects using the checkboxes and use bulk actions:

- **Activate** — enable selected redirects
- **Deactivate** — disable without deleting (preserves hit history)
- **Delete** — permanently remove

## Deactivating vs Deleting

**Deactivate** when you might re-enable later — it preserves the redirect configuration and hit history. The source path passes through to normal routing as if the redirect doesn't exist.

**Delete** when you're sure the redirect is no longer needed. This is permanent.

## Loop Prevention

The plugin prevents infinite redirect loops:

- **At creation time** — you cannot create a redirect where the source and destination resolve to the same path (e.g., `/old` → `/old`, or `/old` → `/old/`, or `/old` → `https://yoursite.com/old`)
- **At runtime** — even if a self-redirect somehow exists in the database, the middleware detects it and passes the request through normally instead of looping

This protection works across path variants (trailing slashes) and same-host absolute URLs. External destinations (different domain) are always allowed.

## Performance

The plugin loads all active redirects into memory once per hour (cached). Every subsequent request is a fast hash lookup — there's no database query per request.

The cache is automatically invalidated whenever you create, edit, or delete a redirect. You never need to manually clear the cache.

For sites with thousands of redirects, performance remains constant — the lookup is O(1) regardless of how many redirects exist.

## Common Use Cases

### Site Migration

When migrating from another platform (e.g., WordPress), create redirects for all your old URLs:

| Old URL | New URL | Status |
|---------|---------|--------|
| `/2024/01/my-post` | `/blog/my-post` | 301 |
| `/category/news` | `/news` | 301 |
| `/wp-content/uploads/doc.pdf` | `/media/doc.pdf` | 301 |

### Page Renames

When you change a page slug in TallCMS, create a redirect from the old slug so existing links and bookmarks still work:

| Old Path | New Path | Status |
|----------|----------|--------|
| `/services/consulting` | `/services/advisory` | 301 |

### Vanity URLs

Create short, memorable URLs for marketing campaigns:

| Vanity URL | Destination | Status |
|------------|-------------|--------|
| `/spring-sale` | `/promotions/spring-2026-clearance` | 302 |
| `/careers` | `/about/join-our-team` | 301 |

### Fixing Broken Incoming Links

Check your analytics or search console for 404 errors from external sites linking to wrong URLs, then create redirects to the correct pages.

## Limitations

- **No wildcard or pattern matching** — each redirect is an exact path match. To redirect `/blog/2024/*` to `/archive/2024/*`, you need individual redirects for each URL.
- **No query string matching** — you cannot create different redirects for `/search?q=old` vs `/search?q=new`.
- **No regex support** — for complex rewrite rules, use your web server configuration (Nginx/Apache) instead.
- **No redirect chains** — if `/a` → `/b` and `/b` → `/c`, a visitor hitting `/a` will be redirected to `/b`, then the browser follows to `/c` (two hops). The plugin does not collapse chains into a single redirect.

These are intentional constraints for v1. The plugin covers the most common redirect needs. For advanced URL rewriting, use server-level configuration.

---

## Common Pitfalls

**"My redirect isn't working"**
Check that the redirect is **Active** (toggle is on). Also verify the source path starts with `/` and matches the exact URL path — remember, matching is case-sensitive and query strings are ignored.

**"I'm getting an infinite redirect loop"**
The plugin prevents self-redirects, but redirect chains can still cause browser-level loops. If `/a` → `/b` and `/b` → `/a`, the browser will loop. Delete one of the conflicting redirects.

**"The old URL still shows the old page"**
Your browser may have cached the previous response. Clear your browser cache or test in an incognito window. Server-side, the redirect cache is invalidated automatically on every change.

**"I can't create a redirect — it says the path already exists"**
The plugin normalizes paths before checking uniqueness. `/old-page` and `/old-page/` are the same path. Check the existing redirects list for a redirect with the same normalized source.

**"POST requests to my old form URL aren't being redirected"**
This is by design. Only GET and HEAD requests are redirected. Update form actions to point to the new URL directly.

---

## Next Steps

- [SEO features](seo) — meta tags, sitemaps, and structured data
- [Plugin development](plugins) — build your own TallCMS plugins
- [Filament ecosystem](filament-ecosystem) — extend the admin panel with community plugins
