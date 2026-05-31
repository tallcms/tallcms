# TallCMS

[![Packagist Version](https://img.shields.io/packagist/v/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![License](https://img.shields.io/packagist/l/tallcms/tallcms)](https://opensource.org/licenses/MIT)

A modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with a Filament admin panel and a daisyUI-powered block system.

## Installation

### Standalone (full CMS)

```bash
composer create-project tallcms/tallcms my-site
cd my-site && npm install && npm run build
php artisan serve
```

Visit `/install` to run the web installer, then `/admin`.

### Plugin (add CMS to your existing Filament app)

```bash
composer require tallcms/cms
php artisan tallcms:install
```

Register the plugin in your panel provider:

```php
->plugin(TallCmsPlugin::make())
```

Add the `HasRoles` trait to your `User` model.

Full guide: [Installation](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md).

## System Requirements

- PHP 8.2+ (Laravel 13 requires PHP 8.3+)
- Laravel 12 or 13, Filament 5
- MySQL 8 / MariaDB 10.3 / SQLite
- Node 20+ (for building assets)

> **Laravel 13:** the core CMS (`tallcms/cms`) supports Laravel 13. The
> standalone skeleton currently resolves on Laravel 12 until
> [`tallcms/filament-registration`](https://packagist.org/packages/tallcms/filament-registration)
> ships a Laravel 13–compatible release, after which `composer update` picks
> up Laravel 13 automatically. Tracking in
> [#61](https://github.com/tallcms/tallcms/issues/61).

## Documentation

Full documentation lives in the [docs/](docs/) directory. Highlights:

- [Installation](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md)
- [Updating](https://github.com/tallcms/tallcms/blob/main/docs/ref-updating.md)
- [Architecture](https://github.com/tallcms/tallcms/blob/main/docs/ref-architecture.md)
- [Blocks](https://github.com/tallcms/tallcms/blob/main/docs/dev-blocks.md)
- [Themes](https://github.com/tallcms/tallcms/blob/main/docs/dev-themes.md)
- [Plugins](https://github.com/tallcms/tallcms/blob/main/docs/dev-plugins.md)
- [API](https://github.com/tallcms/tallcms/blob/main/docs/dev-api.md)

## Commercial add-ons

- **[Multisite](https://tallcms.com/saas-multisite-plugin)** — run multiple sites (each with its own domain, theme, settings, and content) from a single TallCMS install. Built for agencies and SaaS operators.

Browse the full catalog at [tallcms.com/marketplace](https://tallcms.com/marketplace).

## Need Help?

🐞 Bug? [Open an issue](https://github.com/tallcms/tallcms/issues/new).
🤔 Question or feature request? [Start a discussion](https://github.com/tallcms/tallcms/discussions).
🔐 Security issue? Email hello@tallcms.com. Do not file publicly.

## License

MIT — see [LICENSE](LICENSE).

## Links

- Website: https://tallcms.com
- Packagist: https://packagist.org/packages/tallcms/tallcms
