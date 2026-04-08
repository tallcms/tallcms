# TallCMS

[![Packagist Version](https://img.shields.io/packagist/v/tallcms/cms)](https://packagist.org/packages/tallcms/cms)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tallcms/cms)](https://packagist.org/packages/tallcms/cms)
[![License](https://img.shields.io/packagist/l/tallcms/cms)](https://opensource.org/licenses/MIT)

A modern Content Management System package for Laravel Filament. Add rich content editing, pages, posts, media library, and menus to your existing Filament application.

> **Note:** This repository is a read-only subtree split of the [tallcms/tallcms](https://github.com/tallcms/tallcms) monorepo, updated automatically via CI. For issues, pull requests, and full documentation, please visit [tallcms/tallcms](https://github.com/tallcms/tallcms).

> For a full standalone CMS with themes, plugins, and auto-updates, see [tallcms/tallcms](https://github.com/tallcms/tallcms).

## Features

- **Block-Based Editor** — 18 built-in content blocks with animations and responsive design
- **Pages & Posts** — Static pages and blog posts with categories and templates
- **Publishing Workflow** — Draft, Pending Review, Scheduled, and Published states
- **Revision History** — Track changes with diff comparison and rollback
- **Preview System** — Preview unpublished content with shareable tokens
- **Media Library** — Organize uploads with collections and metadata
- **Menu Builder** — Drag-and-drop navigation menus with mega menu support
- **Comments** — Built-in comment system with moderation
- **Contact Form** — Dynamic contact form block with email notifications
- **Full-Text Search** — Laravel Scout-powered search across content
- **SEO** — Meta descriptions, canonical URLs, Open Graph, and structured data (Schema.org)
- **Internationalization** — Multi-language support via Spatie Translatable
- **Site Settings** — Centralized configuration for site name, contact info, social links
- **Role-Based Permissions** — Super Admin, Administrator, Editor, Author via Filament Shield
- **REST API** — Optional Sanctum-authenticated API for headless usage
- **Cloud Storage** — S3-compatible storage (AWS, DigitalOcean, Cloudflare R2)
- **Multi-Panel Support** — Works with any Filament panel configuration

## Screenshots

### Page Editor
Build rich pages with the block-based content editor.

![Page Editor](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/page-editor.png)

### Content Blocks
Choose from many built-in blocks: Hero, CTA, Pricing, FAQ, Testimonials, and more.

![Content Blocks](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/blocks.png)

### Posts Management
Manage blog posts with filters, bulk actions, and status indicators.

![Posts List](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/posts-list.png)

### Media Library
Organize uploads with collections, metadata, and drag-and-drop.

![Media Library](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/media-library.png)

### Menu Builder
Create navigation menus with drag-and-drop ordering.

![Menu Builder](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/menu-builder.png)

### Revision History
Track changes with diff comparison and one-click rollback.

![Revision History](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/revisions.png)

### Site Settings
Configure site name, contact info, social links, and more.

![Site Settings](https://raw.githubusercontent.com/tallcms/cms/main/docs/screenshots/site-settings.png)

## Requirements

- **PHP**: 8.2+
- **Laravel**: 11.x or 12.x
- **Filament**: 5.x
- **Database**: MySQL 8.0+, MariaDB 10.3+, or SQLite

## Installation

**1. Install via Composer:**

```bash
composer require tallcms/cms
```

This will also install Filament 5.x as a dependency.

**2. Configure Filament (if not already done):**

```bash
php artisan filament:install --panels
```

**3. Add HasRoles Trait to User Model:**

Your `User` model must use the `HasRoles` trait from Spatie Permission:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;
    // ...
}
```

**4. Register the plugin in your Panel Provider:**

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

**5. Run the installer:**

```bash
php artisan tallcms:install
```

This single command handles everything:
- Checks prerequisites (HasRoles trait, panel provider, etc.)
- Publishes Spatie Permission migrations
- Runs all database migrations
- Sets up roles and permissions via Filament Shield
- Creates your admin user

**6. (Optional) Publish configuration:**

```bash
php artisan vendor:publish --tag=tallcms-config
```

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

```php
// config/tallcms.php
return [
    // Force mode (auto-detected if not set)
    'mode' => 'plugin', // or 'standalone'

    'plugin_mode' => [
        // Custom user model (defaults to App\Models\User)
        'user_model' => App\Models\User::class,

        // Enable frontend routes for /{slug} paths
        'routes_enabled' => false,

        // Optional URL prefix (e.g., 'cms' for /cms/about)
        'routes_prefix' => '',

        // Enable theme system in plugin mode
        'themes_enabled' => false,

        // Enable plugin system in plugin mode
        'plugins_enabled' => false,
    ],
];
```

## Frontend Routes (Optional)

Frontend routes are **disabled by default** in plugin mode to avoid conflicts with your app's routes.

### 1. Enable CMS Routes

Add to your `.env` file:

```env
TALLCMS_ROUTES_ENABLED=true
```

This registers both `/` (homepage) and `/{slug}` routes for CMS pages.
Routes automatically exclude common paths like your panel path (default `/admin`), `/api`, `/livewire`, `/storage`, etc.

> **Warning:** When `TALLCMS_ROUTES_ENABLED=true` without a prefix, TallCMS
> registers the `/` route. However, Laravel loads your app's `routes/web.php` after
> package routes, so **you must remove the default `/` route from `routes/web.php`**
> for TallCMS to handle your homepage. Alternatively, set `TALLCMS_ROUTES_PREFIX=cms`
> to avoid the conflict.

### 2. Configure the Homepage

Mark a CMS page as "Homepage" in the admin panel. TallCMS will serve it at `/` (or `/{prefix}` if using a prefix).

### 3. Route Prefix (Optional)

To prefix all CMS routes (e.g., `/cms/about` instead of `/about`):

```env
TALLCMS_ROUTES_PREFIX=cms
```

With a prefix, the routes become `/cms` (homepage) and `/cms/{slug}` (pages).

### 4. Publish Assets (Optional)

For frontend styling:

```bash
php artisan vendor:publish --tag=tallcms-assets
```

### 5. Publish Views (Optional)

To customize templates:

```bash
php artisan vendor:publish --tag=tallcms-views
```

### Prerequisites for Frontend Routes

TallCMS frontend pages require **Alpine.js**.

Most Laravel apps include Alpine via Livewire. If your app loads Alpine
separately, ensure it's loaded BEFORE tallcms.js, as TallCMS registers
components on `alpine:init`.

## Built-in Blocks

| Block | Description |
|-------|-------------|
| **Hero** | Landing page headers with CTAs and background images |
| **Call-to-Action** | Conversion-optimized promotional sections |
| **Content** | Article content with headings and rich text |
| **Features** | Feature grids with icons and descriptions |
| **Pricing** | Pricing tables with feature comparison |
| **FAQ** | Accordion-style Q&A with optional FAQPage schema markup |
| **How To** | Step-by-step instructions with HowTo schema markup |
| **Testimonials** | Customer testimonials with ratings |
| **Team** | Team member profiles with social links |
| **Stats** | Statistics and metrics display |
| **Image Gallery** | Lightbox galleries with grid layouts |
| **Logos** | Logo showcases for partners/clients |
| **Timeline** | Chronological event displays |
| **Contact Form** | Dynamic forms with email notifications |
| **Posts** | Display recent blog posts |
| **Parallax** | Parallax scrolling sections |
| **Document List** | Downloadable file listings |
| **Divider** | Visual section separators |

### Creating Custom Blocks

Generate a new block with the artisan command:

```bash
php artisan make:tallcms-block MyCustomBlock
```

This creates:
- Block class at `app/Filament/Blocks/MyCustomBlockBlock.php`
- Blade template at `resources/views/cms/blocks/my-custom-block.blade.php`

## TallCMS Pro

Upgrade to **TallCMS Pro** for advanced features:

- **9 Premium Blocks** — Accordion, Tabs, Counter, Table, Comparison, Video, Before/After, Code Snippet, Map
- **Analytics Dashboard** — Google Analytics 4 integration with visitor stats
- **Priority Support** — Direct email support

Learn more at [tallcms.com/pro](https://tallcms.com/pro)

## User Roles

| Role | Description |
|------|-------------|
| **Super Admin** | Complete system control |
| **Administrator** | Content and user management |
| **Editor** | Full content management |
| **Author** | Create and edit own content |

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
| `tallcms_comments` | Content comments |
| `tallcms_revisions` | Content revision history |
| `tallcms_preview_tokens` | Preview sharing tokens |
| `tallcms_webhooks` | Webhook configurations |
| `tallcms_webhook_deliveries` | Webhook delivery log |
| `tallcms_plugin_migrations` | Plugin migration tracking |
| `tallcms_plugin_licenses` | Plugin license keys |

## Artisan Commands

| Command | Description |
|---------|-------------|
| `tallcms:install` | Full installation (migrations, roles, admin user) |
| `tallcms:setup` | Setup roles, permissions, and admin user only |
| `make:tallcms-block` | Create a custom content block |
| `search:index` | Rebuild the full-text search index |
| `tallcms:clean-preview-tokens` | Remove expired preview tokens |

## Permissions

TallCMS integrates with [Filament Shield](https://github.com/bezhanSalleh/filament-shield) for permissions. Default permissions are created for each resource:

- `ViewAny`, `View`, `Create`, `Update`, `Delete` for each resource
- `View:SiteSettings` for the settings page
- `View:MenuItemsManager` for menu management

## Documentation

Full documentation is available in the monorepo: [tallcms/tallcms/docs](https://github.com/tallcms/tallcms/tree/main/docs)

## Contributing

Contributions are welcome! This repository is a read-only subtree split of the [tallcms/tallcms](https://github.com/tallcms/tallcms) monorepo. Please submit issues and pull requests there.

## Security

If you discover a security vulnerability, please email security@tallcms.com instead of using the issue tracker.

## License

TallCMS is open-source software licensed under the [MIT license](LICENSE.md).

## Credits

### Built with AI

TallCMS is co-developed with [Claude AI](https://claude.ai) (Anthropic) and code-reviewed by [Codex](https://openai.com/index/openai-codex/) (OpenAI).

### Core Technologies

- [Laravel](https://laravel.com/) — The PHP framework
- [Filament](https://filamentphp.com/) — Admin panel framework
- [Livewire](https://laravel-livewire.com/) — Dynamic frontend components
- [Tailwind CSS](https://tailwindcss.com/) — Utility-first CSS
- [daisyUI](https://daisyui.com/) — Tailwind component library and themes
- [Alpine.js](https://alpinejs.dev/) — Lightweight JavaScript framework
- [Spatie](https://spatie.be/) — Laravel packages

## Links

- **Website**: [tallcms.com](https://tallcms.com)
- **Documentation**: [tallcms.com/docs](https://tallcms.com/docs)
- **Monorepo**: [github.com/tallcms/tallcms](https://github.com/tallcms/tallcms)
- **Roadmap**: [ROADMAP.md](https://github.com/tallcms/tallcms/blob/main/ROADMAP.md)
- **Support**: hello@tallcms.com
