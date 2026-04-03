---
title: "Multisite Architecture"
slug: "multisite-architecture"
audience: "developer"
category: "developers"
order: 25
prerequisites:
  - "plugins"
  - "themes"
  - "architecture"
---

# Multisite Architecture

> **What you'll learn:** How the multisite plugin works internally, how to build multisite-aware features, and how per-site isolation is implemented.

---

## Overview

The TallCMS Multisite plugin (`plugins/tallcms/multisite/`) enables multiple websites from a single installation. It operates through three mechanisms:

1. **Domain-based site resolution** on the frontend
2. **Session-based site selection** in the admin panel
3. **Global query scopes** that filter content by site

The plugin is a first-party official plugin (`vendor: tallcms`) that requires license activation. All core package changes are plugin-absence-safe.

---

## Database Schema

### `tallcms_sites`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | |
| `name` | string | Display name |
| `domain` | string, unique | Normalized domain (lowercase, no protocol/port) |
| `theme` | string, nullable | Theme slug override |
| `locale` | string, nullable | Locale override |
| `uuid` | uuid, unique | Stable public identifier |
| `user_id` | unsignedBigInteger, nullable | Reserved for future SaaS model |
| `is_default` | boolean | Fallback site for admin |
| `is_active` | boolean | Enable/disable |
| `metadata` | json, nullable | Extensibility |

### `tallcms_site_setting_overrides`

Per-site setting overrides. Same key/value/type structure as `tallcms_site_settings`, scoped by `site_id`.

### Added columns

- `tallcms_pages.site_id` — nullable FK to `tallcms_sites`, `nullOnDelete`
- `tallcms_menus.site_id` — same pattern

Posts and categories are intentionally **not** site-scoped.

---

## Site Resolution

### Frontend (Domain-Based)

`ResolveSiteMiddleware` runs in the `web` middleware group:

```
Request → match domain against tallcms_sites.domain
  → found: load site, override theme/view paths/locale
  → not found: 404 (frontend routes only)
```

The middleware unconditionally normalizes theme/view state per request:
1. Override `Config::set('theme.active')` if site has a theme
2. Reset `ThemeManager` singleton
3. Reset and re-register view paths (including `tallcms` namespace hints)
4. Register plugin view overrides
5. Flush view finder cache
6. Rebind `ThemeInterface` for color/preset resolution

### Admin (Session-Based)

The admin site switcher stores the selected site in `session('multisite_admin_site_id')`.

Two contexts read this:

| Context | How it reads | Why |
|---------|-------------|-----|
| **SiteScope / SiteSetting** | Via `CurrentSiteResolver` singleton | Resolver detects admin via `tallcms.admin_context` request attribute or URL path match |
| **ThemeManager page** | Directly from `session()` + `DB::table()` | Bypasses resolver to avoid singleton timing issues during boot/Livewire lifecycle |

`MarkAdminContext` middleware (added to Filament panel stack by `MultisitePlugin`) sets the `tallcms.admin_context` request attribute.

---

## Query Scoping

### SiteScope (Global Scope)

Applied to `CmsPage` and `TallcmsMenu` in the service provider:

```php
CmsPage::addGlobalScope(new SiteScope());
TallcmsMenu::addGlobalScope(new SiteScope());
```

Behavior:

| Condition | SQL Effect |
|-----------|-----------|
| Site resolved (has ID) | `WHERE site_id = :siteId` |
| All Sites mode (explicit) | No filter |
| Resolved but no site (unknown domain) | `WHERE 1 = 0` (empty result) |
| Not resolved (console, boot) | No filter |

The scope lazily triggers `resolve(request())` if the resolver hasn't run yet.

### Slug Uniqueness

`UniqueTranslatableSlug` validation rule is site-aware. The same slug (e.g., `/about`) can exist on different sites:

```php
// In UniqueTranslatableSlug::validate()
if (app()->bound('tallcms.multisite.resolver')) {
    $resolver = app('tallcms.multisite.resolver');
    if ($resolver->isResolved() && $resolver->id()) {
        $query->where('site_id', $resolver->id());
    }
}
```

### Menu Location Uniqueness

The `tallcms_menus.location` unique constraint is changed to `(site_id, location)` composite unique, allowing the same location (e.g., `header`) per site.

---

## SiteSetting Integration

### Reading (site-aware `get()`)

`SiteSetting::get()` checks `tallcms_site_setting_overrides` first when the multisite resolver is active:

```php
// Core code — plugin-absence-safe via string container lookup
if (app()->bound('tallcms.multisite.resolver')) {
    $resolver = app('tallcms.multisite.resolver');
    if ($resolver->isResolved() && $resolver->id()) {
        // Check override table, fall back to global
    }
}
```

### Writing (site-aware `set()`)

`SiteSetting::set()` writes to the override table when a site is selected:

```php
if (app()->bound('tallcms.multisite.resolver')) {
    $resolver = app('tallcms.multisite.resolver');
    if ($resolver->isResolved() && $resolver->id()) {
        DB::table('tallcms_site_setting_overrides')->updateOrInsert(...);
        return;
    }
}
// Fall through to global write
```

This is a **platform-level behavior change**: any admin code running with a site context writes per-site overrides automatically.

