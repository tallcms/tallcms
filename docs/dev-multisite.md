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
| `user_id` | unsignedBigInteger, nullable | Site owner (for SaaS multi-tenancy) |
| `is_default` | boolean | Fallback site for admin |
| `is_active` | boolean | Enable/disable |
| `domain_verified` | boolean | Backward-compat TLS flag (synced from `domain_status`) |
| `domain_status` | string(20) | `pending`, `verified`, `failed`, `stale` — backed by `DomainStatus` enum |
| `domain_verified_at` | timestamp, nullable | When DNS was last successfully verified |
| `domain_checked_at` | timestamp, nullable | When DNS was last checked (success or failure) |
| `domain_verification_note` | string, nullable | Human-readable verification result |
| `domain_verification_data` | json, nullable | Structured observed DNS records for diagnostics |
| `metadata` | json, nullable | Extensibility |

**Indexes:** `(domain_status, domain_checked_at)` composite index for re-verification query.

### `tallcms_site_setting_overrides`

Per-site setting overrides. Same key/value/type structure as `tallcms_site_settings`, scoped by `site_id`.

### Added columns

- `tallcms_pages.site_id` — nullable FK to `tallcms_sites`, `nullOnDelete`
- `tallcms_menus.site_id` — same pattern

Posts and categories are intentionally **not** site-scoped.

### Scope Summary

| Resource | Scoped | Mechanism |
|----------|--------|-----------|
| `tallcms_pages` | Per-site | `site_id` FK + `SiteScope` global scope |
| `tallcms_menus` | Per-site | `site_id` FK + `SiteScope` global scope |
| `tallcms_menus.location` | Per-site unique | Composite unique `(site_id, location)` |
| `tallcms_posts` | Global | No `site_id` column |
| `tallcms_categories` | Global | No `site_id` column |
| `tallcms_media` | Global | No `site_id` column |
| `tallcms_site_settings` | Global defaults | Overrides in `tallcms_site_setting_overrides` |
| Theme (active) | Per-site | `tallcms_sites.theme` column, runtime override in middleware |
| Theme preset | Per-site | Via `SiteSetting` override for `theme_default_preset` |
| Installed themes | Global | Filesystem-based, shared across all sites |
| Plugins | Global | Filesystem-based, no per-site activation |
| Users / roles | Global | No `site_id` scoping |

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

**Architectural rule:** Admin-selected site comes from session. Frontend site comes from domain resolution. These must not be collapsed.

Three contexts read the admin session:

| Context | How it reads | Why |
|---------|-------------|-----|
| **SiteSetting** | `resolveCurrentSiteId()` — checks admin context attribute, then session | Context-aware: only uses session in admin, resolver on frontend |
| **SiteScope** | Via `CurrentSiteResolver` singleton | Resolver detects admin via `tallcms.admin_context` request attribute or URL path match |
| **ThemeManager / SiteSettings pages** | Directly from `session()` + `DB::table()` | Bypasses resolver to avoid singleton timing issues during boot/Livewire lifecycle |

`MarkAdminContext` middleware (added to Filament panel stack by `MultisitePlugin`) sets the `tallcms.admin_context` request attribute on every admin request, including Livewire updates.

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

### Settings Scope Policy

Each setting key has a scope:

| Scope | Behavior | Examples |
|-------|----------|---------|
| **global-only** | Never per-site, even in site context | `i18n_enabled`, `default_locale`, `hide_default_locale`, `i18n_locale_overrides`, `code_*_audit` |
| **site-override** | Global default + optional per-site override | All other settings (site_name, contact_email, logo, etc.) |

The registry is a static array `SiteSetting::$globalOnlyKeys`. Everything not listed defaults to `site-override`.

### Context-Aware Resolution

`SiteSetting::get()` and `set()` use `resolveCurrentSiteId()` which is **context-aware**:

| Context | Source | Why |
|---------|--------|-----|
| **Admin** (`tallcms.admin_context` attribute) | Session | Immune to stale resolver state |
| **Frontend** (no attribute) | Resolver singleton | Domain-based, middleware-driven |
| **Boot / console** (no request) | Returns null | Global settings |

This prevents admin session state from leaking into frontend settings reads.

