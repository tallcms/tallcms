---
title: "Plugin Development"
slug: "plugins"
audience: "developer"
category: "developers"
order: 20
time: 45
prerequisites:
  - "installation"
  - "block-development"
---

# Plugin Development Guide

This guide covers how to develop plugins for TallCMS. Plugins extend the CMS with new functionality including custom blocks, routes, admin pages, and more.

## Quick Start

### 1. Generate a Plugin

```bash
php artisan make:plugin "My Plugin" --vendor=acme --with-routes --with-filament --with-migration
```

This scaffolds the full directory structure, `plugin.json`, service provider, and optional Filament plugin class.

### 2. Or Create Manually

```
plugins/
└── acme/
    └── my-plugin/
        ├── plugin.json           # Required: Plugin manifest
        ├── src/
        │   ├── Providers/
        │   │   └── MyPluginServiceProvider.php
        │   └── Blocks/           # Optional: Custom blocks
        ├── resources/
        │   └── views/            # Optional: Blade templates
        ├── routes/
        │   ├── public.php        # Optional: Public routes (no prefix)
        │   ├── web.php           # Optional: Prefixed routes
        │   └── internal.php      # Optional: Internal routes (no web middleware)
        └── database/
            └── migrations/       # Optional: Database migrations
```

### 3. Clear the Cache

```bash
php artisan cache:clear
```

TallCMS discovers plugins automatically from the `plugins/` directory. No Composer require or service provider registration needed.

---

## plugin.json

### Required Fields

```json
{
    "name": "My Plugin",
    "slug": "my-plugin",
    "vendor": "acme",
    "version": "1.0.0",
    "description": "What this plugin does",
    "author": "Your Name",
    "namespace": "Acme\\MyPlugin",
    "provider": "Acme\\MyPlugin\\Providers\\MyPluginServiceProvider"
}
```

### Optional Fields

```json
{
    "compatibility": {
        "php": "^8.2",
        "tallcms": "^1.0 || ^2.0 || ^3.0"
    },
    "public_routes": ["/my-route", "/my-other-route"],
    "filament_plugin": "Acme\\MyPlugin\\Filament\\MyPlugin",
    "tags": ["forms", "free"],
    "license_required": false,
    "license": "MIT"
}
```

| Field | Description |
|-------|-------------|
| `compatibility.tallcms` | Semver constraint. Supports `\|\|` for multiple major versions. |
| `public_routes` | Whitelist of public route paths. Required if you use `routes/public.php`. Max 5. |
| `filament_plugin` | Filament plugin class. Only include if the class exists — the validator checks. |
| `license_required` | Set `true` for paid plugins that require a license key. |

### Vendor and Slug Rules

- Lowercase letters, numbers, and hyphens only
- Must start and end with a letter or number
- Max 64 characters each

---

## File Restrictions

TallCMS validates plugin files for security, both on disk and during ZIP upload. Understanding these rules will save you from confusing validation errors.

### Allowed PHP File Locations

PHP files are **only** allowed in these locations:

| Path | Notes |
|------|-------|
| `src/**/*.php` | All plugin source code |
| `database/migrations/*.php` | Flat only — no subdirectories |
| `routes/public.php` | Public routes (no prefix) |
| `routes/web.php` | Prefixed routes |
| `routes/internal.php` | Internal routes (no web middleware) |
| `resources/views/**/*.blade.php` | Blade templates only (`.blade.php` extension) |

**PHP files anywhere else will be rejected.** This includes `config/` — if your plugin needs configuration, define defaults inline in your service provider and let users create an app-level config file to override them:

```php
public function register(): void
{
    // Inline defaults — no config file needed in the plugin
    $defaults = [
        'enabled' => true,
        'max_items' => 10,
    ];

    // Merge app-level config/my-plugin.php if it exists
    $appConfig = config_path('my-plugin.php');
    if (file_exists($appConfig)) {
        $this->mergeConfigFrom($appConfig, 'my-plugin');
    }

    config(['my-plugin' => array_merge($defaults, config('my-plugin', []))]);
}
```

### Blocked Files and Directories

| Blocked | Reason |
|---------|--------|
| `vendor/` | Dependencies must not be bundled |
| `bootstrap/` | Reserved for Laravel |
| `.env`, `.env.*` | Environment files |
| `.htaccess` | Server config |
| Symlinks | Not allowed in plugins |

### ZIP Upload Limits

| Limit | Value |
|-------|-------|
| Max uncompressed size | 100 MB |
| Max file count | 5,000 |

---

## Routes

### Public Routes (No Prefix)

