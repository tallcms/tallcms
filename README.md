# TallCMS

[![Packagist Version](https://img.shields.io/packagist/v/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![License](https://img.shields.io/packagist/l/tallcms/tallcms)](https://opensource.org/licenses/MIT)

A modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with a Filament admin panel and a daisyUI-powered block system.

## Two Ways to Use TallCMS

### 1. Standalone Application (Full CMS)

Get a complete CMS with themes, plugins, web installer, and auto-updates:

```bash
composer create-project tallcms/tallcms my-site
cd my-site
npm install && npm run build
php artisan serve
```

Visit `http://localhost:8000/install` to complete the web installer, then access the admin panel at `http://localhost:8000/admin`.

### 2. Filament Plugin (Add to Existing App)

Add CMS features to your existing Filament application:

```bash
composer require tallcms/cms
```

Add the `HasRoles` trait to your `User` model:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;
}
```

Register the plugin in your panel provider:

```php
use TallCms\Cms\TallCmsPlugin;

->plugin(TallCmsPlugin::make())
```

Run the installer:

```bash
php artisan tallcms:install
```

Access the admin panel at `https://yourdomain.com/admin`.

See the [Installation Guide](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md) for full setup instructions.

---

## Features

- **Web Installer** — Setup wizard with no command line required (standalone)
- **One-Click Updates** — Secure system updates with Ed25519 signature verification (standalone)
- **Block-Based Editor** — 16 built-in content blocks with animations and responsive design
- **Pages & Posts** — Static pages and blog posts with categories and templates
- **Publishing Workflow** — Draft, Pending Review, Scheduled, and Published states
- **Revision History** — Track changes with diff comparison and rollback
- **Preview System** — Preview unpublished content with shareable tokens
- **Media Library** — Organize uploads with collections and metadata
- **Menu Builder** — Drag-and-drop navigation menus with mega menu support
- **Comments** — Built-in comment system with moderation
- **Full-Text Search** — Laravel Scout-powered search across content
- **SEO** — Sitemaps, meta tags, Open Graph, and structured data
- **Internationalization** — Multi-language support via Spatie Translatable
- **Site Settings** — Per-site configuration with global defaults and inheritance
- **Role-Based Permissions** — Super Admin, Administrator, Editor, Author
- **Plugin System** — Extend functionality with installable plugins (standalone)
- **Theme System** — daisyUI presets or custom themes with template overrides (standalone)
- **REST API** — Optional Sanctum-authenticated API for headless usage
- **Cloud Storage** — S3-compatible storage (AWS, DigitalOcean, Cloudflare R2)
- **Maintenance Mode** — Built-in site maintenance with custom messaging
- **Multisite Ready** — Core Site model supports the [Multisite plugin](https://github.com/tallcms/multisite-plugin) for multi-domain SaaS

## System Requirements

- **PHP**: 8.2+
- **Laravel**: 12.x
- **Filament**: 5.x
- **Database**: MySQL 8.0+, MariaDB 10.3+, or SQLite

## Documentation

Full documentation lives in the [`docs/`](https://github.com/tallcms/tallcms/blob/main/docs/) folder. Start with the [Documentation Index](https://github.com/tallcms/tallcms/blob/main/docs/README.md).

### Getting Started

| Guide | Description |
|-------|-------------|
| [Installation](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md) | System requirements, standalone & plugin setup |
| [Create Your First Page](https://github.com/tallcms/tallcms/blob/main/docs/gs-first-page.md) | Build a page with content blocks |
| [Publish Your First Post](https://github.com/tallcms/tallcms/blob/main/docs/gs-first-post.md) | Write and publish a blog post |
| [Set Up Navigation](https://github.com/tallcms/tallcms/blob/main/docs/gs-menus.md) | Create site menus |

### Site Management

| Guide | Description |
|-------|-------------|
| [Pages & Posts](https://github.com/tallcms/tallcms/blob/main/docs/site-pages-posts.md) | Organize and manage content |
| [Content Blocks](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks.md) | Use Hero, Pricing, Gallery, and 13 more blocks |
| [Block Animations](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-animations.md) | Add entrance and scroll animations |
| [Media Library](https://github.com/tallcms/tallcms/blob/main/docs/site-media.md) | Upload and manage images and files |
| [Menus](https://github.com/tallcms/tallcms/blob/main/docs/site-menus.md) | Full navigation management guide |
| [Mega Menu](https://github.com/tallcms/tallcms/blob/main/docs/site-mega-menu.md) | Advanced navigation with badges, CTAs, templates |
| [Comments](https://github.com/tallcms/tallcms/blob/main/docs/site-comments.md) | Comment system and moderation |
| [SEO](https://github.com/tallcms/tallcms/blob/main/docs/site-seo.md) | Sitemaps, meta tags, structured data |
| [Site Settings](https://github.com/tallcms/tallcms/blob/main/docs/site-settings.md) | Logo, contact info, SPA mode |
| [Multisite](https://github.com/tallcms/tallcms/blob/main/docs/site-multisite.md) | Multi-domain site management |
| [Page Templates](https://github.com/tallcms/tallcms/blob/main/docs/site-templates.md) | Page templates and sidebar widgets |

### Block Reference

Each built-in block has its own guide:

| Block | | Block | |
|-------|-|-------|-|
| [Hero](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-hero.md) | Landing page headers | [Call to Action](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-cta.md) | Conversion sections |
| [Content](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-content.md) | Rich text content | [Features](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-features.md) | Feature grids |
| [Pricing](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pricing.md) | Pricing tables | [FAQ](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-faq.md) | Accordion Q&A |
| [Testimonials](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-testimonials.md) | Customer reviews | [Team](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-team.md) | Team member profiles |
| [Stats](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-stats.md) | Metrics display | [Media Gallery](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-media-gallery.md) | Image galleries |
| [Logos](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-logos.md) | Partner/client logos | [Timeline](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-timeline.md) | Chronological events |
| [Contact Form](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-contact-form.md) | Forms with notifications | [Posts](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-posts.md) | Recent blog posts |
| [Parallax](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-parallax.md) | Parallax scrolling | [Divider](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-divider.md) | Section separators |
| [Document List](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-document-list.md) | Downloadable files | | |

### Pro Blocks

Upgrade to **TallCMS Pro** for 9 additional blocks:

| Block | | Block | |
|-------|-|-------|-|
| [Accordion](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-accordion.md) | Collapsible sections | [Tabs](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-tabs.md) | Tabbed content |
| [Counter](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-counter.md) | Animated counters | [Table](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-table.md) | Data tables |
| [Comparison](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-comparison.md) | Side-by-side compare | [Video](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-video.md) | Video embeds |
| [Before/After](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-before-after.md) | Image slider | [Code Snippet](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-code-snippet.md) | Syntax-highlighted code |
| [Map](https://github.com/tallcms/tallcms/blob/main/docs/site-blocks-pro-map.md) | Interactive maps | | |

See the [Pro Plugin Changelog](https://github.com/tallcms/tallcms/blob/main/docs/ref-pro-plugin-changelog.md) for release history.

Learn more at [tallcms.com/pro](https://tallcms.com/pro).

### Developer Guides

| Guide | Description |
|-------|-------------|
| [Block Development](https://github.com/tallcms/tallcms/blob/main/docs/dev-blocks.md) | Build custom content blocks |
| [Block Styling](https://github.com/tallcms/tallcms/blob/main/docs/dev-block-styling.md) | daisyUI styling patterns for blocks |
| [Theme Development](https://github.com/tallcms/tallcms/blob/main/docs/dev-themes.md) | Create custom themes |
| [Theme Switcher](https://github.com/tallcms/tallcms/blob/main/docs/dev-theme-switcher.md) | Enable runtime theme switching |
| [Plugin Development](https://github.com/tallcms/tallcms/blob/main/docs/dev-plugins.md) | Extend TallCMS with plugins |
| [Template & Widget Development](https://github.com/tallcms/tallcms/blob/main/docs/dev-templates-widgets.md) | Custom templates and sidebar widgets |
| [Multisite Architecture](https://github.com/tallcms/tallcms/blob/main/docs/dev-multisite.md) | Multisite internals, settings policy, theme integration |
| [REST API](https://github.com/tallcms/tallcms/blob/main/docs/dev-api.md) | Build integrations with the TallCMS API |
| [CLI Commands](https://github.com/tallcms/tallcms/blob/main/docs/dev-cli-commands.md) | Artisan command reference |

### Reference

| Guide | Description |
|-------|-------------|
| [Architecture](https://github.com/tallcms/tallcms/blob/main/docs/ref-architecture.md) | Internal architecture and model patterns |
| [Publishing Workflow](https://github.com/tallcms/tallcms/blob/main/docs/ref-publishing.md) | Draft, review, schedule, publish lifecycle |
| [Page Settings](https://github.com/tallcms/tallcms/blob/main/docs/ref-page-settings.md) | All page configuration options |
| [Roles & Authorization](https://github.com/tallcms/tallcms/blob/main/docs/ref-roles-authorization.md) | Permissions and role definitions |
| [API Permissions](https://github.com/tallcms/tallcms/blob/main/docs/ref-api-permissions.md) | API token scopes and access control |
| [Rich Editor](https://github.com/tallcms/tallcms/blob/main/docs/ref-rich-editor.md) | Block categories, search, and editor features |
| [Full-Text Search](https://github.com/tallcms/tallcms/blob/main/docs/ref-search.md) | Search configuration and indexing |
| [Internationalization](https://github.com/tallcms/tallcms/blob/main/docs/ref-i18n.md) | Multi-language support setup |
| [Testing Checklist](https://github.com/tallcms/tallcms/blob/main/docs/testing-checklist.md) | QA testing procedures |

## Architecture

TallCMS is a monorepo. The core CMS package lives at `packages/tallcms/cms/` and is published separately as `tallcms/cms`.

```
tallcms/tallcms (Standalone skeleton)    tallcms/cms (Package)
├── app/                                 ├── src/
│   ├── Models/ (wrappers)               │   ├── Models/
│   ├── Services/                        │   ├── Services/
│   └── Filament/                        │   ├── Filament/
├── themes/                              │   │   ├── Blocks/
├── plugins/                             │   │   ├── Resources/
├── .tallcms-standalone                  │   │   └── Widgets/
└── ...                                  │   ├── Http/
                                         │   └── Console/Commands/
                                         └── database/migrations/
```

- **Standalone mode** — Full skeleton with themes, plugins, web installer, and auto-updates. Detected by the `.tallcms-standalone` marker file.
- **Plugin mode** — Just the CMS package added to your existing Filament app via `composer require tallcms/cms`.

See the [Architecture Reference](https://github.com/tallcms/tallcms/blob/main/docs/ref-architecture.md) for details.

## Updating

### Standalone Mode

Use the built-in update system in **Admin > System > Updates**, or via CLI:

```bash
php artisan tallcms:update
```

The command handles migrations, composer install, and cache clearing for you. **Never** run `composer update tallcms/cms` in standalone mode — the updater verifies signed release bundles, preserves your `.env`/themes/plugins/storage, and tracks a file manifest that direct composer updates would bypass.

### Plugin Mode

```bash
composer update tallcms/cms
php artisan migrate
php artisan view:clear
```

See the full [Updating Reference](https://github.com/tallcms/tallcms/blob/main/docs/ref-updating.md) for preserved paths, rollback procedure, CLI options, and troubleshooting.

## Troubleshooting

Common issues and solutions are documented in the [Installation Guide](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md).

**Quick fixes:**
- **"Where is the admin panel?"** — Go to `/admin` (e.g., `https://yourdomain.com/admin`)
- **"Page not showing changes"** — `php artisan cache:clear && php artisan view:clear`
- **"Styles look broken"** — `npm run build`
- **"Installation already complete"** — Delete `storage/installer.lock` or set `INSTALLER_ENABLED=true`
- **"Permission denied"** — `chmod -R 775 storage bootstrap/cache`
- **"CMS resources not appearing"** — Ensure the plugin is registered and run `php artisan migrate`
- **"Call to undefined method assignRole()"** — Add the `HasRoles` trait to your User model
- **"Site Settings shows migration error"** — Run `php artisan migrate` to create the sites table (required since v4.0)

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

## License

TallCMS is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Links

- **Website**: [tallcms.com](https://tallcms.com)
- **Documentation**: [tallcms.com/docs](https://tallcms.com/docs)
- **GitHub**: [github.com/tallcms/tallcms](https://github.com/tallcms/tallcms)
- **Package**: [github.com/tallcms/cms](https://github.com/tallcms/cms)
- **Roadmap**: [ROADMAP.md](https://github.com/tallcms/tallcms/blob/main/ROADMAP.md)
- **Support**: hello@tallcms.com