### Reading global (bypass override)

`SiteSetting::getGlobal()` always reads from the global table, ignoring multisite overrides. Used by the Theme Manager admin page in global/"All Sites" mode.

---

## Theme Integration

### Per-Request Theme Setup

`ResolveSiteMiddleware` handles theme switching per request:

1. Sets `Config::set('theme.active', $site->theme)`
2. Resets `ThemeManager` singleton via `app()->forgetInstance()`
3. Calls `resetViewPaths()` — clears base paths AND `tallcms` namespace hints
4. Calls `registerThemeViewPaths()` — registers new theme's view hierarchy
5. Calls `registerPluginViewOverrides($theme)` — theme overrides for plugin views
6. Flushes view finder cache via `View::flushFinderCache()`
7. Rebinds `ThemeInterface` to new `FileBasedTheme` instance

### Per-Site Theme Activation

When the Theme Manager activates a theme for a specific site:

1. Updates `tallcms_sites.theme` column
2. Publishes theme assets (`publishThemeAssets()` — creates symlinks)
3. Clears compiled views (prevents stale cached Blade templates)
4. Clears the site's default preset

Global activation (All Sites mode) uses the standard `activateWithRollback()` path, writing to `config/theme.php`.

### Theme Manager Admin Context

The Theme Manager page reads the selected site directly from session, not from the resolver:

```php
protected function getMultisiteContext(): ?object
{
    $sessionValue = session('multisite_admin_site_id');
    if (!$sessionValue || $sessionValue === '__all_sites__') return null;

    return DB::table('tallcms_sites')
        ->where('id', $sessionValue)
        ->where('is_active', true)
        ->first();
}
```

This intentionally bypasses the `CurrentSiteResolver` singleton to avoid boot-time resolution races.

---

## License Gating

### Entitlement Model

| State | Behavior |
|-------|----------|
| Never activated | Plugin inert — migrations run, features disabled |
| Active license | All features enabled |
| Expired license | All features **continue working** (updates gated) |
| Deactivated | Plugin inert (license transferred elsewhere) |

### Implementation

```php
// MultisiteServiceProvider::isLicensed()
$licenseService->isValid('tallcms/multisite')     // active + grace
    || $licenseService->hasEverBeenLicensed(...)   // hard-expired but was activated
```

The Filament admin UI (`MultisitePlugin::register()`) performs the same check and skips resource/render hook registration when unlicensed.

---

## Core Package Changes

All changes are plugin-absence-safe (guarded by runtime checks):

| File | Change | Guard |
|------|--------|-------|
| `CmsPage.php` | Site-aware homepage enforcement in `boot()` | `Schema::hasColumn('tallcms_pages', 'site_id')` |
| `SiteSetting.php` | `get()` checks site overrides; `set()` writes to overrides; `getGlobal()` added | `app()->bound('tallcms.multisite.resolver')` |
| `ThemeManager.php` (service) | `resetViewPaths()` made public; clears namespace hints | N/A (safe regardless) |
| `ThemeManager.php` (page) | Site-aware theme activation, preset, context indicator | `session('multisite_admin_site_id')` + `DB::table()` with `QueryException` catch |
| `UniqueTranslatableSlug.php` | Site-scoped slug uniqueness | `app()->bound('tallcms.multisite.resolver')` |

---

## Building Multisite-Aware Features

### Reading the current site

```php
// In middleware/frontend (via resolver)
$resolver = app('tallcms.multisite.resolver');
$siteId = $resolver->id();

// In admin Filament pages (via session — preferred for reliability)
$siteId = session('multisite_admin_site_id');
$site = DB::table('tallcms_sites')->where('id', $siteId)->first();
```

### Adding site_id to a new model

1. Add a nullable `site_id` FK column with `nullOnDelete`
2. Add `SiteScope` global scope in the service provider
3. Auto-assign `site_id` via a `creating` listener
4. Update any unique constraints to be composite with `site_id`

### Writing site-aware settings

Just use `SiteSetting::get()` and `SiteSetting::set()` — they're automatically site-aware when a site is selected.

For admin pages that must read the global value regardless of site context, use `SiteSetting::getGlobal()`.

---

## Common Pitfalls

**"SiteScope filters out all content"**
The scope returns empty results (`WHERE 1 = 0`) when the resolver ran but found no matching site. This prevents cross-site content leakage. Check that your domain is correctly registered.

**"Admin actions affect the wrong site"**
The `CurrentSiteResolver` singleton can cache stale boot-time state. For admin UI pages, read `session('multisite_admin_site_id')` directly instead of going through the resolver.

**"Theme views don't change between sites"**
`resetViewPaths()` must clear both base view paths AND namespace hints. If you see the wrong theme's layout, check that the `tallcms` namespace hints are being cleaned.

**"Settings write to the wrong scope"**
`SiteSetting::set()` is site-aware as a platform-level behavior. Any call with a resolved site context writes to overrides. Use `SiteSetting::getGlobal()` / direct `static::updateOrCreate()` if you need the global path.

---

## Next Steps

- [Plugin development](plugins) — Build plugins compatible with multisite
- [Theme development](themes) — Create themes for multi-site installations
- [Plugin licensing](plugin-licensing) — How official plugin licensing works