```php
protected static function resolveCurrentSiteId(): ?int
{
    $isAdminContext = request()?->attributes->get('tallcms.admin_context', false);

    if ($isAdminContext) {
        // Admin: session is the source of truth
        $sessionValue = session('multisite_admin_site_id');
        if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
            return (int) $sessionValue;
        }
        return null;
    }

    // Frontend: resolver is the source of truth
    // ...
}
```

### Three Override States

| State | DB | `get()` returns |
|-------|-----|-----------------|
| No override row | No row in overrides table | Global default |
| Override with value | Row with value | Override value |
| Override with empty | Row with `''` | Empty string (not global) |

`resetToGlobal($key)` deletes the override row. Distinct from storing empty.

### Reading and Writing

- `SiteSetting::get($key)` — policy-aware: skips override for global-only keys, checks override then global for site-override keys
- `SiteSetting::set($key, $value)` — policy-aware: writes to global for global-only keys, writes to override table in site context for site-override keys
- `SiteSetting::getGlobal($key)` — always reads global, ignoring overrides
- `SiteSetting::setGlobal($key, $value)` — always writes global
- `SiteSetting::resetToGlobal($key)` — deletes the override row
- `SiteSetting::isGlobalOnly($key)` — checks the hardcoded `$globalOnlyKeys` array in the core model

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

## Domain Verification

### Architecture

Custom domains require DNS verification before TLS certificates are issued. Managed subdomains (`*.base_domain`) are auto-trusted.

**Key classes:**

| Class | Location | Purpose |
|-------|----------|---------|
| `DomainStatus` | `src/Enums/DomainStatus.php` | Backed string enum: `Pending`, `Verified`, `Failed`, `Stale` |
| `DomainVerificationService` | `src/Services/DomainVerificationService.php` | DNS checks (A/AAAA/CNAME), setup instructions, TLS dispatch |
| `TriggerTlsProvisioning` | `src/Jobs/TriggerTlsProvisioning.php` | Queued job, 3 retries with backoff |
| `ReverifyDomains` | `src/Console/Commands/ReverifyDomains.php` | Scheduled hourly, batched re-verification |
| `MultisiteSettings` | `src/Filament/Pages/MultisiteSettings.php` | Admin page for server IPs, CNAME target, batch config |

### State Machine

```
[Create site] → Pending
                  ↓ (manual verify succeeds)
               Verified ←──────────────────┐
                  ↓ (re-verify fails)       │
                Stale                       │
                  ↓ (re-verify fails again) │
                Failed                      │
                  ↓ (manual verify succeeds)│
                  └─────────────────────────┘

[Domain changed] → Pending (resets all verification fields)
```

**Verified** is the only status eligible for TLS. **Stale** is a grace period (one failed re-check). **Failed** revokes `domain_verified` and TLS eligibility.

### TLS Eligibility

`Site::isEligibleForTls()` checks:

1. Site must be active
2. Managed subdomain → always eligible
3. Custom domain → `domain_status === DomainStatus::Verified`

The `/internal/tls/verify` endpoint calls `Site::findVerifiedByDomain()` which uses this method.

### Verification Flow

`DomainVerificationService::verify(Site $site)` reads configuration from `SiteSetting::getGlobal()`:

- `multisite_server_ips` — newline-separated list of accepted IPs (IPv4/IPv6)
- `multisite_cname_target` — expected CNAME target domain

DNS checks use PHP's `dns_get_record()` against A, AAAA, and CNAME record types.

### Re-verification

`tallcms:reverify-domains` runs hourly via auto-registered schedule:

1. Reads `multisite_reverify_days` (minimum 7, 0 = disabled) and `multisite_reverify_batch_size` (clamped 1-500)
2. Queries verified/stale sites with null or stale `domain_checked_at`, ordered stalest-first
3. Processes up to `batch_size` sites per run
4. Writes a heartbeat timestamp (`multisite_reverify_last_run`) for scheduler health monitoring
5. State transitions: verified → stale (first failure), stale → failed (second consecutive failure)

### Backward Compatibility

`domain_verified` (boolean) is kept in sync with `domain_status` for the TLS verify endpoint and any external consumers. `isEligibleForTls()` reads `domain_status` as the source of truth.

### Settings Storage

