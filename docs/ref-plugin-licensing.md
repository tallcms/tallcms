---
title: "Plugin & Theme Licensing Architecture"
slug: "plugin-licensing"
audience: "developer"
category: "reference"
order: 60
prerequisites:
  - "plugins"
---

# Plugin & Theme Licensing Architecture

> **What you'll learn:** How TallCMS handles plugin licensing, the marketplace catalog, and the license activation flow from end to end.

---

## Overview

TallCMS uses a three-layer licensing architecture:

1. **Marketplace Catalog** (tallcms.com) — source of truth for what's available and what requires licensing
2. **License Proxy** (tallcms.com) — middleman between client sites and Anystack for license operations
3. **Client CMS** (your site) — fetches catalog, manages local license state, enforces activation UI

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Client CMS    │────▶│   tallcms.com   │────▶│    Anystack     │
│                 │     │                 │     │                 │
│ MarketplaceCat. │     │ Marketplace API │     │ License API     │
│ PluginLicense   │     │ License Proxy   │     │ Product/License │
│ Service         │     │                 │     │ Activations     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## License Model

| Type | `is_paid` | `requires_license` | Behavior |
|------|-----------|---------------------|----------|
| Official free plugin | false | false | Download freely, no activation |
| Official paid plugin | true | true | Activate license after install via proxy |
| Official paid theme | true | false | Download-gated via Anystack, no post-install activation |
| 3rd party free plugin | false | false | Listed in marketplace, install directly |
| 3rd party paid plugin | true | false | Purchase/download links to developer's site. Developer handles licensing in their service provider |

**Key distinction:** `is_paid` means the item costs money. `requires_license` means the CMS enforces post-install license activation via the TallCMS proxy. These are independent flags.

---

## Source of Truth

### Who decides if a plugin requires license activation?

The **marketplace catalog** is the primary source of truth. Each marketplace item has a `requires_license` boolean set by the TallCMS team.

```
Plugin::requiresLicense()
    ├─ Catalog available? → use catalog's requires_license
    └─ Catalog unreachable? → fall back to plugin.json (official only)
```

The fallback ensures paid plugins don't lose their activation UI during API outages. It only applies to official plugins (`vendor = tallcms`) to prevent 3rd party authors from accidentally triggering the TallCMS proxy.

**Code:** `packages/tallcms/cms/src/Models/Plugin.php` — `requiresLicense()`

### Why not just use plugin.json?

If `plugin.json` controlled licensing, any 3rd party author setting `license_required: true` would trigger the TallCMS Anystack proxy, which wouldn't have their product mapping. The proxy would return "Plugin Not Supported."

---

## Marketplace Catalog Service

`MarketplaceCatalogService` is the centralized service all consumers use to access the remote catalog.

**File:** `packages/tallcms/cms/src/Services/MarketplaceCatalogService.php`

### Methods

| Method | Purpose |
|--------|---------|
| `getPlugins()` | Fetch plugin items (type=plugin) |
| `getThemes()` | Fetch theme items (type=theme) |
| `getAll()` | Fetch all items |
| `findBySlug($fullSlug)` | Find a single item by vendor/slug |
| `getPurchaseUrl($fullSlug)` | Get purchase URL (config fallback → catalog) |
| `getDownloadUrl($fullSlug)` | Get download URL (config fallback → catalog) |
| `clearCache()` | Clear all cached catalog data |

### Caching

- **Client-side:** 1 hour TTL (configurable via `TALLCMS_CATALOG_CACHE_TTL`)
- **Server-side:** 5 minute TTL with version-counter invalidation
- **Cache clearing:** "Check for Updates" in Plugin Manager clears catalog cache

### URL Resolution

Purchase and download URLs resolve through a two-tier lookup:

1. **Config override** — `config('tallcms.plugins.license.purchase_urls.{slug}')` (durable fallback for outages)
2. **Remote catalog** — fetched from the marketplace API

This ensures paid users can always find their purchase/download links even when tallcms.com is unreachable.

### Consumers

