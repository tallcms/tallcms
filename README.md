# TallCMS™

[![Packagist Version](https://img.shields.io/packagist/v/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![License](https://img.shields.io/packagist/l/tallcms/tallcms)](https://opensource.org/licenses/MIT)

A modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with a Filament v4 admin panel and a daisyUI-powered block system.

## Two Ways to Use TallCMS

### 1. Standalone Application (Full CMS)

Get a complete CMS with themes, plugins, web installer, and auto-updates:

```bash
composer create-project tallcms/tallcms my-site
```

### 2. Filament Plugin (Add to Existing App)

Add CMS features to your existing Filament application:

```bash
composer require tallcms/cms
```

Then register the plugin in your panel provider:

```php
use TallCms\Cms\TallCmsPlugin;

->plugin(TallCmsPlugin::make())
```

See the [package documentation](packages/tallcms/cms/README.md) for full plugin installation instructions.

---

### 🤖 Built with AI

TallCMS is proudly **co-developed with [Claude AI](https://claude.ai)** (Anthropic) and **code-reviewed by [Codex](https://openai.com/index/openai-codex/)** (OpenAI).

This project demonstrates what's possible when human creativity meets AI capability - from architecture design to security reviews, AI collaboration accelerated development while maintaining code quality.

---

## Features

- **Web Installer** - Setup wizard with no command line required (standalone only)
- **One-Click Updates** - Secure system updates with Ed25519 signature verification (standalone only)
- **Rich Content Editor** - Block-based editor with 16 built-in content blocks
- **Pages & Posts** - Static pages and blog posts with categories
- **Publishing Workflow** - Draft, Pending Review, Scheduled, and Published states
- **Revision History** - Track changes with diff comparison and rollback
- **Preview System** - Preview unpublished content with shareable tokens
- **Media Library** - Organize uploads with collections and metadata
- **Menu Builder** - Drag-and-drop navigation menus for multiple locations
- **Site Settings** - Centralized configuration for site name, contact info, social links
- **Role-Based Permissions** - Super Admin, Administrator, Editor, Author
- **Plugin System** - Extend functionality with installable plugins (standalone only)
- **Theme System** - daisyUI presets or custom themes with template overrides (standalone only)
- **Cloud Storage** - S3-compatible storage (AWS, DigitalOcean, Cloudflare R2)
- **Maintenance Mode** - Built-in site maintenance with custom messaging

## System Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 11.0 or 12.0
- **Filament**: 4.0
- **Database**: MySQL 8.0+, MariaDB 10.3+, or SQLite
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo, GD

## Standalone Installation

### Via Composer (Recommended)

```bash
composer create-project tallcms/tallcms my-site
cd my-site
npm install && npm run build
php artisan serve
```

Visit `http://localhost:8000/install` to complete the web installer.

### Manual Download

1. **Download** the latest release from [tallcms.com](https://tallcms.com)
2. **Extract** to your web server directory
3. **Visit** your domain in a browser
4. **Follow** the setup wizard

The web installer will guide you through:
- Environment verification
- Database configuration
- Admin account creation
- Initial site settings

### For Contributors

```bash
git clone https://github.com/tallcms/tallcms.git
cd tallcms
composer install
npm install && npm run build
composer dev            # Start dev server with hot reload
```

Visit `http://localhost:8000/install` to complete setup.

## Plugin Installation

For existing Filament applications, install the CMS as a plugin:

### 1. Install the Package

```bash
composer require tallcms/cms
```

> **Note:** TallCMS v2.x requires Filament 4.x (not Filament 5) because filament-shield doesn't yet have a Filament 5 compatible release.

### 2. Add HasRoles Trait to User Model

Your `User` model must use the `HasRoles` trait:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;
    // ...
}
```

### 3. Register the Plugin

Add `TallCmsPlugin` to your panel provider (e.g., `AdminPanelProvider.php`):

```php
use TallCms\Cms\TallCmsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(TallCmsPlugin::make());
}
```

### 4. Run the Installer

```bash
php artisan tallcms:install
```

This single command will:
- Check prerequisites (HasRoles trait, etc.)
- Publish and run migrations
- Setup roles and permissions
- Create your admin user

### Frontend Routes (Plugin Mode)

Frontend routes are **disabled by default** in plugin mode to avoid conflicts.
If you want CMS pages to render in your host app, enable them explicitly:

```
TALLCMS_ROUTES_ENABLED=true
TALLCMS_ROUTES_PREFIX=cms
TALLCMS_CATCH_ALL_ENABLED=true
```

Then publish assets:

```bash
php artisan vendor:publish --tag=tallcms-assets
```

### Selective Features

Disable components you don't need:

```php
->plugin(
    TallCmsPlugin::make()
        ->withoutPosts()
        ->withoutCategories()
        ->withoutContactSubmissions()
)
```

## Built-in Blocks

| Block | Description |
|-------|-------------|
| **Hero** | Landing page headers with CTAs and background images |
| **Call-to-Action** | Conversion-optimized promotional sections |
| **Content** | Article content with headings and rich text |
| **Features** | Feature grids with icons and descriptions |
| **Pricing** | Pricing tables with feature comparison |
| **FAQ** | Accordion-style frequently asked questions |
| **Testimonials** | Customer testimonials with ratings |
| **Team** | Team member profiles with social links |
| **Stats** | Statistics and metrics display |
| **Image Gallery** | Lightbox galleries with grid layouts |
| **Logos** | Logo showcases for partners/clients |
| **Timeline** | Chronological event displays |
| **Contact Form** | Dynamic forms with email notifications |
| **Posts** | Display recent blog posts |
| **Parallax** | Parallax scrolling sections |
| **Divider** | Visual section separators |

## TallCMS Pro

Upgrade to **TallCMS Pro** for advanced features:

- **9 Premium Blocks** - Accordion, Tabs, Counter, Table, Comparison, Video, Before/After, Code Snippet, Map
- **Analytics Dashboard** - Google Analytics 4 integration with visitor stats
- **Priority Support** - Direct email support

Learn more at [tallcms.com/pro](https://tallcms.com/pro)

## User Roles

| Role | Description |
|------|-------------|
| **Super Admin** | Complete system control |
| **Administrator** | Content and user management |
| **Editor** | Full content management |
| **Author** | Create and edit own content |

## Plugin Development

Create custom plugins to extend TallCMS:

```
plugins/your-vendor/your-plugin/
├── plugin.json
├── src/
│   ├── Providers/
│   └── Blocks/
├── resources/views/
└── database/migrations/
```

See [Plugin Development Guide](docs/PLUGIN_DEVELOPMENT.md) for details.

## Theme Development

Create custom themes with daisyUI and template overrides:

```
themes/your-theme/
├── theme.json
├── resources/css/app.css
├── resources/views/
├── public/
└── vite.config.js
```

See [Theme Development Guide](docs/THEME_DEVELOPMENT.md) for details.

## Troubleshooting

### Web Installer

**"Installation already complete"**
- Delete `installer.lock` from project root
- Or set `INSTALLER_ENABLED=true` in `.env`

**"Database connection failed"**
- Verify database credentials
- Ensure database server is running
- Check database exists

### Admin Panel

**"Cannot access admin panel"**
- Complete the web installer first
- Verify your user has an active role

**"Permission denied"**
- Check `storage/` and `bootstrap/cache/` are writable
- Run `chmod -R 775 storage bootstrap/cache`

### Plugin Mode

**"CMS resources not appearing"**
- Ensure `TallCmsPlugin::make()` is registered in your panel provider
- Run `php artisan migrate` to create the CMS tables
- Run `php artisan tallcms:setup` to configure roles and permissions

**"No such table: tallcms_menus"**
- Migrations haven't run yet. Run `php artisan migrate`

**"Nothing to migrate" but tables missing**
- Clear config cache: `php artisan config:clear`
- Re-run package discovery: `php artisan package:discover`
- Then run migrations again: `php artisan migrate`

**"Call to undefined method assignRole()"**
- Your User model is missing the `HasRoles` trait
- Add `use Spatie\Permission\Traits\HasRoles;` and include it in your model

**"No such table: roles"**
- Run `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- Then run `php artisan migrate`

## System Updates

TallCMS includes a one-click update system accessible via **Settings → System Updates** in the admin panel (standalone mode only).

### Features

- **Cryptographic Verification** - All releases are signed with Ed25519 signatures
- **Automatic Backups** - Files and database are backed up before each update
- **Progress Tracking** - Real-time status updates during the update process
- **Fallback Options** - Supports exec, queue workers, or manual CLI commands

### CLI Update

You can also update via command line:

```bash
# Check what would happen (dry run)
php artisan tallcms:update --dry-run

# Update to latest version
php artisan tallcms:update

# Update to specific version
php artisan tallcms:update --target=1.2.0
```

### Update Troubleshooting

**"Update in progress"**
- An update lock exists. Wait for it to complete or clear it via the admin panel.

**"Missing required release files"**
- The release may not have signed artifacts. Check GitHub releases.

**"Signature verification failed"**
- The release may have been tampered with. Do not proceed.

## Architecture

TallCMS v2.0 uses a modular architecture:

```
tallcms/tallcms (Skeleton)     tallcms/cms (Package)
├── app/                       ├── src/
│   ├── Models/ (wrappers)     │   ├── Models/
│   ├── Services/ (wrappers)   │   ├── Services/
│   └── Filament/ (wrappers)   │   ├── Filament/
├── themes/                    │   │   ├── Blocks/
├── plugins/                   │   │   ├── Resources/
├── .tallcms-standalone        │   │   ├── Pages/
└── ...                        │   │   └── Widgets/
                               │   └── ...
                               └── database/migrations/
```

- **Standalone mode**: Full skeleton with themes, plugins, and auto-updates
- **Plugin mode**: Just the CMS package in your existing Filament app

## Credits

### AI Collaboration

- **[Claude AI](https://claude.ai)** (Anthropic) - Co-developer, architecture design, feature implementation
- **[Codex](https://openai.com/index/openai-codex/)** (OpenAI) - Code review, security analysis

### Core Technologies

- [Laravel](https://laravel.com/) - The PHP framework
- [Filament v4](https://filamentphp.com/) - Admin panel framework
- [Livewire](https://laravel-livewire.com/) - Dynamic frontend
- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS
- [daisyUI](https://daisyui.com/) - Tailwind component classes and themes
- [Alpine.js](https://alpinejs.dev/) - Lightweight JavaScript

## License

TallCMS is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Links

- **Website**: [tallcms.com](https://tallcms.com)
- **GitHub**: [github.com/tallcms/tallcms](https://github.com/tallcms/tallcms)
- **Package**: [github.com/tallcms/cms](https://github.com/tallcms/cms)
- **Roadmap**: [View our roadmap](ROADMAP.md)
- **Documentation**: [tallcms.com/docs](https://tallcms.com/docs)
- **Support**: hello@tallcms.com
