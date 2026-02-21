# Upgrade Guide

This guide covers upgrading from TallCMS v1.x (skeleton-based) to v2.0 (package-based).

## Overview

TallCMS v2.0 introduces a **dual-mode architecture**:

1. **Standalone Mode**: Full CMS installation (same as v1.x)
2. **Plugin Mode**: Install as a Filament plugin in existing apps

If you're running a v1.x standalone installation, upgrading is straightforward with minimal changes.

---

## Standalone Installation Upgrade

### Step 1: Update composer.json

Add the package requirement:

```json
{
    "require": {
        "tallcms/cms": "^2.0"
    }
}
```

### Step 2: Run Composer Update

```bash
composer update tallcms/cms
```

### Step 3: Create Standalone Marker

Create a `.tallcms-standalone` file in your project root to enable standalone mode:

```bash
touch .tallcms-standalone
```

This marker tells the package to:
- Enable all CMS routes (pages, posts, catch-all)
- Show System Updates page
- Enable one-click updates
- Use full theme and plugin systems

### Step 4: Update AdminPanelProvider

Update your `app/Providers/Filament/AdminPanelProvider.php`:

```php
use TallCms\Cms\TallCmsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        // ... other configuration
        ->plugin(TallCmsPlugin::make());
}
```

### Step 5: Verify Configuration

Publish and review the configuration if needed:

```bash
php artisan vendor:publish --tag=tallcms-config
```

Key configuration options in `config/tallcms.php`:
- `mode`: Set to `'standalone'` or leave `null` for auto-detection
- `database.prefix`: Table prefix (default: `tallcms_`)
- `filament.panel_id`: Your Filament panel ID (default: `admin`)

### Step 6: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 7: Verify Installation

Visit `/admin` and confirm:
- All CMS resources appear (Pages, Posts, Categories, etc.)
- Site Settings page loads
- System Updates page shows (standalone only)
- Theme Manager and Plugin Manager are accessible

---

## Namespace Changes

v2.0 moves all code to the `TallCms\Cms` namespace. Class aliases are provided for backwards compatibility:

| v1.x Namespace | v2.0 Namespace |
|----------------|----------------|
| `App\Models\CmsPage` | `TallCms\Cms\Models\CmsPage` |
| `App\Models\CmsPost` | `TallCms\Cms\Models\CmsPost` |
| `App\Models\CmsCategory` | `TallCms\Cms\Models\CmsCategory` |
| `App\Services\ThemeManager` | `TallCms\Cms\Services\ThemeManager` |
| `App\Support\ThemeColors` | `TallCms\Cms\Support\ThemeColors` |

**Important**: Existing code using `App\*` namespaces will continue to work via class aliases registered by the package.

### Updating Your Code (Recommended)

While not required, updating to new namespaces is recommended for new development:

```php
// Before (v1.x)
use App\Models\CmsPage;
use App\Services\ThemeManager;

// After (v2.0)
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Services\ThemeManager;
```

---

## Database Migrations

v2.0 uses the same database schema as v1.x. No migration changes are required for existing installations.

If you need to re-run migrations or are installing fresh:

```bash
php artisan vendor:publish --tag=tallcms-migrations
php artisan migrate
```

---

## View Overrides

Custom view overrides should be moved to use the package namespace:

### Before (v1.x)
```
resources/views/cms/blocks/hero.blade.php
```

### After (v2.0)
```
resources/views/vendor/tallcms/cms/blocks/hero.blade.php
```

Publish views if you need to customize them:

```bash
php artisan vendor:publish --tag=tallcms-views
```

---

## Theme Compatibility

Themes from v1.x are compatible with v2.0. Ensure your `theme.json` includes:

```json
{
    "compatibility": {
        "tallcms": "^2.0"
    }
}
```

The package checks this version constraint before activating themes.

---

## Plugin Compatibility

Third-party plugins need to be updated for v2.0:

1. Update namespace references from `App\*` to `TallCms\Cms\*`
2. Update `plugin.json` compatibility constraint:

