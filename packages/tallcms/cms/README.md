# TallCMS

[![Packagist Version](https://img.shields.io/packagist/v/tallcms/cms)](https://packagist.org/packages/tallcms/cms)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tallcms/cms)](https://packagist.org/packages/tallcms/cms)
[![License](https://img.shields.io/packagist/l/tallcms/cms)](https://opensource.org/licenses/MIT)

A modern Content Management System package for Laravel Filament. Adds pages, posts, a block-based editor, media library, menus, comments, and forms to your existing Filament application.

> This repository is a **read-only subtree split** of the [tallcms/tallcms](https://github.com/tallcms/tallcms) monorepo, updated automatically via CI. File issues, PRs, and find full documentation at [tallcms/tallcms](https://github.com/tallcms/tallcms).
>
> For a full **standalone** CMS with themes, plugins, and auto-updates, see [tallcms/tallcms](https://github.com/tallcms/tallcms).

## Installation

```bash
composer require tallcms/cms
php artisan tallcms:install
```

Register the plugin in your panel provider:

```php
->plugin(TallCmsPlugin::make())
```

Add the `HasRoles` trait to your `User` model:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;
}
```

Full guide: [Installation](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md).

## Requirements

- PHP 8.2+
- Laravel 11 or 12, Filament 5
- MySQL 8 / MariaDB 10.3 / SQLite

> **Laravel 13 is not yet supported.** Blocked upstream on
> [`lazychaser/laravel-nestedset`](https://github.com/lazychaser/laravel-nestedset)
> (caps at `illuminate/support <=12.0`). Tracking in
> [tallcms/tallcms#61](https://github.com/tallcms/tallcms/issues/61).

## Documentation

Documentation lives in the monorepo's [docs/](https://github.com/tallcms/tallcms/tree/main/docs) directory. Highlights:

- [Installation](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md)
- [Architecture](https://github.com/tallcms/tallcms/blob/main/docs/ref-architecture.md)
- [Blocks](https://github.com/tallcms/tallcms/blob/main/docs/dev-blocks.md)
- [Themes](https://github.com/tallcms/tallcms/blob/main/docs/dev-themes.md)
- [Plugins](https://github.com/tallcms/tallcms/blob/main/docs/dev-plugins.md)
- [API](https://github.com/tallcms/tallcms/blob/main/docs/dev-api.md)

## Commercial add-ons

- **[Multisite](https://tallcms.com/multisite)** — run multiple sites (each with its own domain, theme, settings, and content) from a single TallCMS install. Built for agencies and SaaS operators.

Browse the full catalog at [tallcms.com/marketplace](https://tallcms.com/marketplace).

## Need Help?

🐞 Bug? [Open an issue](https://github.com/tallcms/tallcms/issues/new).
🤔 Question or feature request? [Start a discussion](https://github.com/tallcms/tallcms/discussions).
🔐 Security issue? Email hello@tallcms.com. Do not file publicly.

## License

MIT — see [LICENSE](LICENSE).

## Links

- Website: https://tallcms.com
- Packagist: https://packagist.org/packages/tallcms/cms
- Monorepo: https://github.com/tallcms/tallcms
