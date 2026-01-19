# TallCMS

A modern, feature-rich CMS package for Laravel Filament. Works as a standalone application or as a plugin in existing Filament apps.

## Features

- **Rich Content Editor** - Block-based editor with 16 built-in content blocks
- **Pages & Posts** - Static pages and blog posts with categories
- **Publishing Workflow** - Draft, Pending Review, Scheduled, and Published states
- **Revision History** - Track changes with diff comparison and rollback
- **Preview System** - Preview unpublished content with shareable tokens
- **Media Library** - Organize uploads with collections and metadata
- **Menu Builder** - Drag-and-drop navigation menus for multiple locations
- **Site Settings** - Centralized configuration for site name, contact info, social links
- **Contact Form** - Built-in contact form block with email notifications
- **SEO Ready** - Meta descriptions, canonical URLs, and structured data support
- **Multi-Panel Support** - Works with any Filament panel configuration

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Filament ^4.0

## Installation

### As a Filament Plugin (Recommended)

Add TallCMS to your existing Filament application.

> **Note:** TallCMS v2.x requires Filament 4.x (not Filament 5) because filament-shield doesn't yet have a Filament 5 compatible release.

**Prerequisites:** You must have Filament installed and configured first:

```bash
composer require filament/filament:"^4.0"
php artisan filament:install --panels
```

**1. Install via Composer:**

```bash
composer require tallcms/cms
```

**2. Add HasRoles Trait to User Model:**

Your `User` model must use the `HasRoles` trait from Spatie Permission:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;
    // ...
}
```

**3. Register the plugin in your Panel Provider:**

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

**4. Run the installer:**

```bash
php artisan tallcms:install
```

This single command handles everything:
- Checks prerequisites (HasRoles trait, etc.)
- Publishes Spatie Permission migrations
- Runs all database migrations
- Sets up roles and permissions via Filament Shield
- Creates your admin user

**5. (Optional) Publish configuration:**

```bash
php artisan vendor:publish --tag=tallcms-config
```

### As a Standalone Application

For a full TallCMS installation with themes, plugins, web installer, and auto-updates:

```bash
composer create-project tallcms/tallcms my-cms
cd my-cms
composer setup
```

Or use the web installer by visiting `/install` after deployment.

## What Gets Registered

The plugin automatically registers these Filament components:

| Type | Components |
|------|------------|
| **Resources** | Pages, Posts, Categories, Media, Menus, Contact Submissions |
| **Pages** | Site Settings, Menu Items Manager |
| **Widgets** | Menu Overview Widget |

## Selective Registration

Disable specific components you don't need:

```php
->plugin(
    TallCmsPlugin::make()
        ->withoutCategories()
        ->withoutPosts()
        ->withoutPages()
        ->withoutMedia()
        ->withoutMenus()
        ->withoutContactSubmissions()
        ->withoutSiteSettings()
)
```

## Configuration

After publishing the config file, customize these options:

> **Frontend routes are disabled by default in plugin mode.**  
> To render CMS pages in your host app, you must enable them explicitly
> and set a non-empty `routes_prefix`.

```php
// config/tallcms.php
return [
    // Force mode (auto-detected if not set)
    'mode' => 'plugin', // or 'standalone'

    'plugin_mode' => [
        // Custom user model (defaults to App\Models\User)
        'user_model' => App\Models\User::class,

        // Enable frontend routes (requires prefix)
        'routes_enabled' => false,
        'routes_prefix' => 'cms', // e.g., /cms/page-slug
        // Optional: enable catch-all slug routing (/{slug}) under the prefix
        'catch_all_enabled' => false,

        // Enable theme system in plugin mode
        'themes_enabled' => false,

        // Enable plugin system in plugin mode
        'plugins_enabled' => false,
    ],
];
```

## Frontend Routes (Optional)

To render CMS pages on your frontend:

**1. Enable routes in config (or .env):**

```php
'plugin_mode' => [
    'routes_enabled' => true,
    'routes_prefix' => 'pages', // Pages accessible at /pages/{slug}
    'catch_all_enabled' => true, // Enable /{slug} catch-all under the prefix
],
```

**.env example:**

```
TALLCMS_ROUTES_ENABLED=true
TALLCMS_ROUTES_PREFIX=pages
TALLCMS_CATCH_ALL_ENABLED=true
```

**2. Publish frontend assets:**

```bash
php artisan vendor:publish --tag=tallcms-assets
```

**3. (Optional) Publish and customize views:**

```bash
php artisan vendor:publish --tag=tallcms-views
```

## Content Blocks

TallCMS includes 16 content blocks for building rich pages:

| Block | Description |
|-------|-------------|
| **Hero** | Full-width hero sections with background images and CTAs |
| **Call to Action** | Promotional sections with buttons |
| **Content** | Rich text content with headings and formatting |
| **Features** | Feature grids with icons and descriptions |
| **Pricing** | Pricing tables with plans and feature lists |
| **FAQ** | Accordion-style frequently asked questions |
| **Testimonials** | Customer testimonials with photos and quotes |
| **Team** | Team member profiles with photos and bios |
| **Stats** | Statistics and metrics display |
| **Image Gallery** | Photo galleries with lightbox |
| **Logos** | Logo showcases for partners/clients |
| **Timeline** | Chronological event displays |
| **Contact Form** | Contact forms with email delivery |
| **Posts** | Display recent blog posts |
| **Parallax** | Parallax scrolling sections |
| **Divider** | Visual separators between sections |

### Creating Custom Blocks

Generate a new block with the artisan command:

```bash
php artisan make:tallcms-block MyCustomBlock
```

This creates:
- Block class at `app/Filament/Blocks/MyCustomBlockBlock.php`
- Blade template at `resources/views/cms/blocks/my-custom-block.blade.php`

## Publishing Workflow

TallCMS supports a full publishing workflow:

| Status | Description |
|--------|-------------|
| **Draft** | Work in progress, not visible |
| **Pending Review** | Submitted for approval |
| **Scheduled** | Will publish at specified date/time |
| **Published** | Live and visible to visitors |

### Preview Tokens

Share unpublished content with stakeholders using preview tokens:

- Generate shareable links from the editor
- Set expiration dates and view limits
- Track remaining views

## Revision History

All content changes are tracked:

- View complete revision history
- Compare any two revisions with diff highlighting
- Restore previous versions with one click
- Pin important revisions as snapshots

## Multi-Panel Support

TallCMS works with any Filament panel configuration. Register the plugin in each panel where you want CMS features:

```php
// app/Providers/Filament/AdminPanelProvider.php
->plugin(TallCmsPlugin::make())

