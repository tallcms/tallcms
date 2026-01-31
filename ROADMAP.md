# TallCMS Roadmap

This roadmap outlines our vision for TallCMS development. We're committed to building the best TALL stack CMS and being transparent about our progress.

> **Note:** This roadmap reflects our current plans and priorities. Items may be added, removed, or reordered based on community feedback and technical considerations.

## Quick Navigation

- [v1.0 - Foundation](#v10---foundation) ✅
- [v1.1 - UI Polish](#v11---ui-polish) ✅
- [v1.2 - Content & SEO](#v12---content--seo) ✅
- [v2.0 - Filament Plugin Architecture](#v20---filament-plugin-architecture) ✅
- [v2.4 - Multilingual](#v24---multilingual-support) ✅
- [v2.5 - Page Settings & Breadcrumbs](#v25---page-settings--breadcrumbs) ✅
- [v2.6 - Full-Text Search](#v26---full-text-search) ✅
- [v2.7 - Media Library](#v27---media-library) ✅
- [v2.8 - REST API](#v28---rest-api) ✅
- [v2.9 - Plugin Manager](#v29---plugin-manager) ✅
- [v2.10 - Community & Users](#v210---community--users) 📋
- [v2.11 - Marketplace](#v211---marketplace-integration) 📋
- [Future Considerations](#future-considerations)
- [How to Contribute](#how-to-contribute)

---

## v1.0 - Foundation

The initial release establishing TallCMS as a fully-featured content management system built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) with Filament v4.

### Core CMS
- [x] Pages with rich content editor
- [x] Posts with categories
- [x] Hierarchical categories
- [x] Publishing workflow (Draft → Pending Review → Published)
- [x] Scheduled publishing
- [x] Revision history with diff comparison
- [x] Manual snapshots (pinned revisions)
- [x] Preview tokens for unpublished content
- [x] Merge tags system (dynamic content substitution)
- [x] SEO fields (meta description, canonical URL)
- [x] Featured images

### Content Blocks
- [x] Hero sections with CTAs
- [x] Call-to-Action blocks
- [x] Content/Article blocks
- [x] Contact Form (with email delivery)
- [x] Divider
- [x] FAQ (accordion style)
- [x] Features grid
- [x] Image Gallery with lightbox
- [x] Logo showcase
- [x] Parallax sections
- [x] Posts listing
- [x] Pricing tables
- [x] Statistics/Metrics
- [x] Team profiles
- [x] Testimonials
- [x] Timeline

### Admin Panel
- [x] Modern Filament v4 interface
- [x] Dashboard with widgets
- [x] Media Library with collections
- [x] Menu Builder (drag-drop, multiple locations)
- [x] Site Settings
- [x] Contact Form Submissions

### Theme System
- [x] Multi-theme architecture
- [x] File-based themes with independent builds
- [x] Template override system
- [x] Theme Manager UI (gallery, preview, rollback)
- [x] ZIP-based theme upload
- [x] Tailwind CSS 4.0 support
- [x] Dark mode support

### Plugin System
- [x] File-based plugin architecture
- [x] Custom blocks via plugins
- [x] Plugin routes and admin pages
- [x] Plugin Manager UI
- [x] ZIP-based plugin upload
- [x] License validation (Anystack.sh)
- [x] Security guardrails

### Users & Permissions
- [x] Role-based access control (Filament Shield)
- [x] 4 default roles (Super Admin, Administrator, Editor, Author)
- [x] Fine-grained permissions
- [x] Multi-factor authentication (TOTP)

### Infrastructure
- [x] Web installer (browser-based setup wizard)
- [x] Cloud storage support (S3, DigitalOcean, Cloudflare R2, etc.)
- [x] Multiple mail providers (SMTP, SES, Sendmail)
- [x] Maintenance mode
- [x] SQLite, MySQL, MariaDB support

---

## v1.1 - UI Polish

Focus: Enhanced theming with daisyUI 5 component library for beautiful, consistent UI.

### Theme Enhancements
- [x] daisyUI 5 integration
- [x] Semantic CSS classes across all blocks
- [x] 30+ daisyUI theme presets available
- [x] Runtime theme switching
- [x] Improved dark mode support
- [x] 2 included themes (TallDaisy, Canopy)

### Block Improvements
- [x] All blocks refactored to use daisyUI semantic classes
- [x] Text color support in Hero block
- [x] Secondary button support in Call-to-Action block
- [x] Menu components using daisyUI classes

### Developer Experience
- [x] Shared node_modules between themes and root
- [x] Updated theme development documentation
- [x] Vite 7 manifest path improvements

---

## v1.2 - Content & SEO

Focus: Complete the blogging experience, SEO tools, and one-click system updates.

### Blog Frontend
- [x] Public blog listing page (via Posts block)
- [x] Individual post pages with SEO-friendly URLs
- [x] Category archive pages
- [x] Author archive pages
- [x] Pagination
- [x] RSS feed (main and per-category)

### SEO Enhancements
- [x] XML sitemap generation (with chunking for large sites)
- [x] robots.txt editor
- [x] Open Graph meta tags
- [x] Twitter Card meta tags
- [x] Structured data (JSON-LD) for articles

### Content Improvements
*Deferred to [v2.7](#v27---content--media)*

### Media Library
*Deferred to [v2.7](#v27---content--media)*

### System Updates
- [x] Admin panel update checker with GitHub integration
- [x] Ed25519 signature verification (pure PHP, no extensions needed)
- [x] One-click updates with exec → queue → CLI fallback
- [x] Automatic file backup before update
- [x] Database backup (SQLite copy, mysqldump, pg_dump)
- [x] Manifest-based conflict detection with quarantine (no auto-delete)
- [x] Platform compatibility checks (PHP version, extensions)
- [x] Stale lock recovery mechanism
- [x] Progress tracking UI with real-time status
- [x] Manual CLI instructions when automated methods unavailable
- [x] CLI update command (php artisan tallcms:update)
- [x] Release signing workflow (GitHub Actions)

---

## v2.0 - Filament Plugin Architecture

**Status: ✅ Released (v2.0.0 - v2.3.x)**

Focus: Restructure TallCMS as a Filament plugin for broader reach and easier adoption.

### Core Package Split
- [x] Extract core CMS to `tallcms/cms` Composer package
- [x] Filament plugin registration and configuration (`TallCmsPlugin`)
- [x] `composer require tallcms/cms` support
- [x] Publish views, migrations, and config
- [x] Monorepo with automatic subtree split (GitHub Action)

### Distribution Options
- [x] **Standalone**: Full TallCMS skeleton (`tallcms/tallcms`)
- [x] **Plugin**: Add to existing Filament apps (`tallcms/cms`)
- [x] Web installer for standalone installations
- [x] Migration path from v1.x (class aliases for backwards compatibility)

### Installation Experience
- [x] Auto-register pages, resources, and widgets via `TallCmsPlugin`
- [x] Configurable URL prefixes (`plugin_mode.routes_prefix`)
- [x] Separate SEO routes control (`seo_routes_enabled`, always at root)
- [x] Opt-in archive routes with prefix (`archive_routes_enabled`, `archive_routes_prefix`)
- [x] Selective component registration (`withoutPages()`, `withoutPosts()`, etc.)
- [x] Multi-panel support with dynamic URL generation
- [x] Asset publishing and customization

### Backwards Compatibility
- [x] All v1.x themes continue working (standalone mode)
- [x] All v1.x plugins continue working (standalone mode)
- [x] Existing content and data preserved
- [x] Class aliases for `App\*` namespace compatibility

---

## v2.4 - Multilingual Support

**Status: ✅ Released (v2.4.0 - v2.4.1)**

Focus: Make TallCMS accessible to global audiences.

### Core Translation
- [x] Multi-language content fields (Spatie Laravel Translatable)
- [x] Language switcher in admin (LaraZeus SpatieTranslatable)
- [x] Translated URL slugs (`/en/about`, `/zh-CN/about`)
- [x] Locale prefix routing with middleware
- [x] hreflang tag generation component
- [x] Default language fallback
- [x] Hide default locale from URL option

### Translation Workflow
- [x] Auto-populate translations from default locale when empty
- [x] "Copy from default" button for manual re-copying
- [x] Confirmation dialog before overwriting existing translations
- [x] LocaleRegistry service for managing available locales
- [x] Config-based locale definitions with RTL support

### Localized URLs
- [x] `tallcms_localized_url()` helper function
- [x] `tallcms_localized_route()` helper function
- [x] Language switcher Blade component
- [x] Automatic locale detection from URL

### Remaining Items
- [ ] Translation status indicators (% complete per locale)
- [ ] Admin interface translations (i18n for admin UI)

### TallCMS Pro: Advanced Translation
The following features are planned for **TallCMS Pro**:
- [ ] Side-by-side translation editor
- [ ] Machine translation hooks (OpenAI, DeepL, Google Translate)
- [ ] Translation memory and glossary support
- [ ] Bulk translation workflows

---

## v2.5 - Page Settings & Breadcrumbs

**Status: ✅ Released (v2.5.0)**

Focus: Enhanced page configuration and navigation aids.

### Page Settings
- [x] Per-page layout selection
- [x] Show/hide header option
- [x] Show/hide footer option
- [x] Custom CSS class support

### Breadcrumbs
- [x] Automatic breadcrumb generation
- [x] Page hierarchy support
- [x] Configurable breadcrumb display
- [x] Theme-aware breadcrumb styling

---

## v2.6 - Full-Text Search

**Status: ✅ Released (v2.6.0)**

Focus: Enable content discovery across Pages and Posts.

### Search Features
- [x] Laravel Scout integration (database driver)
- [x] Frontend search page at `/search` with live results
- [x] Filament global search in admin header
- [x] Denormalized `search_content` column for cross-database compatibility
- [x] Multilingual search across all locales
- [x] XSS-safe highlighted excerpts
- [x] Type filtering (All, Pages, Posts)
- [x] Pagination

### Configuration
- [x] Configurable via `tallcms.search` config
- [x] Respects plugin mode route prefixes
- [x] Respects i18n URL strategies (prefix and query param)
- [x] Configurable route name prefix

### Commands
- [x] `php artisan tallcms:search-index` - Rebuild search index
- [x] Model-specific indexing (`--model=page` or `--model=post`)

### Theme Integration
- [x] `<x-tallcms::search-input />` Blade component
- [x] `tallcms_search_url()` helper function
- [x] TallDaisy theme header search (desktop inline, mobile modal)

---

## v2.7 - Media Library

**Status: ✅ Released (v2.7.0)**

Focus: Complete Media Library overhaul with image optimization and block integration.

### Image Optimization
- [x] Intervention Image 3.0 integration
- [x] Automatic WebP variant generation (thumbnail, medium, large)
- [x] S3/remote disk support (download → process → upload)
- [x] Queued optimization jobs
- [x] Variant cleanup on media deletion

### Lazy Loading
- [x] Native lazy loading (`loading="lazy"`) on all block images
- [x] `<picture>` element with WebP source and fallback
- [x] `<x-tallcms::responsive-image />` Blade component

### Alt Text Management
- [x] Enhanced alt text UI with character counter
- [x] "Generate from filename" action button
- [x] Bulk alt text editor for multiple images
- [x] Missing alt text filter for accessibility audits

### Bulk Upload Improvements
- [x] Collection assignment during upload
- [x] Auto-generate alt text from original filename
- [x] Preserve original filenames (not hashed)
- [x] Optimization job dispatch after upload

### Block Integration
- [x] MediaResolver service for collection queries
- [x] Media Gallery block (renamed from Image Gallery)
- [x] Dual source mode: manual upload or media collections
- [x] Video support with lightbox playback
- [x] Document List block for downloadable files

### Collections Management
- [x] Dedicated Collections resource page
- [x] Color-coded collection badges
- [x] Media count per collection
- [x] Click-through to filtered media view

### Security
- [x] Signed URL media downloads (24h expiry)
- [x] Prevents ID guessing attacks on private files

### Configuration
- [x] `tallcms.media.optimization.enabled` toggle
- [x] `tallcms.media.optimization.queue` setting
- [x] Configurable variant sizes

### Content Improvements (Deferred)
*The following items are deferred to a future release:*
- [ ] Content scheduling calendar view
- [ ] Bulk actions (publish, unpublish, delete)
- [ ] Content templates (save block layouts for reuse)
- [ ] Import/Export content (JSON/Markdown)

---

## v2.8 - REST API

**Status: ✅ Released (v2.8.0)**

Focus: Full REST API for headless CMS usage and third-party integrations.

### REST API
- [x] Full CRUD API for Pages, Posts, Categories, Media
- [x] Media upload API with collection support
- [x] Laravel Sanctum token authentication with abilities
- [x] Filament Shield authorization integration
- [x] OpenAPI documentation via Scribe
- [x] Rate limiting (60/min standard, auth lockout after 5 failures)
- [x] Webhooks for content events (SSRF-protected, retry logic)

### API Features
- [x] Query parameters: filtering, sorting, pagination, includes
- [x] i18n support (single-locale and multi-locale modes)
- [x] Soft-delete operations (trash, restore, force-delete)
- [x] Publishing workflow (publish, unpublish, submit, approve, reject)
- [x] Revision history and restore

### Admin Panel
- [x] API Tokens management page
- [x] Webhook management with delivery logs

### Documentation
- [x] REST API Development guide
- [x] API Permissions Reference
- [x] Roles & Authorization guide

### Developer Tools (Remaining)
- [x] CLI tool for scaffolding themes (php artisan make:theme)
- [ ] CLI tool for scaffolding plugins
- [x] Plugin development documentation
- [x] Theme development guide
- [ ] Local development improvements
- [ ] Debug toolbar integration
- [ ] List on Filament plugin directory

### Block Animations (v2.8.5)
- [x] Scroll-triggered entrance animations for content blocks
- [x] Animation types: Fade In, Fade In Up (Core); + Down/Left/Right, Zoom In, Zoom In Up (Pro)
- [x] Configurable animation speed (Normal, Relaxed, Dramatic; + Snappy, Quick in Pro)
- [x] Stagger effect for multi-item blocks (Pro)
- [x] Accessibility: respects prefers-reduced-motion
- [x] Supported blocks: Features, Testimonials, Pricing, Stats, Team, FAQ, CTA, Logos

---

## v2.9 - Plugin Manager

**Status: ✅ Released (v2.9.0)**

Focus: Streamlined plugin management with update notifications and one-click updates.

### Update Management
- [x] Update availability display with version badges
- [x] "Check for Updates" button with cache refresh
- [x] Updates summary section showing all available updates
- [x] One-click updates with license validation
- [x] Automatic ZIP download from Anystack CDN

### Plugin Manager UI
- [x] Plugin search/filter by name, description, author, or tags
- [x] Combined "Install / Update Plugin" action (auto-detects mode)
- [x] License button linking directly to Plugin Licenses page
- [x] Pre-selected plugin on Plugin Licenses via query string

### Version Constraints
- [x] Support for OR operator (`||`) in version constraints
- [x] Composer-style version matching (`^1.0 || ^2.0`)

---

## v2.10 - Community & Users

Focus: Enable community interaction with your content.

### Comments System
- [ ] Native commenting on posts
- [ ] Nested replies
- [ ] Moderation queue
- [ ] Spam protection
- [ ] Email notifications
- [ ] Guest vs. authenticated comments

### User Features
- [ ] Frontend user registration
- [ ] User profiles
- [ ] Password reset flow
- [ ] Social login (Google, GitHub, etc.)
- [ ] Email verification

### Activity & Analytics
- [ ] User activity logging (audit trail)
- [ ] Content analytics dashboard
- [ ] Popular content tracking
- [ ] User engagement metrics

---

## v2.11 - Marketplace Integration

Focus: Connect to the official TallCMS marketplace for plugins and themes.

### Marketplace Client
- [ ] Browse marketplace from admin panel
- [ ] One-click plugin/theme installation
- [ ] License key activation
- [ ] Automatic update notifications
- [ ] Update changelog previews

### Developer Features
- [ ] Plugin/theme packaging standards
- [ ] Marketplace submission guidelines
- [ ] License validation improvements

---

## Future Considerations

Items we're exploring for future releases:

### TallCMS Pro Roadmap
Premium features planned for TallCMS Pro plugin:

**Current (v1.3.0):**
- 9 Advanced Blocks (Accordion, Tabs, Counter, Table, Comparison, Video, Before/After, Code Snippet, Map)
- Google Analytics 4 dashboard integration
- Email marketing settings (Mailchimp, ConvertKit, Sendinblue)

**Planned:**
- **Advanced Translation** — Side-by-side editor, machine translation (OpenAI, DeepL, Google), translation memory
- **AI Content** — AI writing assistant, auto meta descriptions, SEO suggestions, content outlines, image alt text generation

### May Become Core
- [ ] Custom post types
- [ ] Custom fields builder
- [ ] Advanced form builder

### Likely Plugins
- [ ] E-commerce (product catalog, cart, payments)
- [ ] Real estate (property listings, search filters, agent profiles)
- [ ] Email marketing integrations
- [ ] Social media auto-posting
- [ ] CRM integrations

### Exploring
- [ ] Static site generation
- [ ] Multi-tenant support
- [ ] Edge caching
- [ ] Database optimization tools

---

## What TallCMS Is NOT

To stay focused, we're explicitly **not** building:

- **E-commerce platform** — Use dedicated solutions like Shopify, or integrate via plugins
- **Email marketing tool** — Integrate with Mailchimp, ConvertKit, etc.
- **Social network** — We're a CMS, not a community platform
- **Real-time collaboration** — Google Docs-style editing is out of scope
- **Mobile app** — Web-first with responsive admin

Some of these may be achievable via plugins from the marketplace.

---

## How to Contribute

We welcome community input on our roadmap:

1. **Feature Requests**: Open an issue on [GitHub](https://github.com/tallcms/tallcms/issues) with the `enhancement` label
2. **Bug Reports**: Help us improve stability by reporting issues
3. **Pull Requests**: Contributions are welcome for any roadmap item
4. **Discussions**: Join the conversation about priorities and implementation

---

## Version History

| Version | Status | Theme | Highlights |
|---------|--------|-------|------------|
| v1.0 | ✅ Released | Foundation | Core CMS, Themes, Plugins, Permissions |
| v1.1 | ✅ Released | UI Polish | daisyUI 5, semantic CSS classes |
| v1.2 | ✅ Released | Content & SEO | Blog frontend, SEO tools, System Updates |
| v2.0 | ✅ Released | Platform | Filament plugin architecture, package split |
| v2.4 | ✅ Released | Global | Multilingual support, locale routing, translation workflow |
| v2.5 | ✅ Released | Navigation | Page settings, breadcrumbs |
| v2.6 | ✅ Released | Discovery | Full-text search for Pages and Posts |
| v2.7 | ✅ Released | Media | Image optimization, WebP variants, collections, video support |
| v2.8 | ✅ Released | API | REST API, Sanctum auth, Webhooks, OpenAPI docs |
| v2.8.5 | ✅ Released | Animations | Block entrance animations, stagger effects |
| v2.9 | ✅ Released | Plugins | Plugin Manager updates, search, one-click updates |
| v2.10 | 📋 Planned | Community | Comments, User profiles, Analytics |
| v2.11 | 📋 Planned | Ecosystem | Marketplace integration |
| Pro | 🔄 Ongoing | Premium | Advanced blocks, Analytics, AI Content, Translation |

---

## Community

- 🌐 **Website**: [tallcms.com](https://tallcms.com)
- 🐙 **GitHub**: [github.com/tallcms/tallcms](https://github.com/tallcms/tallcms)
- 🐦 **Twitter/X**: [@tallcms](@tallcms59858)

---

*Last updated: January 31, 2026 — v2.9.0 Plugin Manager improvements released*