Multisite settings use `SiteSetting::getGlobal()` / `setGlobal()` directly, making them installation-wide without needing to register global-only keys in the core package.

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
| `SiteSetting.php` | Settings scope policy, context-aware `resolveCurrentSiteId()`, `get()`/`set()` policy-aware, `getGlobal()`, `setGlobal()`, `resetToGlobal()` | `request()?->attributes->get('tallcms.admin_context')` + `app()->bound('tallcms.multisite.resolver')` |
| `SiteSettings.php` (page) | Override indicators (hint icons, "Reset to global" actions), smart save loop (only changed fields create overrides), global-only fields locked | `session('multisite_admin_site_id')` + `DB::table()` |
| `ThemeManager.php` (service) | `resetViewPaths()` made public; clears namespace hints | N/A (safe regardless) |
| `ThemeManager.php` (page) | Site-aware theme activation, preset, rollback, context indicator | `session('multisite_admin_site_id')` + `DB::table()` with `QueryException` catch |
| `UniqueTranslatableSlug.php` | Site-scoped slug uniqueness | `app()->bound('tallcms.multisite.resolver')` |
| `CmsPageForm.php` | Uses `UniqueTranslatableSlug` for non-i18n path too (site-aware) | N/A |

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

### Checking domain verification

```php
$site = Site::find($id);

// Check TLS eligibility (managed subdomain or verified status)
$site->isEligibleForTls();

// Check if managed subdomain (auto-trusted)
$site->isManagedSubdomain();

// Read verification status
$site->domain_status; // DomainStatus enum: Pending, Verified, Failed, Stale

// Programmatic verification
$service = app(DomainVerificationService::class);
$result = $service->verify($site);
// Returns: ['status' => 'verified'|'failed', 'message' => '...', 'observed' => [...], 'matched_on' => 'A'|'CNAME'|null]
```

### Writing site-aware settings

`SiteSetting::get()` and `set()` are automatically site-aware when a site is selected. The scope policy ensures global-only keys (i18n, audit) always read/write globally.

Key methods:
- `SiteSetting::get($key)` — site-override first, global fallback
- `SiteSetting::set($key, $value)` — writes to override in site context, global otherwise
- `SiteSetting::getGlobal($key)` — always reads global (for admin pages showing global defaults)
- `SiteSetting::setGlobal($key, $value)` — always writes global
- `SiteSetting::resetToGlobal($key)` — deletes the override, restoring inheritance
- `SiteSetting::isGlobalOnly($key)` — checks the hardcoded `$globalOnlyKeys` array (core-owned keys only; plugins should use `getGlobal()`/`setGlobal()` instead)

### Admin save loop pattern

When saving settings in a multisite-aware admin page, only create overrides for fields that actually changed:

```php
$hasExistingOverride = in_array($key, $overriddenKeys);
$globalValue = SiteSetting::getGlobal($key);

// Skip unchanged fields (no override pollution)
if (! $hasExistingOverride && $this->valuesMatch($value, $globalValue, $type)) {
    continue;
}

SiteSetting::set($key, $value, $type, $group);
```

This prevents the common bug where saving a form creates overrides for every field, even untouched ones that had the global default loaded into the form.

---

## Site Ownership

### Model

`tallcms_sites.user_id` identifies the site owner. `Site::owner()` is a `BelongsTo` relationship.

- **Super-admin:** Sees all sites. Can assign/reassign ownership via Select field.
- **Non-super-admin:** Sees only their owned sites. `user_id` auto-assigned on creation.

### Enforcement Layers

| Layer | What it does | Where |
|-------|-------------|-------|
| **SiteResource::getEloquentQuery()** | Filters site list by `user_id` | Plugin resource |
| **SiteSwitcher::siteQuery()** | Filters switcher by `user_id` | Plugin Livewire |
| **SitePolicy** | Gates view/update/delete by ownership | Plugin policy |
| **MarkAdminContext** | Validates session site belongs to user, resets if not | Plugin middleware |
| **"All Sites" mode** | Disabled for non-super-admins | Switcher Blade view |

### Quotas (Site Plan System)

As of v1.3.0, the plugin includes a built-in plan/tier system for quota enforcement.

**Database tables:**
- `tallcms_site_plans` — Plan definitions (name, slug, max_sites, is_default, is_active)
- `tallcms_user_site_plans` — Per-user plan assignments (user_id unique, site_plan_id with restrictOnDelete)

