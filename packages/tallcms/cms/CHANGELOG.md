# Changelog

All notable changes to TallCMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-01-16

### Added

- **Filament Plugin Architecture** - TallCMS can now be installed as a Filament plugin in existing applications
- `TallCmsPlugin` class for registering CMS components with Filament panels
- Selective component registration (`withoutPages()`, `withoutPosts()`, etc.)
- Multi-panel support with dynamic URL generation
- Plugin mode configuration options
- Custom user model support via `plugin_mode.user_model` config
- Class aliases for backwards compatibility with `App\*` namespaces
- 22 publishable migrations for CMS tables
- Comprehensive documentation

### Changed

- Extracted all CMS functionality to `tallcms/cms` Composer package
- Moved models, services, events, and exceptions to `TallCms\Cms` namespace
- Filament resources, pages, and widgets now in package namespace
- Policies use `Authenticatable` interface instead of concrete User class
- Internal URLs use `Page::getUrl()` for multi-panel compatibility
- Standalone-only features (updates, themes, plugins) gated by mode detection

### Migration Guide

**For Standalone Users:**
- No action required - the package auto-detects standalone mode
- All existing customizations in `app/` continue to work
- Class aliases ensure backwards compatibility

**For New Plugin Users:**
1. `composer require tallcms/cms`
2. `php artisan vendor:publish --tag=tallcms-migrations`
3. `php artisan migrate`
4. Register `TallCmsPlugin::make()` in your panel provider

## [1.2.0] - 2026-01-15

### Added

- One-click system updates with Ed25519 signature verification
- Admin panel update checker with GitHub integration
- Automatic file and database backup before updates
- Progress tracking UI with real-time status
- Manual CLI fallback when automated methods unavailable
- Stale lock recovery mechanism

### Security

- Ed25519 cryptographic signatures for release verification
- Manifest-based integrity checking with SHA256 checksums

## [1.1.0] - 2026-01-10

### Added

- daisyUI 5 integration for theme styling
- 30+ theme presets available
- Runtime theme switching
- Improved dark mode support

### Changed

- All blocks refactored to use daisyUI semantic classes
- Shared node_modules between themes and root

## [1.0.0] - 2026-01-01

### Added

- Initial release
- Pages and Posts with rich content editor
- 16 built-in content blocks
- Publishing workflow (Draft, Pending Review, Scheduled, Published)
- Revision history with diff comparison
- Preview tokens for unpublished content
- Media library with collections
- Menu builder with drag-and-drop
- Site settings management
- Contact form with email notifications
- Multi-theme architecture
- Plugin system with security guardrails
- Role-based access control via Filament Shield
- Web installer for easy setup
- Cloud storage support (S3, DigitalOcean, Cloudflare R2)