// app/Providers/Filament/EditorPanelProvider.php
->plugin(
    TallCmsPlugin::make()
        ->withoutSiteSettings() // Editors can't change settings
)
```

All internal URLs use Filament's `Page::getUrl()` method, ensuring compatibility with custom panel paths and multi-panel setups.

## Customization

### Custom User Model

If your application uses a custom User model:

```php
// config/tallcms.php
'plugin_mode' => [
    'user_model' => App\Models\CustomUser::class,
],
```

The model must implement `Illuminate\Contracts\Auth\Authenticatable`.

### Overriding Views

Publish views for customization:

```bash
php artisan vendor:publish --tag=tallcms-views
```

Views are published to `resources/views/vendor/tallcms/`.

### Extending Resources

Create your own resource classes that extend the package resources:

```php
namespace App\Filament\Resources;

use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource as BaseResource;

class CmsPageResource extends BaseResource
{
    // Override methods as needed
    public static function getNavigationGroup(): ?string
    {
        return 'My Custom Group';
    }
}
```

## Database Tables

TallCMS creates these tables (all prefixed with `tallcms_`):

| Table | Purpose |
|-------|---------|
| `tallcms_pages` | Static content pages |
| `tallcms_posts` | Blog posts |
| `tallcms_categories` | Hierarchical categories |
| `tallcms_post_category` | Post-category pivot |
| `tallcms_site_settings` | Site configuration |
| `tallcms_media` | Media library items |
| `tallcms_media_collections` | Media collections |
| `tallcms_menus` | Navigation menus |
| `tallcms_menu_items` | Menu items (nested set) |
| `tallcms_contact_submissions` | Contact form entries |
| `tallcms_revisions` | Content revision history |
| `tallcms_preview_tokens` | Preview sharing tokens |
| `tallcms_plugin_migrations` | Plugin migration tracking |
| `tallcms_plugin_licenses` | Plugin license storage |

## Artisan Commands

| Command | Description |
|---------|-------------|
| `make:tallcms-block` | Create a custom content block |
| `tallcms:clean-preview-tokens` | Remove expired preview tokens |

### Setup Command

| Command | Description |
|---------|-------------|
| `tallcms:setup` | Setup roles, permissions, and admin user |

### Standalone-Only Commands

These commands are only available in standalone mode:

| Command | Description |
|---------|-------------|
| `tallcms:update` | Update to latest version |
| `tallcms:version` | Display current version |

## Permissions

TallCMS integrates with [Filament Shield](https://github.com/bezhanSalleh/filament-shield) for permissions. Default permissions are created for each resource:

- `ViewAny`, `View`, `Create`, `Update`, `Delete` for each resource
- `View:SiteSettings` for the settings page
- `View:MenuItemsManager` for menu management

## Upgrade Guide

### From v1.x Standalone to v2.x

If upgrading from a standalone TallCMS v1.x installation:

1. Update `composer.json` to require `tallcms/cms: ^2.0`
2. Run `composer update`
3. The package auto-detects standalone mode via `.tallcms-standalone` marker file
4. All existing data and customizations are preserved

### Migrating Standalone to Plugin Mode

To convert a standalone installation to plugin mode:

1. Remove the `.tallcms-standalone` marker file
2. Set `'mode' => 'plugin'` in `config/tallcms.php`
3. Register `TallCmsPlugin` in your panel provider

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please email security@tallcms.com instead of using the issue tracker.

## License

TallCMS is open-source software licensed under the [MIT license](LICENSE.md).

## Credits

- Built with [Laravel](https://laravel.com) and [Filament](https://filamentphp.com)
- Uses [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools)
