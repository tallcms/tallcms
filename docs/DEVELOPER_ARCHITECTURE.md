# TallCMS v2.0 Developer Architecture Guide

> **Internal Documentation** - For TallCMS core developers

## Table of Contents

1. [Overview](#overview)
2. [Monorepo Structure](#monorepo-structure)
3. [The Single Source of Truth Principle](#the-single-source-of-truth-principle)
4. [Mode Detection](#mode-detection)
5. [Class Alias System](#class-alias-system)
6. [Extension Pattern](#extension-pattern)
7. [Configuration Management](#configuration-management)
8. [View Architecture](#view-architecture)
9. [Route Architecture](#route-architecture)
10. [Development Workflow](#development-workflow)
11. [Testing Strategy](#testing-strategy)
12. [Release Process](#release-process)
13. [Common Pitfalls](#common-pitfalls)

---

## Overview

TallCMS v2.0 uses a **monorepo architecture** where the package (`tallcms/cms`) lives inside the standalone application (`tallcms/tallcms`). This enables:

- Single codebase for both distribution modes
- Real-time package development with immediate feedback
- Consistent behavior across standalone and plugin modes

### Two Distribution Modes

| Aspect | Standalone Mode | Plugin Mode |
|--------|----------------|-------------|
| **Package** | `tallcms/tallcms` | `tallcms/cms` |
| **Installation** | `composer create-project` | `composer require` |
| **Target User** | New projects, non-technical users | Existing Filament apps |
| **Features** | Full CMS + themes + plugins + updates | CMS core + optional features |
| **Marker File** | `.tallcms-standalone` present | No marker file |

---

## Monorepo Structure

```
tallcms/tallcms (Standalone Application)
├── app/                          # Standalone-specific code
│   ├── Models/                   # Wrapper classes (extend package)
│   ├── Http/Controllers/         # Wrapper controllers
│   ├── Filament/                 # Wrapper resources/blocks
│   └── Providers/                # App-specific providers
├── config/
│   └── tallcms.php               # App-level overrides (minimal)
├── routes/
│   └── web.php                   # Standalone routes
├── resources/views/              # Standalone views
├── themes/                       # Installed themes
├── plugins/                      # Installed plugins
├── .tallcms-standalone           # MODE MARKER FILE
│
└── packages/tallcms/cms/         # THE PACKAGE (Source of Truth)
    ├── src/
    │   ├── Models/               # Base model implementations
    │   ├── Services/             # Core business logic
    │   ├── Filament/             # Resources, Pages, Blocks
    │   ├── Http/Controllers/     # Base controllers
    │   ├── Livewire/             # Livewire components
    │   └── TallCmsServiceProvider.php
    ├── config/
    │   └── tallcms.php           # MASTER CONFIG (comprehensive)
    ├── database/migrations/      # ALL CMS migrations live here
    ├── resources/views/          # Package views (tallcms:: namespace)
    ├── routes/
    │   └── frontend.php          # Frontend catch-all routes (plugin mode)
    └── tests/                    # Package tests
```

---

## The Single Source of Truth Principle

### DO NOT Duplicate Code

The package (`packages/tallcms/cms`) is the **single source of truth** for:

| Component | Location | Notes |
|-----------|----------|-------|
| **Models** | `packages/.../src/Models/` | All business logic here |
| **Services** | `packages/.../src/Services/` | Core functionality |
| **Filament Blocks** | `packages/.../src/Filament/Blocks/` | Block implementations |
| **Filament Resources** | `packages/.../src/Filament/Resources/` | CRUD interfaces |
| **Migrations** | `packages/.../database/migrations/` | Database schema |
| **Config** | `packages/.../config/tallcms.php` | Master configuration |
| **Package Views** | `packages/.../resources/views/` | `tallcms::` namespace |

### What Standalone Provides

The standalone app only provides:

1. **Wrapper Classes** - Thin extensions for backwards compatibility
2. **Mode Marker** - `.tallcms-standalone` file
3. **App-Specific Overrides** - Custom views, routes for standalone features
4. **Themes/Plugins Directories** - Runtime extension storage

### Example: Adding a New Feature

**WRONG** - Adding to both places:
```
# Don't do this!
app/Services/NewFeature.php           # Duplicate
packages/.../src/Services/NewFeature.php  # Duplicate
```

**CORRECT** - Add to package only:
```
# Add implementation to package
packages/tallcms/cms/src/Services/NewFeature.php

# Add alias in TallCmsServiceProvider.php (if App\ namespace needed)
'App\\Services\\NewFeature' => Services\NewFeature::class,
```

---

## Mode Detection

### How It Works

```php
// In TallCmsServiceProvider.php
protected function isStandaloneMode(): bool
{
    // 1. Explicit config takes priority
    if (config('tallcms.mode') !== null) {
        return config('tallcms.mode') === 'standalone';
    }

    // 2. Check for marker file
    return file_exists(base_path('.tallcms-standalone'));
}
```

### The Marker File

The `.tallcms-standalone` file:
- **Present** = Standalone mode (full features)
- **Absent** = Plugin mode (minimal footprint)

This file is:
- Included in `tallcms/tallcms` skeleton
- NOT included in `tallcms/cms` package
- Should NOT be committed to version control in user projects

---

## Class Alias System

The package creates 150+ class aliases to enable both namespaces:

```php
// In TallCmsServiceProvider.php
protected array $classAliases = [
    'App\\Models\\CmsPage' => Models\CmsPage::class,
    'App\\Models\\CmsPost' => Models\CmsPost::class,
    'App\\Services\\ThemeManager' => Services\ThemeManager::class,
    // ... 150+ more
];

protected function registerClassAliases(): void
{
    foreach ($this->classAliases as $alias => $original) {
        if (!class_exists($alias)) {
            class_alias($original, $alias);
        }
    }
}
```

### How Aliases Work

```php
// Both of these work identically:
$page = new \App\Models\CmsPage();
$page = new \TallCms\Cms\Models\CmsPage();

// Because of:
class_alias('TallCms\Cms\Models\CmsPage', 'App\Models\CmsPage');
```

### When to Add Aliases

Add an alias when:
1. Creating a new class that might be referenced as `App\*`
2. The class needs to work in both standalone and plugin mode
3. Backwards compatibility is required

---

## Extension Pattern

### Wrapper Classes in Standalone

Standalone provides thin wrapper classes for customization points:

```php
// app/Models/CmsPage.php (Standalone)
namespace App\Models;

use TallCms\Cms\Models\CmsPage as BaseCmsPage;

class CmsPage extends BaseCmsPage
{
    // Override or extend as needed
    // Most apps leave this empty
}
```

### When Wrappers Are Needed

| Scenario | Use Wrapper? | Reason |
|----------|-------------|--------|
| Standard usage | No | Alias handles it |
| App-specific customization | Yes | Override methods |
| Backwards compatibility | Yes | Existing code uses `App\` |
| Plugin mode | No | Use package classes directly |

### Filament Resource Extension

```php
// app/Filament/Pages/PluginManager.php (Standalone)
namespace App\Filament\Pages;

use TallCms\Cms\Filament\Pages\PluginManager as BasePluginManager;

class PluginManager extends BasePluginManager
{
    // Extend to customize plugin manager page
}
```

---

## Configuration Management

### Two Config Files, One Source of Truth

| File | Purpose | Scope |
|------|---------|-------|
| `packages/.../config/tallcms.php` | **Master config** | Comprehensive, all options |
| `config/tallcms.php` | App overrides | Minimal, standalone-specific |

### Master Config Structure (Package)

```php
// packages/tallcms/cms/config/tallcms.php
return [
    'version' => '2.0.0',

    'mode' => env('TALLCMS_MODE'),  // null = auto-detect

    'database' => [
        'prefix' => 'tallcms_',  // Note: 'prefix' not 'table_prefix'
    ],

    'plugin_mode' => [
        'routes_enabled' => false,        // Default: false (opt-in)
        'routes_prefix' => null,          // REQUIRED when routes_enabled=true
        'route_name_prefix' => 'tallcms.',// Route name prefix
        'themes_enabled' => false,        // Default: false (opt-in)
        'plugins_enabled' => false,       // Default: false (opt-in)
        'preview_routes_enabled' => true, // Essential routes on by default
        'api_routes_enabled' => true,     // Essential routes on by default
    ],

    'plugins' => [
        'catalog' => [
            'tallcms/pro' => [
                'name' => 'TallCMS Pro',
                'download_url' => '...',  // Required for download button
                'purchase_url' => '...',  // Required for purchase button
            ],
        ],
    ],

    // ... comprehensive settings
];
```

### Config Key Namespacing

**Always use `tallcms.` prefix** when reading config:

```php
// CORRECT
config('tallcms.plugins.catalog');
config('tallcms.plugin_mode.routes_enabled');

// WRONG - will fail in plugin mode
config('plugin.catalog');
config('routes_enabled');
```

---

## View Architecture

### View Namespaces

| Namespace | Location | Usage |
|-----------|----------|-------|
| `tallcms::` | Package views | Always use in package code |
| (none) | App views | Standalone-specific views |
| `vendor/tallcms/` | Published views | User customization |

### Always Use Namespace in Package

```php
// In package code (Blocks, Resources, etc.)
// CORRECT
return view('tallcms::cms.blocks.hero', $data);
return view('tallcms::filament.pages.plugin-manager');

// WRONG - will fail in plugin mode
return view('cms.blocks.hero', $data);
return view('filament.pages.plugin-manager');
```

### View Override Priority

1. `themes/{active}/resources/views/` (if themes enabled)
2. `resources/views/vendor/tallcms/` (published views)
3. `packages/.../resources/views/` (package defaults)

---

## Route Architecture

### Route Name Convention

**Package code must always use `tallcms.*` prefix:**

```php
// In package code - ALWAYS use tallcms.* prefix
Route::get('/preview/page/{id}', ...)->name('tallcms.preview.page');
route('tallcms.preview.page', ['id' => 1]);
```

**Standalone mode:** Main routes use `tallcms.*` names. Legacy aliases (e.g., `contact.submit` at `/api/contact`) exist for backwards compatibility at different URLs.

**Plugin mode:** All routes use `tallcms.*` names exclusively.

### Route Loading by Mode

**Standalone Mode:**
- Routes defined in app's `routes/web.php` (loaded by Laravel)
- Package routes are NOT loaded to avoid duplication

**Plugin Mode:**
- Essential routes registered directly in `TallCmsServiceProvider::loadEssentialRoutes()`
- Frontend routes loaded from `routes/frontend.php` when `routes_enabled=true`

```php
// Frontend routes (opt-in via config)
protected function loadPluginModeRoutes(): void
{
    if (config('tallcms.plugin_mode.routes_enabled', false)) {
        Route::middleware(['web', 'tallcms.maintenance'])
            ->prefix(config('tallcms.plugin_mode.routes_prefix'))
            ->group(__DIR__ . '/../routes/frontend.php');
    }
}
```

### Essential Routes (Always Loaded in Plugin Mode)

Essential routes are registered directly in the service provider (not from a routes file):

```php
protected function loadEssentialRoutes(): void
{
    // Preview routes (admin-only, for preview buttons)
    if (config('tallcms.plugin_mode.preview_routes_enabled', true)) {
        Route::get('/preview/page/{page}', ...)->name('tallcms.preview.page');
        Route::get('/preview/post/{post}', ...)->name('tallcms.preview.post');
        Route::get('/preview/share/{token}', ...)->name('tallcms.preview.token');
    }

    // Contact form API (for contact form blocks)
    if (config('tallcms.plugin_mode.api_routes_enabled', true)) {
        Route::post('/api/tallcms/contact', ...)->name('tallcms.contact.submit');
    }
}
```

---

## Development Workflow

### Initial Setup

```bash
# Clone the monorepo
git clone git@github.com:tallcms/tallcms.git
cd tallcms

# Install dependencies (symlinks package automatically)
composer install
npm install

# Setup database
php artisan migrate

# Start development
composer dev
```

### Making Package Changes

1. Edit files in `packages/tallcms/cms/`
2. Changes reflect immediately (symlinked)
3. Test in standalone mode first
4. Test in plugin mode using test project

### Testing in Plugin Mode

```bash
# In a separate test project
cd /path/to/test-project

# Add path repository to composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../tallcms/packages/tallcms/cms",
            "options": { "symlink": true }
        }
    ]
}

# Require the package
composer require tallcms/cms:@dev

# Register plugin in AdminPanelProvider
->plugin(TallCmsPlugin::make())

# Run migrations
php artisan migrate

# Clear caches after package changes
php artisan config:clear && php artisan cache:clear
```

### Package Test Suite

```bash
# Run package tests
cd packages/tallcms/cms
./vendor/bin/phpunit

# Or from root
composer test-package
```

---

## Testing Strategy

### Package Tests (`packages/tallcms/cms/tests/`)

| Test Type | Purpose |
|-----------|---------|
| `Unit/RouteNameConsistencyTest` | Ensures all routes use `tallcms.*` prefix |
| `Unit/ViewNamespaceConsistencyTest` | Ensures all views use `tallcms::` namespace |
| `Feature/ModeDetectionTest` | Tests standalone/plugin mode detection |
| `Feature/ConfigOptionsTest` | Tests config-driven feature toggles |
| `Feature/PluginModeIntegrationTest` | Tests package works in plugin mode |

### Standalone Tests (`tests/`)

Application-level integration tests for standalone mode.

### Test Both Modes

Before releasing:
1. Run package test suite
2. Run standalone test suite
3. Manual test in plugin mode (test project)

---

## Release Process

### 1. Version Bump

```php
// packages/tallcms/cms/config/tallcms.php
'version' => '2.1.0',

// packages/tallcms/cms/composer.json
"version": "2.1.0"
```

### 2. Run All Tests

```bash
# Package tests
cd packages/tallcms/cms && ./vendor/bin/phpunit

# Standalone tests
cd /path/to/tallcms && php artisan test

# Manual plugin mode test
cd /path/to/test-project && php artisan serve
```

### 3. Package Release (tallcms/cms)

```bash
# Tag the package (from monorepo root)
git tag cms-v2.1.0
git push origin cms-v2.1.0

# Or use split tool to push to separate repo
# The CI/CD splits packages/tallcms/cms to tallcms/cms repo
```

### 4. Standalone Release (tallcms/tallcms)

```bash
# Tag the full application
git tag v2.1.0
git push origin v2.1.0
```

### Versioning Strategy

| Package | Version | Notes |
|---------|---------|-------|
| `tallcms/cms` | `2.x.x` | Semver, package-only changes |
| `tallcms/tallcms` | `2.x.x` | Matches cms version |

---

## Common Pitfalls

### 1. Forgetting View Namespace

```php
// WRONG
return view('cms.blocks.hero');

// CORRECT
return view('tallcms::cms.blocks.hero');
```

**Fix**: Always use `tallcms::` prefix in package code.

### 2. Wrong Config Key

```php
// WRONG
$catalog = config('plugin.catalog');

// CORRECT
$catalog = config('tallcms.plugins.catalog');
```

**Fix**: Always use full `tallcms.*` path.

### 3. Unprefixed Route Names in Package Code

```php
// WRONG (in package code)
route('preview.page', $id);

// CORRECT (in package code)
route('tallcms.preview.page', $id);
```

**Fix**: Package code must always use `tallcms.*` route names to work in both modes.

**Note**: Standalone's `routes/web.php` may have legacy aliases (e.g., `contact.submit`) at different URLs for backwards compatibility. These are app-level, not package-level.

### 4. Duplicating Code in Standalone

```php
// WRONG - Adding feature to both places
app/Services/MyService.php
packages/.../src/Services/MyService.php

// CORRECT - Add to package, alias if needed
packages/tallcms/cms/src/Services/MyService.php
// + add alias in TallCmsServiceProvider if App\ namespace needed
```

### 5. Hardcoding App\ Namespace in Package

```php
// WRONG (in package code)
use App\Models\User;

// CORRECT (in package code)
use TallCms\Cms\Models\User;
// or
use Illuminate\Contracts\Auth\Authenticatable;
```

### 6. Forgetting to Clear Cache After Changes

```bash
# Always run after package changes in test project
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 7. Registering Blade Aliases Without Guards

```php
// WRONG - Overrides host app components
$blade->component('tallcms::components.menu', 'menu');

// CORRECT - Only when themes enabled
if (config('tallcms.plugin_mode.themes_enabled', false)) {
    $blade->component('tallcms::components.menu', 'menu');
}
```

### 8. Duplicate Route Definitions for "Aliases"

Laravel only keeps **one route name per URI pattern**. Re-registering the same URI overwrites the previous name.

```php
// WRONG - Second definition overwrites the first!
Route::get('/preview/page/{page}', ...)->name('tallcms.preview.page');
Route::get('/preview/page/{page}', ...)->name('preview.page'); // tallcms.* is now gone!

// CORRECT - One definition per URI, use service provider for aliases if needed
Route::get('/preview/page/{page}', ...)->name('tallcms.preview.page');
// Legacy aliases can be created via Route::aliasMiddleware or custom logic
```

**Fix**: Never re-register the same URI pattern with a different name. Use a single route name and implement proper aliasing in a service provider if backwards compatibility is needed.

### 9. Published Config Gets Stale

When users publish package config (`php artisan vendor:publish`), they get a **snapshot** that doesn't auto-update with package changes.

```php
// Package config updated with new 'download_url' field
// But user's published config/tallcms.php doesn't have it!
```

**Solutions**:
1. Document new config options in CHANGELOG
2. Use sensible defaults so missing keys don't break functionality
3. Consider API-based data for frequently changing content (e.g., plugin catalog)

---

## Quick Reference

### Adding a New Model

1. Create in `packages/tallcms/cms/src/Models/NewModel.php`
2. Add alias in `TallCmsServiceProvider::$classAliases`
3. Create migration in `packages/tallcms/cms/database/migrations/`
4. (Optional) Create wrapper in `app/Models/` for customization

### Adding a New Filament Block

1. Create in `packages/tallcms/cms/src/Filament/Blocks/NewBlock.php`
2. Create view in `packages/tallcms/cms/resources/views/cms/blocks/new-block.blade.php`
3. Add alias in `TallCmsServiceProvider::$classAliases`
4. Register in block registry

### Adding a New Config Option

1. Add to `packages/tallcms/cms/config/tallcms.php`
2. Use `config('tallcms.your.option')` to read
3. Document in README

### Adding a New Route

1. Add to `packages/tallcms/cms/routes/web.php`
2. Use `tallcms.` prefix for route name
3. Add to standalone `routes/web.php` if needed for backwards compatibility

---

## Questions?

For architecture questions, check:
- This document
- `packages/tallcms/cms/README.md`
- `CLAUDE.md` (AI assistant context)
- Open an issue on GitHub
