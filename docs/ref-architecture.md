---
title: "Developer Architecture"
slug: "architecture"
audience: "developer"
category: "reference"
order: 50
---

# TallCMS Developer Architecture Guide

Internal architecture reference for TallCMS core developers.

---

## Overview

TallCMS v2.0 uses a **monorepo architecture** where the package (`tallcms/cms`) lives inside the standalone application (`tallcms/tallcms`).

### Two Distribution Modes

| Aspect | Standalone Mode | Plugin Mode |
|--------|----------------|-------------|
| **Package** | `tallcms/tallcms` | `tallcms/cms` |
| **Installation** | `composer create-project` | `composer require` |
| **Target User** | New projects | Existing Filament apps |
| **Features** | Full CMS + themes + plugins | CMS core |
| **Marker File** | `.tallcms-standalone` present | No marker file |

---

## Monorepo Structure

```
tallcms/tallcms (Standalone Application)
├── app/                          # Standalone-specific code
│   ├── Models/                   # Wrapper classes
│   ├── Http/Controllers/         # Wrapper controllers
│   ├── Filament/                 # Wrapper resources
│   └── Providers/                # App-specific providers
├── config/
│   └── tallcms.php               # App-level overrides
├── routes/
│   └── web.php                   # Standalone routes
├── resources/views/              # Standalone views
├── themes/                       # Installed themes
├── plugins/                      # Installed plugins
├── .tallcms-standalone           # MODE MARKER FILE
│
└── packages/tallcms/cms/         # THE PACKAGE (Source of Truth)
    ├── src/
    │   ├── Models/               # Base models
    │   ├── Services/             # Core logic
    │   ├── Filament/             # Resources, Blocks
    │   ├── Http/Controllers/     # Base controllers
    │   └── TallCmsServiceProvider.php
    ├── config/
    │   └── tallcms.php           # MASTER CONFIG
    ├── database/migrations/      # ALL migrations
    ├── resources/views/          # Package views
    └── routes/
        └── frontend.php          # Frontend routes
```

---

## Single Source of Truth Principle

The package is the **single source of truth** for:

| Component | Location |
|-----------|----------|
| Models | `packages/.../src/Models/` |
| Services | `packages/.../src/Services/` |
| Filament Blocks | `packages/.../src/Filament/Blocks/` |
| Filament Resources | `packages/.../src/Filament/Resources/` |
| Migrations | `packages/.../database/migrations/` |
| Config | `packages/.../config/tallcms.php` |
| Package Views | `packages/.../resources/views/` |

### Standalone Provides

1. **Wrapper Classes** - Thin extensions for compatibility
2. **Standalone Features** - Themes, plugins, updates
3. **Config Overrides** - Minimal environment-specific changes
4. **Additional Views** - Welcome page, theme layouts

---

## Mode Detection

```php
// In TallCmsServiceProvider
protected function detectMode(): string
{
    if (file_exists(base_path('.tallcms-standalone'))) {
        return 'standalone';
    }
    return 'plugin';
}
```

### Helper Function

```php
tallcms_mode(): string  // 'standalone' or 'plugin'
tallcms_is_standalone(): bool
tallcms_is_plugin(): bool
```

---

## Class Alias System

Standalone wrappers extend package classes:

```php
// app/Models/CmsPage.php (Standalone)
namespace App\Models;

use TallCms\Cms\Models\CmsPage as BaseCmsPage;

class CmsPage extends BaseCmsPage
{
    // Standalone-specific additions
}
```

The service provider registers aliases:

```php
$this->app->alias(
    \App\Models\CmsPage::class,
    \TallCms\Cms\Models\CmsPage::class
);
```

---

## Extension Pattern

### Adding Standalone Features

```php
// In standalone service provider
if (tallcms_is_standalone()) {
    $this->registerThemeManager();
    $this->registerPluginManager();
    $this->registerUpdateService();
}
```

### Conditional Routes

```php
// routes/web.php (Standalone)
if (tallcms_is_standalone()) {
    Route::get('/themes', ThemeController::class);
}
```

---

## Configuration Management

### Package Config (Master)

`packages/tallcms/cms/config/tallcms.php`:

```php
return [
    'mode' => env('TALLCMS_MODE'),  // null = auto-detect
    'version' => '2.5.0',

    'plugin_mode' => [
        'routes_enabled' => env('TALLCMS_ROUTES_ENABLED', false),
        'routes_prefix' => env('TALLCMS_ROUTES_PREFIX', ''),
    ],

    // Comprehensive defaults...
];
```

### App Config (Overrides)

`config/tallcms.php` (Standalone):

```php
return [
    // Only override what's different
    'mode' => 'standalone',
];
```

---

## View Architecture

### View Namespaces

| Namespace | Path | Priority |
|-----------|------|----------|
| (none) | `resources/views/` | 1 (highest) |
| `tallcms` | `packages/.../views/` | 2 |
| theme | `themes/{slug}/views/` | 0 (with theme) |

### Resolution Order

1. Active theme views
2. Application views
3. Package views (fallback)

```php
// Always falls back correctly
return view('tallcms::cms.blocks.hero');
```

---

## Route Architecture

### Package Routes

```php
// TallCmsServiceProvider
$this->loadRoutesFrom(__DIR__.'/../routes/frontend.php');
```

### Route Groups

| Group | Middleware | Prefix |
|-------|------------|--------|
| Frontend | `web` | (configurable) |
| Admin | `web, auth` | `/admin` |
| API | `api` | `/api/tallcms` |

### Plugin Mode Routes

```php
if (config('tallcms.plugin_mode.routes_enabled')) {
    Route::middleware('web')
        ->prefix(config('tallcms.plugin_mode.routes_prefix'))
        ->group(function () {
            // CMS routes
        });
}
```

---

## Development Workflow

### Working on Package

```bash
cd packages/tallcms/cms
# Make changes
# Test immediately in standalone app
```

### Testing Both Modes

```bash
# Standalone mode
php artisan test

# Plugin mode simulation
rm .tallcms-standalone
php artisan test
touch .tallcms-standalone
```

---

## Testing Strategy

### Package Tests

```bash
cd packages/tallcms/cms
./vendor/bin/phpunit
```

### Test Isolation

```php
// Base test case ensures clean state
protected function setUp(): void
{
    parent::setUp();
    config(['tallcms.mode' => null]);  // Auto-detect
}
```

---

## Release Process

1. Update version in `packages/tallcms/cms/config/tallcms.php`
2. Update version in `packages/tallcms/cms/composer.json`
3. Tag monorepo
4. GitHub Action splits to `tallcms/cms`
5. Packagist picks up new tag

---

## Common Pitfalls

### DO NOT

- Duplicate code between standalone and package
- Put business logic in standalone `app/`
- Reference standalone classes from package
- Hard-code paths without mode awareness

### DO

- Extend package classes in standalone
- Use config for mode-specific behavior
- Keep package self-contained
- Test both modes

---

## Key Files Reference

| Purpose | Package Location | Standalone Override |
|---------|------------------|---------------------|
| Service Provider | `src/TallCmsServiceProvider.php` | `app/Providers/TallCmsProvider.php` |
| Config | `config/tallcms.php` | `config/tallcms.php` |
| Routes | `routes/frontend.php` | `routes/web.php` |
| Base Models | `src/Models/` | `app/Models/` |
| Views | `resources/views/` | `resources/views/` |

---

## Next Steps

- [Plugin development](plugins)
- [Theme development](themes)
- [Block development](block-development)
