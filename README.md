# TallCMS™

[![Packagist Version](https://img.shields.io/packagist/v/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tallcms/tallcms)](https://packagist.org/packages/tallcms/tallcms)
[![License](https://img.shields.io/packagist/l/tallcms/tallcms)](https://opensource.org/licenses/MIT)

A modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with a Filament v4 admin panel and a daisyUI-powered block system.

```bash
composer create-project tallcms/tallcms my-site
```

---

### 🤖 Built with AI

TallCMS is proudly **co-developed with [Claude AI](https://claude.ai)** (Anthropic) and **code-reviewed by [Codex](https://openai.com/index/openai-codex/)** (OpenAI).

This project demonstrates what's possible when human creativity meets AI capability - from architecture design to security reviews, AI collaboration accelerated development while maintaining code quality.

---

## Features

- **Web Installer** - Setup wizard with no command line required
- **One-Click Updates** - Secure system updates with Ed25519 signature verification
- **Rich Content Editor** - daisyUI-first blocks with merge tags and device preview
- **Hierarchical Pages & Posts** - SEO optimization and revision history
- **Drag & Drop Menu Builder** - Nested navigation with multiple locations
- **Role-Based Permissions** - Super Admin, Administrator, Editor, Author
- **Plugin System** - Extend functionality with installable plugins
- **Theme System** - daisyUI presets or custom themes with template overrides
- **Theme Controller** - Optional runtime theme switching
- **Cloud Storage** - S3-compatible storage (AWS, DigitalOcean, Cloudflare R2)
- **Maintenance Mode** - Built-in site maintenance with custom messaging

## System Requirements

- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+, MariaDB 10.3+, or SQLite
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo, GD

## Installation

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

## Built-in Blocks

| Block | Description |
|-------|-------------|
| **Hero** | Landing page headers with CTAs and background images |
| **Call-to-Action** | Conversion-optimized promotional sections |
| **Image Gallery** | Lightbox galleries with grid/masonry layouts |
| **Contact Form** | Dynamic forms with email notifications |
| **Content** | Article content with headings and rich text |
| **FAQ** | Accordion-style frequently asked questions |
| **Features** | Feature grids with icons and descriptions |
| **Pricing** | Pricing tables with feature comparison |
| **Team** | Team member profiles with social links |
| **Testimonials** | Customer testimonials with ratings |
| **Timeline** | Chronological event displays |
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

## System Updates

TallCMS includes a one-click update system accessible via **Settings → System Updates** in the admin panel.

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
- **Roadmap**: [View our roadmap](ROADMAP.md)
- **Documentation**: [tallcms.com/docs](https://tallcms.com/docs)
- **Support**: hello@tallcms.com
