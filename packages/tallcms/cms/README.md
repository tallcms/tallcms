# TallCMS

A modern CMS package for Laravel Filament. Works as a standalone application or as a plugin in existing Filament apps.

## Installation

### As a Filament Plugin (Existing App)

1. **Install via Composer:**

```bash
composer require tallcms/cms
```

2. **Publish config and migrations:**

```bash
php artisan vendor:publish --tag=tallcms-config
php artisan vendor:publish --tag=tallcms-migrations
php artisan migrate
```

3. **Register the plugin in your Panel Provider:**

```php
use TallCms\Cms\TallCmsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... your existing configuration
        ->plugin(TallCmsPlugin::make());
}
```

> **Important:** You must register `TallCmsPlugin` in your panel provider. The CMS resources will not appear in your admin panel without this step.

4. **(Optional) Publish assets for frontend routes:**

```bash
php artisan vendor:publish --tag=tallcms-assets
```

### What Gets Registered

The plugin automatically registers:
- **Resources**: Pages, Posts, Categories, Media, Menus, Contact Submissions
- **Pages**: Site Settings, Menu Items Manager
- **Widgets**: Menu Overview Widget

### Selective Registration

You can disable specific components if you don't need them:

```php
->plugin(
    TallCmsPlugin::make()
        ->withoutCategories()
        ->withoutPosts()
        ->withoutContactSubmissions()
        ->withoutSiteSettings()
)
```

Available methods:
- `withoutCategories()` - Disable categories resource
- `withoutPages()` - Disable pages resource
- `withoutPosts()` - Disable posts resource
- `withoutContactSubmissions()` - Disable contact submissions resource
- `withoutMedia()` - Disable media library resource
- `withoutMenus()` - Disable menus resource and related pages/widgets
- `withoutSiteSettings()` - Disable site settings page

### Configuration

Publish and edit the config file:

```bash
php artisan vendor:publish --tag=tallcms-config
```

Key configuration options for plugin mode:

```php
// config/tallcms.php
return [
    // Force plugin mode (auto-detected if not set)
    'mode' => 'plugin', // or 'standalone'
    
    'plugin_mode' => [
        // Custom user model (defaults to App\Models\User)
        'user_model' => App\Models\User::class,
        
        // Enable frontend routes (requires prefix)
        'routes_enabled' => false,
        'routes_prefix' => 'cms', // e.g., /cms/page-slug
        
        // Enable theme system in plugin mode
        'themes_enabled' => false,
        
        // Enable plugin system in plugin mode  
        'plugins_enabled' => false,
    ],
];
```

### Frontend Routes (Optional)

To enable CMS page rendering on the frontend:

1. Enable routes in config:

```php
'plugin_mode' => [
    'routes_enabled' => true,
    'routes_prefix' => 'pages', // Pages at /pages/slug
],
```

2. Publish assets:

```bash
php artisan vendor:publish --tag=tallcms-assets
```

## As a Standalone Application

For a full TallCMS installation with themes, plugins, and auto-updates, use the skeleton:

```bash
composer create-project tallcms/tallcms my-cms
cd my-cms
composer setup
```

## Resources Included

- **Pages** - Static content pages with rich editor
- **Posts** - Blog posts with categories
- **Categories** - Hierarchical categories for posts
- **Media** - Media library management
- **Menus** - Navigation menu builder
- **Contact Submissions** - Contact form submissions viewer

## Custom Blocks

TallCMS includes a rich set of content blocks:

- Hero, Call to Action, Content
- Pricing, Features, FAQ
- Testimonials, Team, Stats
- Image Gallery, Logos, Timeline
- Contact Form, Posts Grid, Parallax

### Creating Custom Blocks

```bash
php artisan make:tallcms-block MyCustomBlock
```

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Filament ^4.0

## License

MIT
