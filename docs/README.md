# TallCMS Documentation

Welcome to TallCMS — a modern, block-based CMS built on Laravel and Filament.

---

## Choose Your Path

### New to TallCMS?

| Guide | Time | What You'll Do |
|-------|------|----------------|
| [Installation](gs-installation.md) | 10 min | Get TallCMS running |
| [Create Your First Page](gs-first-page.md) | 5 min | Build a page with blocks |
| [Publish Your First Post](gs-first-post.md) | 5 min | Write and publish a blog post |
| [Set Up Navigation](gs-menus.md) | 5 min | Create site menus |

### Managing Your Site

| Guide | Description |
|-------|-------------|
| [Pages & Posts](site-pages-posts.md) | Organize your content |
| [Content Blocks](site-blocks.md) | Use Hero, Pricing, Gallery, and more |
| [Media Library](site-media.md) | Upload and manage images |
| [SEO](site-seo.md) | Sitemaps, meta tags, structured data |
| [Menus](site-menus.md) | Full navigation guide |
| [Mega Menu](site-mega-menu.md) | Advanced navigation with badges, CTAs, templates |
| [Site Settings](site-settings.md) | Logo, contact info, SPA mode |
| [Code Injection](site-code-injection.md) | Analytics, tracking scripts, chat widgets |
| [Multisite Setup](site-multisite.md) | Run multiple sites — single-tenant up to full self-serve SaaS |

### Building Themes & Plugins

| Guide | Description |
|-------|-------------|
| [Theme Development](dev-themes.md) | Create custom themes |
| [Theme Switcher](dev-theme-switcher.md) | Enable runtime theme switching |
| [Plugin Development](dev-plugins.md) | Extend TallCMS functionality |
| [Block Development](dev-blocks.md) | Build custom content blocks |
| [Block Styling](dev-block-styling.md) | daisyUI styling patterns |

### Reference

| Guide | Description |
|-------|-------------|
| [Page Settings](ref-page-settings.md) | All page configuration options |
| [Publishing Workflow](ref-publishing.md) | Draft, review, schedule, publish |
| [Internationalization](ref-i18n.md) | Multi-language support |
| [Rich Editor](ref-rich-editor.md) | Block categories and search |
| [Redirects](ref-redirects.md) | Manage 301/302 redirects for SEO and migrations |
| [Filament Ecosystem](ref-filament-ecosystem.md) | Use hundreds of Filament plugins with TallCMS |
| [Architecture](ref-architecture.md) | Internal developer reference |
| [Multisite Architecture](dev-multisite.md) | Site model, settings inheritance, scoping internals |
| [Billing Plugin (Stripe)](dev-billing.md) | Operator runbook for Cashier-backed paid plans |

---

## Quick Links

- **GitHub**: [tallcms/tallcms](https://github.com/tallcms/tallcms)
- **Packagist**: [tallcms/cms](https://packagist.org/packages/tallcms/cms)

---

## Common Pitfalls

**"Page not showing changes"**
Clear the cache: `php artisan cache:clear && php artisan view:clear`

**"Styles look broken"**
Rebuild assets: `npm run build`

**"Migration errors"**
Run migrations: `php artisan migrate`

**"Admin panel not accessible"**
Complete installation first, then visit your panel URL (defaults to `/admin`) with your admin credentials.

---

## Getting Help

- Check the **Common Pitfalls** section in each guide
- Open an issue on [GitHub](https://github.com/tallcms/tallcms/issues)
