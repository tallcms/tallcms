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

### 1. Create Plugin Directory Structure

```
plugins/
└── your-vendor/
    └── your-plugin/
        ├── plugin.json           # Required: Plugin metadata
        ├── src/
        │   ├── Providers/
        │   │   └── YourPluginServiceProvider.php
        │   └── Blocks/           # Optional: Custom blocks
        │       └── ExampleBlock.php
        ├── resources/
        │   └── views/
        │       └── blocks/
        │           └── example.blade.php
        ├── routes/
        │   ├── public.php        # Optional: Public routes
        │   └── web.php           # Optional: Prefixed routes
        └── database/
            └── migrations/       # Optional: Database migrations
```

### 2. Create plugin.json

```json
{
    "name": "Your Plugin",
    "slug": "your-plugin",
    "vendor": "your-vendor",
    "version": "1.0.0",
    "description": "A brief description of your plugin",
    "author": "Your Name",
    "namespace": "YourVendor\\YourPlugin",
    "provider": "YourVendor\\YourPlugin\\Providers\\YourPluginServiceProvider",
    "compatibility": {
        "php": "^8.2",
        "tallcms": "^1.0"
    }
}
```

### 3. Create Service Provider

```php
<?php

namespace YourVendor\YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings, config, etc.
    }

    public function boot(): void
    {
        // Bootstrap your plugin
        // Load views, register components, etc.
    }
}
```

---

## Plugin Structure

### Required Files

| File | Description |
|------|-------------|
| `plugin.json` | Plugin metadata and configuration |
| `src/Providers/*ServiceProvider.php` | Service provider class |

### Optional Directories

| Directory | Purpose |
|-----------|---------|
| `src/Blocks/` | Custom content blocks |
| `src/Filament/` | Filament resources, pages, widgets |
| `resources/views/` | Blade templates |
| `routes/` | Route definitions |
| `database/migrations/` | Database migrations |
| `config/` | Configuration files |

---

## Plugin Configuration

### Complete plugin.json Example

```json
{
    "name": "Contact Forms Pro",
    "slug": "contact-forms-pro",
    "vendor": "acme",
    "version": "1.2.0",
    "description": "Advanced contact forms with conditional logic",
    "author": "Acme Inc.",
    "author_url": "https://acme.com",
    "namespace": "Acme\\ContactFormsPro",
    "provider": "Acme\\ContactFormsPro\\Providers\\ContactFormsProServiceProvider",
    "compatibility": {
        "php": "^8.2",
        "tallcms": "^1.0"
    },
    "public_routes": ["/form-submit"],
    "filament_plugin": "Acme\\ContactFormsPro\\Filament\\ContactFormsProPlugin"
}
```

### Configuration Fields

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Display name |
| `slug` | Yes | URL-safe identifier |
| `vendor` | Yes | Vendor/organization name |
| `version` | Yes | Semantic version |
| `namespace` | Yes | PHP namespace |
| `provider` | Yes | Service provider class |
| `compatibility` | No | PHP/TallCMS version requirements |
| `public_routes` | No | Whitelisted public routes |
| `filament_plugin` | No | Filament plugin class |

---

## Routes

### Public Routes (No Prefix)

Public routes require explicit whitelisting in `plugin.json`:

```json
{
    "public_routes": ["/form-submit", "/webhook"]
}
```

```php
// routes/public.php
Route::post('/form-submit', FormController::class)->name('form.submit');
```

### Prefixed Routes (Auto-Prefixed)

Routes in `routes/web.php` are automatically prefixed with `/_plugins/{vendor}/{slug}/`:

```php
// routes/web.php
Route::get('/dashboard', DashboardController::class)->name('dashboard');
// Accessible at: /_plugins/acme/contact-forms-pro/dashboard
```

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

---

## Migrations

Place migrations in `database/migrations/`:

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

---

## Security Guidelines

### Service Provider Restrictions

The following are blocked in service providers:
- `Route::` facade calls
- Direct router access
- Route aliasing

### Route File Restrictions

Blocked in route files:
- `Route::any`
- `Route::resource`
- `Route::group`
- Router instance variables

### Namespace Restrictions

Plugins cannot use these namespaces:
- `App\`, `Database\`, `Tests\`
- `Illuminate\`, `Laravel\`, `Filament\`
- `Livewire\`, `Spatie\`

---

## Testing Your Plugin

### Directory Structure

```
plugins/your-vendor/your-plugin/
└── tests/
    ├── Feature/
    │   └── BlockTest.php
    └── Unit/
        └── HelperTest.php
```

### Running Tests

```bash
cd plugins/your-vendor/your-plugin
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

Package as ZIP and distribute through admin panel upload (if enabled).

---

## Common Pitfalls

**"Plugin not loading"**
Check `plugin.json` is valid JSON and provider class exists.

**"Routes not working"**
Public routes must be whitelisted in `public_routes`. Prefixed routes use `/_plugins/` path.

**"Block not appearing"**
Implement `CustomBlockInterface` and use the `HasBlockMetadata` trait.

**"Views not found"**
Verify view namespace registration in service provider.

---

## Next Steps

- [Block development](block-development)
- [Theme development](themes)
- [Architecture reference](architecture)
