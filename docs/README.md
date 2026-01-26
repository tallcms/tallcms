# TallCMS Documentation

Welcome to TallCMS — a modern, block-based CMS built on Laravel and Filament.

---

## Choose Your Path

### New to TallCMS?

| Guide | Time | What You'll Do |
|-------|------|----------------|
| [Installation](installation) | 10 min | Get TallCMS running |
| [Create Your First Page](first-page) | 5 min | Build a page with blocks |
| [Publish Your First Post](first-post) | 5 min | Write and publish a blog post |
| [Set Up Navigation](quick-menus) | 5 min | Create site menus |

### Managing Your Site

| Guide | Description |
|-------|-------------|
| [Pages & Posts](pages-posts) | Organize your content |
| [Content Blocks](blocks) | Use Hero, Pricing, Gallery, and more |
| [Media Library](media) | Upload and manage images |
| [SEO](seo) | Sitemaps, meta tags, structured data |
| [Menus](menus) | Full navigation guide |
| [Site Settings](site-settings) | Logo, contact info, SPA mode |

### Building Themes & Plugins

| Guide | Description |
|-------|-------------|
| [Theme Development](themes) | Create custom themes |
| [Plugin Development](plugins) | Extend TallCMS functionality |
| [Block Development](block-development) | Build custom content blocks |
| [Block Styling](block-styling) | daisyUI styling patterns |

### Reference

| Guide | Description |
|-------|-------------|
| [Page Settings](page-settings) | All page configuration options |
| [Publishing Workflow](publishing) | Draft, review, schedule, publish |
| [Internationalization](i18n) | Multi-language support |
| [Rich Editor](rich-editor) | Block categories and search |
| [Architecture](architecture) | Internal developer reference |

---

## Quick Links

- **Admin Panel**: `/admin`
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
Complete installation first, then visit `/admin` with your admin credentials.

---

## Getting Help

- Check the **Common Pitfalls** section in each guide
- Open an issue on [GitHub](https://github.com/tallcms/tallcms/issues)