| Consumer | Usage |
|----------|-------|
| `PluginManager` page | "From the Marketplace" section, available plugins |
| `ThemeManager` page | "From the Marketplace" section, available themes |
| `PluginLicenseService` | Purchase/download URLs in license status |
| `Plugin` model | `requiresLicense()` check |

---

## Marketplace API

**Endpoint:** `https://tallcms.com/marketplace-api/v1/catalog`

### Query Parameters

| Param | Values | Description |
|-------|--------|-------------|
| `type` | `plugin`, `theme` | Filter by item type |

### Response

```json
{
    "items": [
        {
            "full_slug": "tallcms/pro",
            "name": "TallCMS Pro",
            "description": "Advanced blocks and analytics",
            "author": "TallCMS",
            "version": "1.6.0",
            "item_type": "plugin",
            "item_type_name": "Plugin",
            "category": "official",
            "categories": ["official"],
            "featured": true,
            "is_paid": true,
            "requires_license": true,
            "price": "$20",
            "price_type": "lifetime",
            "download_url": "https://anystack.sh/download/tallcms-pro-plugin",
            "purchase_url": "https://checkout.anystack.sh/tallcms-pro-plugin",
            "screenshot_url": "https://...",
            "requires": ">=3.6"
        }
    ],
    "version": "2"
}
```

### Server-Side Caching

The API uses a version-counter pattern for cache invalidation:

1. Every `MarketplaceItem`, `MarketplaceCategory`, and `MarketplaceItemType` save/delete bumps `marketplace_api_version` in cache
2. The API route includes the version in its cache key
3. Old cache entries expire via 5-minute TTL

---

## License Proxy

The license proxy runs on tallcms.com as a plugin. It maps plugin slugs to Anystack product IDs and handles activation, validation, deactivation, and update checks.

**Plugin:** `tallcms/license-proxy`

### Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/license-proxy/activate` | POST | Activate a license key for a domain |
| `/license-proxy/validate` | POST | Validate a cached license |
| `/license-proxy/deactivate` | POST | Deactivate a license from a domain |
| `/license-proxy/updates` | POST | Check for updates (requires valid license) |

### Product Mapping

Each licensable plugin needs a product ID in the proxy's config:

```php
// license-proxy-plugin/src/config.php
'products' => [
    'tallcms/pro' => env('ANYSTACK_PRODUCT_ID_PRO', 'uuid-here'),
    'tallcms/mega-menu' => env('ANYSTACK_PRODUCT_ID_MEGA_MENU', 'uuid-here'),
],
```

If a plugin slug has no mapping, the proxy returns 404 and the CMS shows "Plugin Not Supported."

### License Key Lookup

Anystack's `GET /licenses?key=...` returns **all** licenses for a product, not just the matching one. The proxy uses `findLicenseByKey()` to match the correct license client-side.

### Domain Matching

Activations are matched by `fingerprint` and `hostname` (case-insensitive). The CMS sends `request()->getHost()` as the domain for all operations.

---

## License Activation Flow

```
User clicks "Activate" in Plugin Manager
    │
    ▼
PluginManager::activateLicenseAction()
    │ modal with license key input
    ▼
PluginLicenseService::activate($slug, $key)
    │
    ▼
LicenseProxyClient::activate($slug, $key, $domain)
    │ POST /license-proxy/activate
    ▼
License Proxy (tallcms.com)
    │ maps slug → Anystack product ID
    │ POST /v1/products/{id}/licenses/activate-key
    ▼
Anystack API
    │ creates activation record (fingerprint = domain)
    ▼
Response flows back:
    Anystack → Proxy → LicenseProxyClient → PluginLicenseService
    │ stores PluginLicense record locally
    ▼
UI updates: license badge turns green
```

---

## Deactivation Flow