Public routes are served at the root URL (e.g., `/register`, `/webhook`). They require strict validation because they share the URL space with the host application.

**Rules:**

1. Every path must be declared in `plugin.json` under `public_routes`
2. Maximum **5 public routes** per plugin
3. Route files must use **flat `Route::get`/`Route::post` calls only**
4. Each route path must be unique — the parser counts `Route::` calls and compares to unique paths. If you need GET and POST on the same path, use distinct paths (e.g., `/form` for GET, `/form/submit` for POST)

**Forbidden in route files:**

- `Route::group()`, `Route::middleware()`, `Route::prefix()`, `Route::name()` as standalone calls
- `Route::any()`, `Route::match()`, `Route::resource()`, `Route::apiResource()`
- `Route::view()`, `Route::redirect()`, `Route::fallback()`
- `Route::domain()`, `Route::controller()`
- Router instance access (`app('router')`, `resolve('router')`, `$this->app['router']`)
- `require`/`include` statements
- Variable-based dispatch (`$class::get(...)`)

**Chaining is fine** — you can chain `->name()` and `->where()` on individual route definitions:

```php
// routes/public.php — this is correct
Route::get('/my-form', [FormController::class, 'show'])->name('form');
Route::post('/my-form/submit', [FormController::class, 'submit'])->name('submit');
```

**What the host adds automatically:**

The plugin system wraps your public routes with:
- Middleware: `['web', 'throttle:60,1']`
- Name prefix: `plugin.{vendor}.{slug}.`

So `->name('form')` becomes `plugin.acme.my-plugin.form`. If you need guest or auth guards, handle them in your controller — you cannot add middleware in the route file.

### Prefixed Routes

Routes in `routes/web.php` are automatically prefixed with `/_plugins/{vendor}/{slug}/`:

```php
// routes/web.php
Route::get('/settings', SettingsController::class)->name('settings');
// Accessible at: /_plugins/acme/my-plugin/settings
// Named: plugin.acme.my-plugin.settings
```

The same middleware and name prefix are applied. Prefixed routes do not need to be declared in `plugin.json`.

### Internal Routes

Routes in `routes/internal.php` have **no web middleware** — intended for machine-to-machine endpoints (webhooks, API callbacks). No prefix is applied.

### Dangerous Route Patterns

These patterns are blocked in all route files:

```php
// These will all be rejected:
Route::fallback(...)           // Would catch all unmatched URLs
Route::any('/', ...)           // Would hijack the homepage
Route::get('/', ...)           // Would hijack the homepage
Route::domain(...)             // Would affect other domains
```

---

## Service Provider

### What You Can Do

```php
public function register(): void
{
    // Merge config, bind services, register singletons
}

public function boot(): void
{
    // Load views
    $this->loadViewsFrom(__DIR__.'/../../resources/views', 'acme-myplugin');

    // Load migrations
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

    // Register event listeners
    Event::listen(SomeEvent::class, SomeListener::class);
}
```

### What You Cannot Do

**No route registration in service providers.** All routes must be in route files. The validator scans your provider (and all `src/` files) for these patterns and rejects the plugin if found:

- `Route::` calls of any kind
- `app('router')`, `resolve('router')`, `App::make('router')`
- `$this->app['router']`, `$this->app->make('router')`
- `Illuminate\Routing\Router` class usage
- `Illuminate\Contracts\Routing\Registrar` usage
- Aliased Route facades (`use ... Route as R; R::get(...)`)
- Dynamic dispatch (`Route::class`, `call_user_func(...Route...)`)

This is enforced in all PHP files under `src/`, not just the provider.

---

## Plugin Configuration

### Version Constraints

The `compatibility.tallcms` field uses Composer-style semver. Use `||` to support multiple major versions:

```json
{
    "compatibility": {
        "tallcms": "^1.0 || ^2.0 || ^3.0"
    }
}
```

If omitted, no version check is performed. If present, the plugin won't load if the constraint isn't satisfied.

### Filament Plugin Class

If you include `filament_plugin` in your manifest, the class **must exist** — the validator checks after autoloading. If you don't need admin panel integration, omit the key entirely rather than pointing to a nonexistent class.

---

## Custom Blocks

Blocks in `src/Blocks/` implementing `CustomBlockInterface` are auto-discovered:

