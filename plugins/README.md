# TallCMS Plugins

This directory contains installed plugins for TallCMS.

## Directory Structure

```
plugins/
├── {vendor}/
│   └── {plugin-slug}/
│       ├── plugin.json          # Required: Plugin metadata
│       ├── src/                  # PHP source code
│       │   ├── Providers/        # Service providers
│       │   ├── Blocks/           # Custom blocks
│       │   └── Filament/         # Filament resources
│       ├── resources/views/      # Blade templates
│       ├── routes/               # Route definitions
│       │   ├── public.php        # Public routes (whitelisted)
│       │   └── web.php           # Prefixed routes
│       └── database/migrations/  # Database migrations
```

## Quick Start

### 1. Create Plugin Directory

```bash
mkdir -p plugins/your-vendor/your-plugin/src/Providers
```

### 2. Create plugin.json

```json
{
    "name": "Your Plugin",
    "slug": "your-plugin",
    "vendor": "your-vendor",
    "version": "1.0.0",
    "description": "What your plugin does",
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
// src/Providers/YourPluginServiceProvider.php

namespace YourVendor\YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'your-vendor-your-plugin');
    }
}
```

## Adding Routes

### Public Routes (No Prefix)

1. Add routes to `plugin.json`:
   ```json
   { "public_routes": ["/my-route"] }
   ```

2. Create `routes/public.php`:
   ```php
   Route::get('/my-route', fn() => 'Hello!')->name('my-route');
   ```

### Prefixed Routes

Create `routes/web.php` (auto-prefixed with `/_plugins/{vendor}/{slug}/`):
```php
Route::get('/dashboard', fn() => view('...'))->name('dashboard');
```

## Adding Blocks

1. Create block class in `src/Blocks/YourBlock.php`
2. Implement `App\Contracts\CustomBlockInterface`
3. Create view in `resources/views/blocks/your-block.blade.php`

Blocks are auto-discovered from `src/Blocks/`.

## Security Rules

**Not Allowed:**
- Route registration in service providers
- `Route::any`, `Route::resource`, `Route::group` in public routes
- Router instance access (`app('router')`, etc.)
- Namespaces starting with `App\`, `Illuminate\`, etc.

**Allowed:**
- Simple routes: `Route::get`, `Route::post`, etc.
- Route chaining: `Route::get(...)->name(...)->middleware(...)`

## Full Documentation

See [docs/PLUGIN_DEVELOPMENT.md](/docs/PLUGIN_DEVELOPMENT.md) for complete documentation.
