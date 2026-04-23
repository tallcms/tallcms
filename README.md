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

- PHP 8.2+
- Laravel 12, Filament 5
- MySQL 8 / MariaDB 10.3 / SQLite
- Node 20+ (for building assets)

## Documentation

Full documentation lives in the [docs/](docs/) directory. Highlights:

- [Installation](https://github.com/tallcms/tallcms/blob/main/docs/gs-installation.md)
- [Updating](https://github.com/tallcms/tallcms/blob/main/docs/ref-updating.md)
- [Architecture](https://github.com/tallcms/tallcms/blob/main/docs/ref-architecture.md)
- [Blocks](https://github.com/tallcms/tallcms/blob/main/docs/dev-blocks.md)
- [Themes](https://github.com/tallcms/tallcms/blob/main/docs/dev-themes.md)
- [Plugins](https://github.com/tallcms/tallcms/blob/main/docs/dev-plugins.md)
- [API](https://github.com/tallcms/tallcms/blob/main/docs/dev-api.md)

## Need Help?

🐞 Bug? [Open an issue](https://github.com/tallcms/tallcms/issues/new).
🤔 Question or feature request? [Start a discussion](https://github.com/tallcms/tallcms/discussions).
🔐 Security issue? Email security@tallcms.com. Do not file publicly.

## License

MIT — see [LICENSE](LICENSE).

## Links

- Website: https://tallcms.com
- Packagist: https://packagist.org/packages/tallcms/tallcms
