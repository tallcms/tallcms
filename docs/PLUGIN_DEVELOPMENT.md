# Plugin Development Guide

This guide covers how to develop plugins for TallCMS. Plugins extend the CMS with new functionality including custom blocks, routes, admin pages, and more.

## Table of Contents

- [Quick Start](#quick-start)
- [Plugin Structure](#plugin-structure)
- [Plugin Configuration (plugin.json)](#plugin-configuration)
- [Service Provider](#service-provider)
- [Routes](#routes)
- [Custom Blocks](#custom-blocks)
- [Filament Integration](#filament-integration)
- [Migrations](#migrations)
- [Views and Assets](#views-and-assets)
- [Theme Override Support](#theme-override-support)
- [Security Guidelines](#security-guidelines)
- [Testing Your Plugin](#testing-your-plugin)
- [Distribution](#distribution)

---

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
| `src/` | PHP source code (PSR-4 autoloaded) |
| `src/Blocks/` | Custom block classes |
| `src/Filament/` | Filament resources, pages, widgets |
| `resources/views/` | Blade templates |
| `routes/` | Route definitions |
| `database/migrations/` | Database migrations |
| `config/` | Configuration files |

---

## Plugin Configuration

The `plugin.json` file defines your plugin's metadata and behavior.

### Required Fields

```json
{
    "name": "Human-readable Plugin Name",
    "slug": "plugin-slug",
    "vendor": "vendor-name",
    "version": "1.0.0",
    "description": "What your plugin does",
    "author": "Author Name",
    "namespace": "Vendor\\PluginName",
    "provider": "Vendor\\PluginName\\Providers\\PluginServiceProvider"
}
```

### Optional Fields

```json
{
    "tags": ["category", "feature"],
    "author_url": "https://yourwebsite.com",
    "license": "MIT",
    "homepage": "https://plugin-homepage.com",

    "compatibility": {
        "php": "^8.2",
        "tallcms": "^1.0",
        "extensions": ["gd", "curl"]
    },

    "filament_plugin": "Vendor\\PluginName\\Filament\\PluginNamePlugin",

    "public_routes": ["/your-route", "/another-route"],

    "view_namespace": "custom-namespace",

    "screenshots": {
        "primary": "screenshot.png",
        "gallery": ["screenshot-1.png", "screenshot-2.png"]
    }
}
```

### Field Reference

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Display name for the plugin |
| `slug` | string | URL-safe identifier (lowercase, hyphens only) |
| `vendor` | string | Your vendor/organization name |
| `version` | string | Semantic version (e.g., "1.0.0") |
| `description` | string | Brief description of functionality |
| `author` | string | Author or organization name |
| `namespace` | string | PSR-4 namespace for autoloading |
| `provider` | string | Fully qualified service provider class |
| `tags` | array | Categorization tags |
| `compatibility.php` | string | Required PHP version constraint |
| `compatibility.tallcms` | string | Required TallCMS version |
| `compatibility.extensions` | array | Required PHP extensions |
| `filament_plugin` | string | Filament plugin class (optional) |
| `public_routes` | array | Whitelisted public route paths |
| `view_namespace` | string | Custom view namespace (default: vendor-slug) |

---

## Service Provider

Your service provider is the entry point for your plugin. It's registered and booted automatically when TallCMS loads.

### Basic Provider

```php
<?php

namespace YourVendor\YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/your-plugin.php',
            'your-plugin'
        );
    }

    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(
            __DIR__.'/../../resources/views',
            'your-plugin'
        );

        // Publish assets (optional)
        $this->publishes([
            __DIR__.'/../../config/your-plugin.php' => config_path('your-plugin.php'),
        ], 'your-plugin-config');
    }
}
```

### Important Restrictions

Your service provider **MUST NOT**:

- Register routes directly (use route files instead)
- Use the Route facade
- Access the router instance
- Import or use `Illuminate\Routing\Router`

See [Security Guidelines](#security-guidelines) for details.

---

## Routes

Plugins can define two types of routes:

### Public Routes (`routes/public.php`)

Public routes are loaded **without a URL prefix** but require explicit whitelisting in `plugin.json`.

**plugin.json:**
```json
{
    "public_routes": ["/hello", "/hello/world"]
}
```

**routes/public.php:**
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return view('your-plugin::hello');
})->name('hello');

Route::get('/hello/world', function () {
    return 'Hello World!';
})->name('hello.world');
```

**Restrictions for public routes:**
- Maximum 5 public routes per plugin
- Must be declared in `public_routes` array
- Cannot use route parameters (`{id}`)
- Cannot use wildcards (`*`)
- Cannot start with `/admin`, `/api`, or `/install`
- Only `Route::get`, `post`, `put`, `patch`, `delete`, `options` allowed

### Prefixed Routes (`routes/web.php`)

Prefixed routes are automatically namespaced under `/_plugins/{vendor}/{slug}/`.

**routes/web.php:**
```php
<?php

use Illuminate\Support\Facades\Route;

// Accessible at: /_plugins/your-vendor/your-plugin/dashboard
Route::get('/dashboard', function () {
    return view('your-plugin::dashboard');
})->name('dashboard');

// Accessible at: /_plugins/your-vendor/your-plugin/settings
Route::get('/settings', function () {
    return view('your-plugin::settings');
})->name('settings');
```

### Route Naming

All plugin routes are automatically prefixed with `plugin.{vendor}.{slug}.`:

```php
// In routes/public.php with name('hello')
// Full name: plugin.your-vendor.your-plugin.hello

route('plugin.your-vendor.your-plugin.hello'); // Returns URL
```

---

## Custom Blocks

Plugins can provide custom blocks for the page/post editor.

### Block Class

Create a block class in `src/Blocks/`:

```php
<?php

namespace YourVendor\YourPlugin\Blocks;

use App\Contracts\CustomBlockInterface;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ExampleBlock implements CustomBlockInterface
{
    public static function getBlockName(): string
    {
        return 'example';
    }

    public static function getBlockLabel(): string
    {
        return 'Example Block';
    }

    public static function getBlockIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getBlockSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Title')
                ->required(),
            Textarea::make('content')
                ->label('Content')
                ->rows(4),
            Select::make('style')
                ->label('Style')
                ->options([
                    'default' => 'Default',
                    'highlight' => 'Highlighted',
                ])
                ->default('default'),
        ];
    }

    public static function getViewName(): string
    {
        // View at: resources/views/blocks/example.blade.php
        return 'your-vendor-your-plugin::blocks.example';
    }
}
```

### Block View

Create the corresponding view at `resources/views/blocks/example.blade.php`:

```blade
@props(['title' => '', 'content' => '', 'style' => 'default'])

<div class="example-block example-block--{{ $style }}">
    @if($title)
        <h2 class="text-2xl font-bold mb-4">{{ $title }}</h2>
    @endif

    @if($content)
        <div class="prose">
            {!! nl2br(e($content)) !!}
        </div>
    @endif
</div>
```

### Block Discovery

Blocks are automatically discovered from your plugin's `src/Blocks/` directory. No additional registration is required.

---

## Filament Integration

Plugins can integrate with the Filament admin panel.

### Filament Plugin Class

Create a Filament plugin class to register resources, pages, and widgets:

```php
<?php

namespace YourVendor\YourPlugin\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class YourPluginPlugin implements Plugin
{
    public function getId(): string
    {
        return 'your-vendor-your-plugin';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                // Resources\YourResource::class,
            ])
            ->pages([
                // Pages\YourPage::class,
            ])
            ->widgets([
                // Widgets\YourWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

**Register in plugin.json:**
```json
{
    "filament_plugin": "YourVendor\\YourPlugin\\Filament\\YourPluginPlugin"
}
```

### Creating Resources

```php
<?php

namespace YourVendor\YourPlugin\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;

class ExampleResource extends Resource
{
    protected static ?string $model = \YourVendor\YourPlugin\Models\Example::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Your Plugin';

    // ... standard Filament resource implementation
}
```

---

## Migrations

Database migrations should be placed in `database/migrations/`.

### Migration Naming

Use the TallCMS table prefix convention:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use underscores, not hyphens in table names
        Schema::create('tallcms_yourplugin_examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallcms_yourplugin_examples');
    }
};
```

### Important Notes

- Table names should use underscores, not hyphens
- Prefix tables with `tallcms_yourplugin_` to avoid conflicts
- Migrations run automatically on plugin install
- Migrations are rolled back on plugin uninstall

---

## Views and Assets

### View Namespace

Plugin views are registered under the namespace `{vendor}-{slug}`:

```php
// In your plugin code
return view('your-vendor-your-plugin::pages.index');

// Also available as
return view('plugin.your-vendor.your-plugin::pages.index');
```

### Loading Views in Provider

```php
public function boot(): void
{
    $this->loadViewsFrom(
        __DIR__.'/../../resources/views',
        'your-vendor-your-plugin'
    );
}
```

### Directory Structure

```
resources/
└── views/
    ├── blocks/
    │   └── example.blade.php
    ├── pages/
    │   └── index.blade.php
    └── components/
        └── card.blade.php
```

---

## Theme Override Support

Themes can override plugin views. Plugin developers should design views to be override-friendly.

### How It Works

Themes can override any plugin view by creating a file at:
```
themes/{theme-slug}/resources/views/vendor/{plugin-view-namespace}/
```

### Example

For a plugin with view namespace `acme-analytics`:

**Plugin view:** `plugins/acme/analytics/resources/views/blocks/chart.blade.php`

**Theme override:** `themes/my-theme/resources/views/vendor/acme-analytics/blocks/chart.blade.php`

### Best Practices for Override-Friendly Views

1. **Use CSS classes** for styling instead of inline styles
2. **Accept configuration via props** rather than hardcoding values
3. **Document available slots and props** for theme developers
4. **Keep views modular** - break complex views into components

---

## Security Guidelines

TallCMS enforces strict security rules for plugins to prevent malicious code execution.

### Forbidden Patterns

The following patterns are **blocked** and will prevent plugin installation:

#### In Service Providers

```php
// FORBIDDEN - Direct route registration
Route::get('/path', ...);

// FORBIDDEN - Aliased Route facade
use Illuminate\Support\Facades\Route as R;
R::get('/path', ...);

// FORBIDDEN - Router instance access
app('router')->get('/path', ...);
$this->app['router']->get('/path', ...);
resolve('router')->get('/path', ...);

// FORBIDDEN - Router class usage
use Illuminate\Routing\Router;
app(Router::class)->get('/path', ...);
```

#### In Route Files

```php
// FORBIDDEN - Route grouping/middleware chaining
Route::middleware('auth')->group(function() { ... });
Route::prefix('admin')->group(function() { ... });

// FORBIDDEN - Catch-all and dangerous routes
Route::any('/', ...);
Route::fallback(...);
Route::domain(...);

// FORBIDDEN - Resource routes in public.php
Route::resource('posts', PostController::class);

// FORBIDDEN - Router instance in route files
$router->get('/path', ...);
app('router')->get('/path', ...);
```

### Allowed Patterns

```php
// ALLOWED - Simple route definitions in route files
Route::get('/path', [Controller::class, 'method'])->name('route-name');
Route::post('/path', [Controller::class, 'method']);

// ALLOWED - Chaining name/middleware on individual routes
Route::get('/path', [Controller::class, 'method'])
    ->name('route-name')
    ->middleware('throttle');
```

### Namespace Restrictions

Plugins **cannot** use these namespace prefixes:

- `App\`
- `Database\`
- `Tests\`
- `Illuminate\`
- `Laravel\`
- `Filament\`
- `Livewire\`
- `Spatie\`

### File Restrictions

- PHP files only allowed in `src/`, `database/migrations/`, and `routes/`
- Blade templates (`.blade.php`) allowed in `resources/views/`
- No `.htaccess`, `.env`, or similar files
- No symlinks
- No `vendor/` or `bootstrap/` directories

---

## Testing Your Plugin

### Local Development

1. Create your plugin in the `plugins/{vendor}/{slug}/` directory
2. The plugin will be auto-discovered on next request
3. Check the admin panel under Plugins to verify it's loaded

### Verification Checklist

- [ ] Plugin appears in admin Plugins list
- [ ] All routes are accessible
- [ ] Custom blocks appear in block picker
- [ ] Migrations run successfully
- [ ] Filament resources load (if applicable)
- [ ] Views render correctly
- [ ] No errors in `storage/logs/laravel.log`

### Common Issues

| Issue | Solution |
|-------|----------|
| Plugin not discovered | Check `plugin.json` is valid JSON |
| Class not found | Verify namespace matches directory structure |
| Routes not working | Ensure routes are in `public_routes` whitelist |
| Blocks not appearing | Check block class implements `CustomBlockInterface` |
| Migration errors | Use underscores in table names, not hyphens |

---

## Distribution

### Creating a ZIP Package

1. Ensure all files are in place
2. Remove any development files (`.git`, `node_modules`, etc.)
3. ZIP the plugin directory:

```bash
cd plugins/your-vendor
zip -r your-plugin-v1.0.0.zip your-plugin/ \
    -x "*.git*" \
    -x "*node_modules*" \
    -x "*.DS_Store"
```

### Pre-flight Validation

Before distributing, verify your plugin passes validation:

1. Try installing via the admin Upload feature
2. Check for any warning or error messages
3. Test on a fresh TallCMS installation

### Version Updates

When releasing updates:

1. Increment the version in `plugin.json`
2. Add migration files for any database changes (don't modify existing migrations)
3. Update compatibility requirements if needed
4. Test the upgrade path from previous versions

---

## Example: Complete Plugin

Here's a complete example of a simple "Hello World" plugin:

### Directory Structure

```
plugins/acme/hello-world/
├── plugin.json
├── src/
│   ├── Providers/
│   │   └── HelloWorldServiceProvider.php
│   └── Blocks/
│       └── HelloWorldBlock.php
├── resources/
│   └── views/
│       └── blocks/
│           └── hello-world.blade.php
└── routes/
    └── public.php
```

### plugin.json

```json
{
    "name": "Hello World",
    "slug": "hello-world",
    "vendor": "acme",
    "version": "1.0.0",
    "description": "A simple hello world plugin",
    "author": "ACME Corp",
    "namespace": "Acme\\HelloWorld",
    "provider": "Acme\\HelloWorld\\Providers\\HelloWorldServiceProvider",
    "tags": ["demo", "example"],
    "compatibility": {
        "php": "^8.2",
        "tallcms": "^1.0"
    },
    "public_routes": ["/hello"]
}
```

### HelloWorldServiceProvider.php

```php
<?php

namespace Acme\HelloWorld\Providers;

use Illuminate\Support\ServiceProvider;

class HelloWorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../../resources/views',
            'acme-hello-world'
        );
    }
}
```

### HelloWorldBlock.php

```php
<?php

namespace Acme\HelloWorld\Blocks;

use App\Contracts\CustomBlockInterface;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class HelloWorldBlock implements CustomBlockInterface
{
    public static function getBlockName(): string
    {
        return 'hello-world';
    }

    public static function getBlockLabel(): string
    {
        return 'Hello World';
    }

    public static function getBlockIcon(): string
    {
        return 'heroicon-o-hand-raised';
    }

    public static function getBlockSchema(): array
    {
        return [
            TextInput::make('greeting')
                ->label('Greeting')
                ->default('Hello')
                ->required(),
            TextInput::make('name')
                ->label('Name')
                ->default('World'),
            Select::make('style')
                ->options([
                    'default' => 'Default',
                    'gradient' => 'Gradient',
                ])
                ->default('default'),
        ];
    }

    public static function getViewName(): string
    {
        return 'acme-hello-world::blocks.hello-world';
    }
}
```

### hello-world.blade.php

```blade
@props(['greeting' => 'Hello', 'name' => 'World', 'style' => 'default'])

<div @class([
    'hello-world-block p-8 rounded-lg text-center',
    'bg-gray-100' => $style === 'default',
    'bg-gradient-to-r from-blue-500 to-purple-600 text-white' => $style === 'gradient',
])>
    <h2 class="text-3xl font-bold">
        {{ $greeting }}, {{ $name }}!
    </h2>
</div>
```

### routes/public.php

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return view('acme-hello-world::blocks.hello-world', [
        'greeting' => 'Hello',
        'name' => 'Visitor',
        'style' => 'gradient',
    ]);
})->name('hello');
```

---

## Getting Help

- Check the [TallCMS Documentation](/)
- Review existing plugins for examples
- Report issues on GitHub

---

*Last updated: January 2026*
