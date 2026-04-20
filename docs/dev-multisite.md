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

> **What you'll learn:** How the multisite system works internally, how Site is a core model, how settings inheritance works, and how to build multisite-aware features.

---

## Overview

TallCMS has a two-layer site architecture:

1. **Core**: Every TallCMS installation has at least one Site record. Standalone = one site. Site model, settings service, and Site resource live in core (`packages/tallcms/cms/`).
2. **Multisite plugin**: Adds multiple sites, domain resolution, ownership, site switching, domain verification, plans/quotas, and templates (`plugins/tallcms/multisite/`).

The plugin extends core — it does not own the Site model or settings infrastructure.

---

## Database Schema

### `tallcms_sites` (core)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | |
| `name` | string | Public brand name |
| `domain` | string, unique | Normalized domain (lowercase, no protocol/port) |
| `theme` | string, nullable | Theme slug override |
| `locale` | string, nullable | Locale override |
| `uuid` | uuid, unique | Stable public identifier |
| `is_default` | boolean | Fallback site (exactly one) |
| `is_active` | boolean | Enable/disable |
| `metadata` | json, nullable | Extensibility |

### Multisite plugin adds to `tallcms_sites`:

| Column | Type | Notes |
|--------|------|-------|
| `user_id` | unsignedBigInteger, nullable | Site owner |
| `is_template_source` | boolean | Template authoring flag |
| `domain_verified` | boolean | Backward-compat TLS flag |
| `domain_status` | string(20) | `pending`, `verified`, `failed`, `stale` |
| `domain_verified_at` | timestamp, nullable | Last successful verification |
| `domain_checked_at` | timestamp, nullable | Last check attempt |
| `domain_verification_note` | string, nullable | Human-readable result |
| `domain_verification_data` | json, nullable | Observed DNS records |

### `tallcms_site_setting_overrides` (core)

Per-site setting overrides. `site_id` + `key` unique composite.

| Column | Type |
|--------|------|
| `site_id` | FK to `tallcms_sites` |
| `key` | string |
| `value` | text |
| `type` | string |

### Content scoping

| Resource | Scoped | Mechanism |
|----------|--------|-----------|
| `tallcms_pages` | Per-site | `site_id` FK + `SiteScope` global scope |
| `tallcms_menus` | Per-site | `site_id` FK + `SiteScope` global scope |
| `tallcms_posts` | User-owned | `user_id` FK, no site scope |
| `tallcms_categories` | User-owned | `user_id` FK, no site scope |
| `tallcms_media` | User-owned | `user_id` FK, no site scope |

---

## Settings Architecture

### Two-Level Model

Settings use a global-default + per-site-override model:

```
SiteSetting::get('contact_email')
  → Check per-site override (tallcms_site_setting_overrides)
  → Fall back to global default (tallcms_site_settings)
```

### Settings Service (Admin Writes)

All admin settings writes go through `SiteSettingsService` with explicit site IDs:

```php
$service = app(SiteSettingsService::class);

// Read for a specific site (override → global fallback)
$service->getForSite($siteId, 'contact_email', $default);

// Write an override for a specific site
$service->setForSite($siteId, 'contact_email', 'hello@example.com');

// Remove override (site resumes inheriting global)
$service->resetForSite($siteId, 'contact_email');

// Check if a site has an override
$service->hasOverride($siteId, 'contact_email');

// Read global default (no site context)
$service->getGlobal('contact_email', $default);
```

No admin write path uses ambient session context. The site ID is always explicit.

### Frontend Reads

Frontend code uses `SiteSetting::get()` which resolves site context automatically:

- **Admin requests** (`tallcms.admin_context` attribute): reads from session site
- **Frontend requests**: reads from domain-resolved site
- **Console/boot**: returns global default

### Global-Only Keys

Some settings are installation-scoped and never per-site:

