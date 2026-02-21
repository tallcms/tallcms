# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TallCMS is a Laravel-based CMS built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) with a Filament v5 admin panel and daisyUI-powered block system. It operates in two modes:

- **Standalone mode** — Full CMS with themes, plugins, web installer, and auto-updates. Detected by the `.tallcms-standalone` marker file at the project root.
- **Plugin mode** — Adds CMS features to an existing Filament app via `composer require tallcms/cms`.

## Common Commands

### Development
```bash
composer run dev          # Starts server, queue, logs (pail), and Vite concurrently
composer run setup        # Full setup: install, migrate, build
npm run dev               # Vite dev server only
npm run build             # Production build
```

### Testing
```bash
composer run test                              # Clears config cache, then runs all tests
php artisan test                               # Run all tests directly
php artisan test tests/Feature/SomeTest.php    # Run a single test file
php artisan test --filter=SomeTest             # Filter by test name
```

Tests use SQLite in-memory database, array cache, and sync queue (configured in `phpunit.xml`).

**Package tests** (CI runs these across PHP 8.2-8.4 + Laravel 11-12):
```bash
cd packages/tallcms/cms && vendor/bin/phpunit
```

### Linting
```bash
./vendor/bin/pint          # Laravel Pint (PHP CS Fixer)
```

### Useful Artisan Commands
```bash
php artisan make:tallcms-block BlockName    # Generate a custom content block
php artisan make:theme ThemeName            # Generate a theme (standalone)
php artisan search:index                    # Rebuild search index
php artisan tallcms:install                 # Full CMS setup
```

## Architecture

### Monorepo Structure

The repository is a monorepo. The core CMS package lives at `packages/tallcms/cms/` and is symlinked via Composer path repository. It is automatically split and synced to `tallcms/cms` on GitHub via CI workflows.

```
tallcms/                          # Standalone skeleton app
├── packages/tallcms/cms/         # Core CMS package (→ tallcms/cms repo)
│   ├── src/
│   │   ├── Models/               # Core Eloquent models
│   │   ├── Services/             # Business logic services
│   │   ├── Filament/             # Admin panel resources, pages, blocks, widgets
│   │   ├── Http/                 # API controllers, middleware
│   │   ├── Console/Commands/     # Artisan commands
│   │   ├── View/Components/      # Blade components
│   │   └── Providers/            # TallCmsServiceProvider
│   ├── database/migrations/      # CMS table migrations
│   ├── config/tallcms.php        # Package config
│   └── resources/                # Package views
├── app/                          # Standalone app wrappers
│   ├── Models/                   # Wrappers extending package models
│   ├── Filament/Blocks/          # Custom blocks
│   └── Services/                 # App-level services
├── themes/                       # Theme directory (standalone only)
└── plugins/                      # Plugin directory (standalone only)
```

### Model Wrapper Pattern

Standalone `app/Models/` classes wrap package models (`TallCms\Cms\Models\*`) for customization while maintaining factory compatibility. The package models contain the actual logic.

### Key Models and Traits

- `CmsPage`, `CmsPost` — Content types with publishing workflow, revisions, preview tokens
- `CmsCategory` — Hierarchical content categories
- `TallcmsMenu` / `TallcmsMenuItem` — Navigation menus (nested set pattern)
- `TallcmsMedia` / `MediaCollection` — Media library

Reusable model traits in `src/Models/Concerns/`:
- `HasPublishingWorkflow` — Draft → Pending Review → Scheduled → Published state machine
- `HasRevisions` — Automatic revision tracking with diff snapshots
- `HasPreviewTokens` — Shareable preview links with expiration
- `HasSearchableContent` — Full-text search via Laravel Scout (database driver)
- `HasTranslatableContent` — Multi-language support via Spatie Laravel Translatable

### Content Blocks

16 built-in blocks (Hero, CTA, Features, Pricing, FAQ, etc.). Custom blocks are generated with `make:tallcms-block` and placed in `app/Filament/Blocks/`. Each block has a Filament form schema class and a corresponding Blade view in `resources/views/cms/blocks/`.

### Filament Resources

Resources are organized with separate Form, Table, and Pages classes under `src/Filament/Resources/`. They use the LaraZeus translatable trait and Filament Shield for permissions.

### Service Provider

`TallCmsServiceProvider` (using Spatie Laravel Package Tools) auto-discovers and registers migrations, commands, routes, views, Livewire components, and class aliases. Mode-specific features (themes, plugins, updates) are conditionally booted based on standalone detection.

### Frontend Assets

Vite entry points (see `vite.config.js`):
- `resources/css/app.css` — Tailwind + daisyUI frontend styles
- `resources/js/app.js` — Alpine.js
- `resources/css/filament/admin/theme.css` — Filament admin theme
- `resources/css/filament/admin/preview.css` — Block preview styles

### Routes

- Frontend (standalone): `/`, `/{slug}`, `/preview/*`, `/feed`, `/category/*`, `/author/*`
- API (opt-in): `/api/v1/tallcms/*` with Sanctum token auth
- Admin: `/admin` (Filament)

### Database

All CMS tables are prefixed with `tallcms_`. Translatable columns (title, slug, content, meta fields) are stored as JSON. Migrations live in `packages/tallcms/cms/database/migrations/`.

## Tech Stack

- **PHP 8.2+**, Laravel 12, Filament 5, Livewire 4
- **Frontend**: Tailwind CSS 4 + daisyUI 5, Alpine.js 3, Vite 7
- **Permissions**: Spatie Laravel Permission + Filament Shield
- **Search**: Laravel Scout with database driver (required)
- **Auth**: Laravel Sanctum for API tokens