```
User clicks "Deactivate"
    │
    ▼
PluginLicenseService::deactivate($slug)
    │
    ▼
LicenseProxyClient::deactivate($slug, $key, $domain)
    │ POST /license-proxy/deactivate
    ▼
License Proxy:
    1. Lookup license by key (findLicenseByKey)
    2. Get all activations (paginated)
    3. Find activation matching domain (case-insensitive)
    4. DELETE activation via Anystack API
    │
    ▼
Only marks local license as deactivated if proxy confirms success.
If no activation matches the domain → returns error (not success).
```

---

## Configuration

### Client CMS (`config/tallcms.php`)

```php
'plugins' => [
    // Marketplace catalog API
    'catalog_url' => env('TALLCMS_CATALOG_URL', 'https://tallcms.com/marketplace-api/v1/catalog'),
    'catalog_cache_ttl' => env('TALLCMS_CATALOG_CACHE_TTL', 3600),

    // Human-facing marketplace page
    'marketplace_url' => env('TALLCMS_MARKETPLACE_URL', 'https://tallcms.com/marketplace'),

    'license' => [
        // License proxy URL
        'proxy_url' => env('TALLCMS_LICENSE_PROXY_URL', 'https://tallcms.com'),

        // Durable fallback URLs (used when catalog API is unreachable)
        'purchase_urls' => [
            'tallcms/pro' => 'https://checkout.anystack.sh/tallcms-pro-plugin',
        ],
        'download_urls' => [
            'tallcms/pro' => 'https://anystack.sh/download/tallcms-pro-plugin',
        ],
    ],
],
```

### License Proxy (`license-proxy-plugin/src/config.php`)

```php
'anystack' => [
    'api_url' => env('ANYSTACK_API_URL', 'https://api.anystack.sh'),
    'api_key' => env('ANYSTACK_API_KEY'),
],

'products' => [
    'tallcms/pro' => env('ANYSTACK_PRODUCT_ID_PRO', 'uuid'),
    'tallcms/mega-menu' => env('ANYSTACK_PRODUCT_ID_MEGA_MENU', 'uuid'),
],
```

---

## Identifier Convention

All identifiers use **full slug format** (`vendor/slug`) end-to-end:

| Layer | Format | Example |
|-------|--------|---------|
| Marketplace DB | `slug` column | `tallcms/pro` |
| API response | `full_slug` field | `tallcms/pro` |
| Plugin model | `getFullSlug()` | `tallcms/pro` |
| License table | `plugin_slug` column | `tallcms/pro` |
| Proxy requests | `plugin_slug` param | `tallcms/pro` |

**Theme convention:** Marketplace theme entries use `vendor/theme-{name}` (e.g., `tallcms/theme-elevate`) to match `Theme::getLicenseSlug()`.

---

## Adding a New Licensed Plugin

1. **Create the product in Anystack** — get the product UUID
2. **Add product mapping to the license proxy** config: `'tallcms/your-plugin' => env('ANYSTACK_PRODUCT_ID_YOUR_PLUGIN', 'uuid')`
3. **Add update metadata to the license proxy** config under `updates`
4. **Deploy the updated proxy** to tallcms.com
5. **Add the plugin to the marketplace** with `is_paid: true` and `requires_license: true`
6. **Set `license_required: true`** in the plugin's `plugin.json` (fallback for offline)
7. **Add fallback URLs** to `tallcms.php` config under `purchase_urls` and `download_urls`

---

## Common Pitfalls

**"Plugin Not Supported" on activation**
The license proxy doesn't have a product mapping for this plugin slug. Add it to the proxy's `products` config.

**License shows as deactivated locally but still active in Anystack**
The proxy couldn't find an activation matching the domain. Check that the domain sent by `getCurrentDomain()` matches the `fingerprint` stored in Anystack (case-insensitive).

**"Available Plugins" section is empty**
The marketplace API is unreachable or returned no items. Check `TALLCMS_CATALOG_URL` in `.env` and verify the endpoint returns JSON.

**Paid plugin loses activation UI**
The catalog API is unreachable and `plugin.json` doesn't have `license_required: true`. Add it as a fallback for official plugins.

---

## Next Steps

- [Plugin development guide](plugins)
- [Theme development guide](themes)
- [API reference](api)