```php
// Explicit keys
SiteSetting::$globalOnlyKeys = [
    'i18n_enabled', 'default_locale', 'hide_default_locale', 'i18n_locale_overrides',
    'code_head', 'code_body_start', 'code_body_end',
    'code_head_audit', 'code_body_start_audit', 'code_body_end_audit',
    'seo_rss_enabled', 'seo_rss_limit', 'seo_rss_full_content', 'seo_sitemap_enabled',
];

// Prefix-based
SiteSetting::$globalOnlyPrefixes = ['seo_'];
```

`SiteSetting::set()` automatically routes global-only keys through `setGlobal()`.

### site_name Alias

`site_name` is a Site model field (`tallcms_sites.name`), not a setting override:

- `SiteSetting::get('site_name')` resolves from `Site.name` for the current site
- `SiteSetting::set('site_name', ...)` writes to `Site.name` for the current site
- Fallback chain: current site name → global `site_name` setting → default site name → `config('app.name')`

### Admin Save Loop Pattern

When saving settings on a Site edit page, the loop preserves inheritance:

```php
foreach ($settingKeys as $key => $type) {
    $value = $data[$key];
    $globalValue = $service->getGlobal($key);
    $matchesGlobal = valuesMatch($value, $globalValue, $type);
    $hasOverride = $service->hasOverride($site->id, $key);

    if ($matchesGlobal) {
        // Matches global — remove override to restore inheritance
        if ($hasOverride) {
            $service->resetForSite($site->id, $key);
        }
        continue;
    }

    // Differs from global — create or update override
    $service->setForSite($site->id, $key, $value, $type);
}
```

Four states:
- No override + matches global → skip (preserve inheritance)
- No override + differs from global → create override
- Has override + matches global → delete override (restore inheritance)
- Has override + differs from global → update override

---

## Filament Admin Structure

### Core (packages/tallcms/cms/)

| Component | Purpose |
|-----------|---------|
| `SiteResource` | Single-record edit page in standalone; base for multisite extension |
| `EditSite` (Page) | Custom page with settings form; loads/saves via SiteSettingsService |
| `SiteForm` | Tab-based form: General, Branding, Contact, Social, Publishing, Maintenance |
| `GlobalDefaults` (Page) | Installation-scoped defaults for all 20 site-scoped settings + i18n |
| `SeoSettings` (Page) | Installation-scoped SEO settings (RSS, sitemap, robots, OG, llms.txt) |
| `CodeInjection` (Page) | Installation-scoped embed code (head, body start, body end) |
| `PagesRelationManager` | Pages belonging to the site |
| `MenusRelationManager` | Menus belonging to the site |

### Multisite Plugin (plugins/tallcms/multisite/)

| Component | Purpose |
|-----------|---------|
| `SiteResource` | Full CRUD with list/create/edit, ownership filtering |
| `EditSite` (EditRecord) | Extends Filament EditRecord; saves settings in `afterSave()` |
| `SiteForm` | Site + Status tabs (multisite-specific), imports core settings tabs |
| `PagesRelationManager` | Pages with site-context-aware create action |
| `MenusRelationManager` | Menus with inline create |
| `SiteSwitcher` (Livewire) | "Filter by Site" dropdown for content browsing |

The multisite `SiteForm` imports core's settings tabs:

```php
protected static function coreSettingsTabs(): array
{
    return [
        CoreSiteForm::settingsGeneralTab(),
        CoreSiteForm::brandingTab(),
        CoreSiteForm::contactTab(),
        CoreSiteForm::socialTab(),
        CoreSiteForm::publishingTab(),
        CoreSiteForm::maintenanceTab(),
    ];
}
```

### Navigation Adapts to Mode

- **Standalone**: Pages and Menus are top-level nav items (direct access)
- **Multisite**: Pages and Menus hidden from top-level nav; accessed through Site resource relation managers

This is controlled by `shouldRegisterNavigation()` on `CmsPageResource` and `TallcmsMenuResource`, which return `false` when `tallcms_multisite_active()`.

---

## Site Resolution (Multisite Only)

### Frontend (Domain-Based)

`ResolveSiteMiddleware` runs in the `web` middleware group:

