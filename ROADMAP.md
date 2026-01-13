# TallCMS Roadmap

This roadmap outlines our vision for TallCMS development. We're committed to building the best TALL stack CMS and being transparent about our progress.

> **Note:** This roadmap reflects our current plans and priorities. Items may be added, removed, or reordered based on community feedback and technical considerations.

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

### Content Blocks (14 Built-in)
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
- [x] Web installer (WordPress-style setup)
- [x] Cloud storage support (S3, DigitalOcean, Cloudflare R2, etc.)
- [x] Multiple mail providers (SMTP, SES, Sendmail)
- [x] Maintenance mode
- [x] SQLite, MySQL, MariaDB support

---

## v1.1 - UI Polish (Current Release)

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

Focus: Complete the blogging experience and give content the SEO tools it deserves.

### Blog Frontend
- [x] Public blog listing page (via Posts block)
- [x] Individual post pages with SEO-friendly URLs
- [ ] Category archive pages
- [ ] Author archive pages
- [ ] Pagination
- [ ] RSS feed

### SEO Enhancements
- [ ] XML sitemap generation
- [ ] robots.txt editor
- [ ] Open Graph meta tags
- [ ] Twitter Card meta tags
- [ ] Structured data (JSON-LD) for articles

### Content Improvements
- [ ] Full-text search across pages and posts
- [ ] Content scheduling calendar view
- [ ] Bulk actions (publish, unpublish, delete)
- [ ] Content templates (save block layouts for reuse)
- [ ] Import/Export content (JSON/Markdown)

### Media Library
- [ ] Image optimization (automatic resizing)
- [ ] Lazy loading
- [ ] Alt text management
- [ ] Bulk upload improvements

---

## v1.3 - Multilingual Support

Focus: Make TallCMS accessible to global audiences.

### Core Translation
- [ ] Multi-language content fields
- [ ] Language switcher in admin
- [ ] Translated URL slugs (/en/about, /es/acerca-de)
- [ ] hreflang tag generation
- [ ] Default language fallback

### Translation Workflow
- [ ] Side-by-side translation editor
- [ ] Translation status indicators
- [ ] RTL (right-to-left) layout support
- [ ] Admin interface translations
- [ ] Machine translation hooks (optional)

---

## v1.4 - Developer Experience

Focus: Make TallCMS the best platform for developers to build on.

### REST API
- [ ] Full CRUD API for pages, posts, categories
- [ ] Media upload API
- [ ] Authentication (API tokens, OAuth)
- [ ] API documentation (OpenAPI/Swagger)
- [ ] Rate limiting
- [ ] Webhooks for content events

### Developer Tools
- [x] CLI tool for scaffolding themes (php artisan make:theme)
- [ ] CLI tool for scaffolding plugins
- [x] Plugin development documentation
- [x] Theme development guide
- [ ] Local development improvements
- [ ] Debug toolbar integration

---

## v1.5 - AI-Powered Content Creation

Focus: Leverage AI to help creators produce better content faster.

### AI Writing Assistant
- [ ] AI content generation in editor
- [ ] Tone and style adjustments
- [ ] Content summarization
- [ ] Headline/title suggestions
- [ ] SEO optimization suggestions
- [ ] Grammar and spelling checks

### AI Workflow Automation
- [ ] Auto-generate meta descriptions
- [ ] Auto-suggest categories and tags
- [ ] Image alt text generation
- [ ] Content outline generator
- [ ] Related content suggestions

### AI Configuration
- [ ] Bring your own API key (OpenAI, Anthropic, etc.)
- [ ] Custom AI prompts
- [ ] Usage limits and monitoring
- [ ] Privacy controls (opt-in/opt-out)

---

## v1.6 - Community & Users

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

## v1.7 - Marketplace Integration

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
| v1.0 | Released | Foundation | Core CMS, Themes, Plugins, Permissions |
| v1.1 | Current | UI Polish | daisyUI 5, semantic CSS classes |
| v1.2 | Next | Content & SEO | Blog frontend, RSS, Search, Sitemap |
| v1.3 | Planned | Global | Multilingual support, RTL |
| v1.4 | Planned | Developers | REST API, CLI tools, Webhooks |
| v1.5 | Planned | AI | Content generation, Auto-optimization |
| v1.6 | Planned | Community | Comments, User profiles, Analytics |
| v1.7 | Planned | Ecosystem | Marketplace integration |

---

*Last updated: January 2026*