**Enforcement architecture (three layers):**

| Layer | Role | Locking? |
|-------|------|----------|
| `SitePolicy::create()` | **Advisory UI gate** — hides Create buttons, returns 403. No lock, can be stale. | No |
| `SitePlanService::createSiteWithQuota()` | **Authoritative** — owns DB transaction, acquires per-user `lockForUpdate()`, counts, creates. | Yes |
| `Site::creating()` boot hook | **Safety net** — catches direct `Site::create()` calls that bypass the service. | No |

All three layers delegate to `SitePlanService::resolveQuotaDecision()` — the single source of truth for quota logic.

**Key service methods:**
- `SitePlanService::createSiteWithQuota($user, $attributes)` — The only path for user-facing site creation
- `SitePlanService::canCreateSite($user)` — Advisory check (no lock), for UI/policy
- `SitePlanService::assignPlan($user, $plan, $assignedBy)` — Upserts the assignment row
- `SitePlanService::isOverQuota($user)` — Grandfathering indicator

**Configuration:** `tallcms.multisite.quota_enforcement` — `'strict'` (default, deny if no plan) or `'permissive'` (allow if no plan, for self-hosted).

**Customization:** Apps can swap the `SitePlanService` binding or extend `SitePolicy` for custom quota logic. The `HasSitePlan` trait (`Tallcms\Multisite\Concerns\HasSitePlan`) can be added to the User model for convenience methods.

**Spark integration:** Plan slugs (`tallcms_site_plans.slug`) are the bridge. When Spark is added, a subscription change listener calls `SitePlanService::assignPlan()` to update the user's effective plan.

---

## Common Pitfalls

**"SiteScope filters out all content"**
The scope returns empty results (`WHERE 1 = 0`) when the resolver ran but found no matching site. This prevents cross-site content leakage. Check that your domain is correctly registered.

**"Admin actions affect the wrong site"**
The `CurrentSiteResolver` singleton can cache stale boot-time state. For admin UI pages, read `session('multisite_admin_site_id')` directly instead of going through the resolver. The `SiteSetting` model handles this via `resolveCurrentSiteId()` which uses session in admin context and resolver on frontend.

**"Frontend shows the wrong site's settings"**
`resolveCurrentSiteId()` is context-aware: it only uses the admin session when `tallcms.admin_context` request attribute is set. On frontend requests (no attribute), it uses the domain-based resolver. If you see cross-site settings leakage, check that the admin context attribute is not set on frontend requests.

**"Saving settings creates overrides for untouched fields"**
The save loop must compare submitted values against global defaults and only create overrides for fields that actually changed. See the "Admin save loop pattern" section above.

**"Theme views don't change between sites"**
`resetViewPaths()` must clear both base view paths AND `tallcms` namespace hints. If you see the wrong theme's layout, check that the namespace hints are being cleaned.

**"Settings write to the wrong scope"**
`SiteSetting::set()` is site-aware: writes to overrides in admin context, global on frontend. Global-only keys (i18n, audit) always write to global. Use `SiteSetting::getGlobal()` / `SiteSetting::setGlobal()` if you need the global path explicitly.

**"Global-only settings are editable per-site"**
Add the key to `SiteSetting::$globalOnlyKeys` to prevent per-site overrides. The Site Settings admin page will show these as locked fields with a "Global setting" label. For plugin-owned settings, use `SiteSetting::getGlobal()` / `setGlobal()` directly to bypass the override system entirely.

**"Domain verification says 'not configured'"**
Server IPs or CNAME target must be set in **Multisite Settings** (`multisite_server_ips` / `multisite_cname_target` in `tallcms_site_settings` table). These are read via `SiteSetting::getGlobal()`.

**"Re-verification never runs"**
The command is auto-scheduled hourly but requires the Laravel scheduler (`php artisan schedule:run`) to be running. The Multisite Settings page shows a health warning if the scheduler heartbeat (`multisite_reverify_last_run`) is stale.

---

## Next Steps

- [Plugin development](plugins) — Build plugins compatible with multisite
- [Theme development](themes) — Create themes for multi-site installations
- [Plugin licensing](plugin-licensing) — How official plugin licensing works
