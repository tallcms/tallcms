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

- **PHP**: 8.2+ with OpenSSL, PDO, Mbstring, GD extensions
- **Laravel**: 11.0 or 12.0
- **Filament**: 4.0
- **Database**: MySQL 8.0+, MariaDB 10.3+, or SQLite

See [Installation Guide](docs/INSTALLATION.md) for complete requirements.

## Standalone Installation

```bash
composer create-project tallcms/tallcms my-site
cd my-site
npm install && npm run build
php artisan serve
```

Visit `http://localhost:8000/install` to complete the web installer.

See [Installation Guide](docs/INSTALLATION.md) for manual download, contributor setup, and web server configuration.

## Plugin Installation

Add CMS features to your existing Filament application:

```bash
# 1. Install package
composer require tallcms/cms

# 2. Add HasRoles trait to User model
# use Spatie\Permission\Traits\HasRoles;

# 3. Register plugin in your panel provider
# ->plugin(TallCmsPlugin::make())

# 4. Run installer
php artisan tallcms:install
```

See [Installation Guide](docs/INSTALLATION.md) for detailed steps, frontend routes, and selective features.

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

## Documentation

| Guide | Description |
|-------|-------------|
| [Installation](docs/INSTALLATION.md) | System requirements, setup, configuration |
| [Block Development](docs/BLOCK_DEVELOPMENT.md) | Create custom content blocks |
| [CMS Rich Editor](docs/CMS_RICH_EDITOR.md) | Enhanced editor with search and categories |
| [Menu Management](docs/MENUS.md) | Navigation menus and locations |
| [Theme Development](docs/THEME_DEVELOPMENT.md) | Create custom themes with daisyUI |
| [Plugin Development](docs/PLUGIN_DEVELOPMENT.md) | Extend TallCMS with plugins |
| [Site Settings](docs/SITE_SETTINGS.md) | Configuration and SPA mode |
| [Publishing Workflow](docs/PUBLISHING_WORKFLOW.md) | Draft, review, scheduling |
| [SEO](docs/SEO.md) | Search engine optimization |

## Troubleshooting

Common issues and solutions are documented in the [Installation Guide](docs/INSTALLATION.md#troubleshooting).

**Quick fixes:**
- **"Installation already complete"** - Delete `storage/installer.lock` or set `INSTALLER_ENABLED=true`
- **"Permission denied"** - Run `chmod -R 775 storage bootstrap/cache`
- **"CMS resources not appearing"** - Ensure plugin is registered and run `php artisan migrate`
- **"Call to undefined method assignRole()"** - Add `HasRoles` trait to User model

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