```php
<?php

namespace Acme\MyPlugin\Blocks;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use TallCms\Cms\Contracts\CustomBlockInterface;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class MyBlock extends RichContentCustomBlock implements CustomBlockInterface
{
    use HasBlockMetadata;

    public static function getId(): string
    {
        return 'my-block';
    }

    public static function getLabel(): string
    {
        return 'My Block';
    }

    public static function getBlockName(): string
    {
        return 'my-block';
    }

    public static function getBlockLabel(): string
    {
        return 'My Block';
    }

    public static function getBlockIcon(): string
    {
        return 'heroicon-o-star';
    }

    public static function getBlockSchema(): array
    {
        return [
            // Filament form schema
        ];
    }

    public static function getViewName(): string
    {
        return 'acme-plugin::blocks.my-block';
    }
}
```

---

## Filament Integration

### Creating a Filament Plugin

```php
<?php

namespace Acme\MyPlugin\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MyPlugin implements Plugin
{
    public function getId(): string
    {
        return 'acme-my-plugin';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                // Your resources
            ])
            ->pages([
                // Your pages
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Bootstrap code
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
```

Reference this in `plugin.json`:

```json
{
    "filament_plugin": "Acme\\MyPlugin\\Filament\\MyPlugin"
}
```

TallCMS also benefits from the broader [Filament plugin ecosystem](filament-ecosystem) — any Filament community plugin works alongside TallCMS plugins in the admin panel.

---

## Views and Assets

### Loading Views

```php
public function boot(): void
{
    $this->loadViewsFrom(__DIR__.'/../../resources/views', 'acme-plugin');
}
```

### Using Views

```php
return view('acme-plugin::blocks.my-block', $data);
```

### Theme Override Support

Themes can override plugin views at:

```
themes/{theme}/resources/views/vendor/{view-namespace}/
```

The plugin system prepends the theme path automatically, so theme views take precedence.

---

## Migrations

Place migrations in `database/migrations/` — **flat structure only**, no subdirectories:

```php
// database/migrations/2024_01_01_000000_create_my_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acme_my_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acme_my_table');
    }
};
```

Migrations run automatically when `tallcms.plugins.auto_migrate` is `true` (the default).

---

## Testing Your Plugin

### Directory Structure

```
plugins/acme/my-plugin/
└── tests/
    ├── Feature/
    │   └── BlockTest.php
    └── Unit/
        └── HelperTest.php
```

### Running Tests

```bash
cd plugins/acme/my-plugin
./vendor/bin/phpunit
```

---

## Distribution

### Via Composer

Publish to Packagist:

```json
{
    "name": "acme/tallcms-contact-forms",
    "type": "tallcms-plugin",
    "require": {
        "tallcms/cms": "^1.0"
    }
}
```

### Via ZIP Upload

Package as ZIP and upload through the admin panel (if enabled). The ZIP is validated against all the rules described in [File Restrictions](#file-restrictions) before extraction. Common rejection reasons:

- PHP files outside `src/`, `database/migrations/`, or route files (e.g., `config/*.php`)
- Version constraint that doesn't match the installed TallCMS version
- Route files using forbidden methods like `Route::group()`

---

## Common Pitfalls

**"Invalid plugin package — forbidden file"**
PHP files are only allowed in `src/`, `database/migrations/`, and the three route files. Move config defaults inline into your service provider. Blade templates must use the `.blade.php` extension and live in `resources/views/`.

**"Plugin requires TallCMS ^1.0, current version is X.Y.Z"**
Widen your version constraint: `"tallcms": "^1.0 || ^2.0 || ^3.0"`.

**"Detected Route:: calls that could not be parsed"**
The route parser counts `Route::` calls and compares to unique paths. If you have GET and POST on the same path (e.g., two calls to `/form`), the count won't match. Use distinct paths: `/form` (GET) and `/form/submit` (POST).

**"Route registration found in src/"**
All routes must be in route files, not in service providers or other `src/` classes. The validator scans all PHP files in `src/` for Route facade usage.

**"Filament plugin class not found"**
Either create the class or remove `filament_plugin` from `plugin.json`. The validator only checks if the key is present.

**"Plugin not loading"**
Check that `plugin.json` is valid JSON, the provider class exists, and the cache is cleared (`php artisan cache:clear`).

**"Routes not working"**
Public routes must be declared in `public_routes` in `plugin.json`. The paths must exactly match what's in the route file.

**"Block not appearing"**
Implement `CustomBlockInterface` and use the `HasBlockMetadata` trait.

**"Views not found"**
Verify view namespace registration in your service provider's `boot()` method.

---

## Next Steps

- [Block development](block-development)
- [Theme development](themes)
- [Architecture reference](architecture)
- [Filament plugin ecosystem](filament-ecosystem) — use any of the hundreds of Filament community plugins alongside TallCMS plugins