```
Request → match domain against tallcms_sites.domain
  → found: load site, override theme/view paths/locale
  → not found: 404
```

### Admin (Session-Based)

The "Filter by Site" dropdown stores the selected site in `session('multisite_admin_site_id')`. This filters content lists (pages, menus) via `SiteScope`.

**Important**: The site filter only affects content browsing. Settings writes are always explicit-by-site-id through the Site edit page — they never depend on the session filter.

### Context-Aware Resolution

`SiteSetting::resolveCurrentSiteId()` uses different sources based on request type:

| Context | Source | Why |
|---------|--------|-----|
| **Admin** (`tallcms.admin_context` attribute) | Session | Immune to stale resolver |
| **Frontend** (no attribute) | Resolver singleton | Domain-based |
| **Boot / console** (no request) | Returns null | Global settings |

---

## Query Scoping (Multisite Only)

### SiteScope

Applied to `CmsPage` and `TallcmsMenu`:

| Condition | SQL Effect |
|-----------|-----------|
| Site resolved (has ID) | `WHERE site_id = :siteId` |
| All Sites mode | No filter |
| Unknown domain | `WHERE 1 = 0` (empty) |
| Not resolved (console) | No filter |

### Slug Uniqueness

`UniqueTranslatableSlug` is site-aware for site-scoped tables and user-aware for user-owned tables. It also excludes soft-deleted records.

---

## Domain Verification (Multisite Only)

Custom domains require DNS verification before TLS certificates are issued. Managed subdomains (`*.base_domain`) are auto-trusted.

### State Machine

```
[Create site] → Pending
                  ↓ (verify succeeds)
               Verified ←──────────────┐
                  ↓ (re-verify fails)   │
                Stale                   │
                  ↓ (fails again)       │
                Failed                  │
                  ↓ (verify succeeds)   │
                  └─────────────────────┘
```

### Key Classes

| Class | Purpose |
|-------|---------|
| `DomainStatus` | Enum: Pending, Verified, Failed, Stale |
| `DomainVerificationService` | DNS checks, setup instructions, TLS dispatch |
| `TriggerTlsProvisioning` | Queued job, 3 retries |
| `ReverifyDomains` | Scheduled hourly, batched re-verification |

---

## Building Multisite-Aware Features

### Reading settings for a specific site

```php
// Explicit (admin writes, jobs, commands)
$service = app(SiteSettingsService::class);
$value = $service->getForSite($siteId, 'contact_email');

// Ambient (frontend runtime, views, Blade)
$value = SiteSetting::get('contact_email');
```

### Adding site_id to a new model

1. Add a nullable `site_id` FK column with `nullOnDelete`
2. Add `SiteScope` global scope in the multisite service provider
3. Auto-assign `site_id` via a `creating` listener
4. Update unique constraints to be composite with `site_id`

### Writing installation-scoped settings

For settings that should never vary per site:

```php
// Option A: Add to $globalOnlyKeys in SiteSetting
// Option B: Use setGlobal/getGlobal directly
SiteSetting::setGlobal('my_plugin_setting', $value, 'text', 'my-plugin');
$value = SiteSetting::getGlobal('my_plugin_setting', $default);
```

---

## Remaining Cleanup (Post-Refactor)

The following are known architectural items deferred for future work:

1. **Model duplication**: Multisite plugin has its own `Site.php` and `SiteSettingOverride.php` that shadow core's instead of extending them. Future fixes should land in one place.
2. **SEO scoping**: Currently all `seo_*` keys are global-only. A future pass should split them: feed/index settings stay global, brand/policy settings (robots.txt, OG image, llms.txt) become site-scoped.
3. **ThemeManager**: Still uses `SiteSetting::set()` for `theme_default_preset` — could be made explicitly global.

---

## Next Steps

- [Plugin development](plugins) — Build plugins compatible with multisite
- [Theme development](themes) — Create themes for multi-site installations
- [Site settings](site-settings) — User guide for settings management