```json
{
    "compatibility": {
        "tallcms": "^2.0"
    }
}
```

---

## Configuration Changes

### New Configuration Keys

```php
// config/tallcms.php

// Mode detection (new)
'mode' => env('TALLCMS_MODE'), // 'standalone', 'plugin', or null (auto-detect)

// Plugin mode settings (new)
'plugin_mode' => [
    'routes_enabled' => env('TALLCMS_ROUTES_ENABLED', false),
    'routes_prefix' => env('TALLCMS_ROUTES_PREFIX'),
    'plugins_enabled' => env('TALLCMS_PLUGINS_ENABLED', true),
    'themes_enabled' => env('TALLCMS_THEMES_ENABLED', true),
    // ...
],

// Auth configuration (new)
'auth' => [
    'guard' => env('TALLCMS_AUTH_GUARD', 'web'),
    'login_route' => env('TALLCMS_LOGIN_ROUTE'),
],
```

### Renamed Configuration Keys

| v1.x | v2.0 |
|------|------|
| `tallcms.table_prefix` | `tallcms.database.prefix` |

---

## Breaking Changes

### Filament v4 Required

v2.0 requires Filament v4. If you're on Filament v3, you must upgrade Filament first.

### PHP 8.2+ Required

Minimum PHP version is now 8.2 (previously 8.1).

### Laravel 11+ Required

Minimum Laravel version is now 11 (previously 10).

### Removed Features in Plugin Mode

When running as a plugin (not standalone), these features are disabled:
- System Updates page (use Composer instead)
- One-click updates
- Full installer flow

---

## Troubleshooting

### "Class not found" Errors

If you see class not found errors after upgrading:

1. Clear all caches:
   ```bash
   php artisan optimize:clear
   composer dump-autoload
   ```

2. Verify the package is installed:
   ```bash
   composer show tallcms/cms
   ```

### Routes Not Working

If CMS routes aren't working:

1. Verify standalone marker exists: `.tallcms-standalone`
2. Check route configuration in `config/tallcms.php`
3. Clear route cache: `php artisan route:clear`

### Views Not Found

If views aren't found:

1. Clear view cache: `php artisan view:clear`
2. Verify view namespace is registered by checking service provider boot

### Dark Mode Issues

If dark mode styling is inconsistent:

1. Ensure your Filament panel uses the default theme
2. Clear compiled views: `php artisan view:clear`
3. Rebuild assets: `npm run build`

---

## Upgrading to v3.1: Plugin Config Consolidation

### `config/plugin.php` Removed

All plugin configuration now lives under `config('tallcms.plugins.*')` in the package config. The standalone `config/plugin.php` file has been removed.

**If you have a published `config/tallcms.php`**, re-publish to get the new `purchase_urls` and `download_urls` keys:

```bash
php artisan vendor:publish --tag=tallcms-config --force
```

### Deprecated Env Vars

The `PLUGIN_*` environment variables are deprecated in favor of `TALLCMS_PLUGIN_*`. Fallback support is included so existing `.env` files continue to work, but operators should migrate:

| Old (deprecated)         | New                              |
|--------------------------|----------------------------------|
| `PLUGIN_ALLOW_UPLOADS`   | `TALLCMS_PLUGIN_ALLOW_UPLOADS`   |
| `PLUGIN_MAX_UPLOAD_SIZE` | `TALLCMS_PLUGIN_MAX_UPLOAD_SIZE` |
| `PLUGIN_CACHE_ENABLED`   | `TALLCMS_PLUGIN_CACHE_ENABLED`   |
| `PLUGIN_AUTO_MIGRATE`    | `TALLCMS_PLUGIN_AUTO_MIGRATE`    |

---

## Getting Help

- **Documentation**: [tallcms.com/docs](https://tallcms.com/docs)
- **GitHub Issues**: [github.com/tallcms/cms/issues](https://github.com/tallcms/cms/issues)
- **Discord**: [discord.gg/tallcms](https://discord.gg/tallcms)
