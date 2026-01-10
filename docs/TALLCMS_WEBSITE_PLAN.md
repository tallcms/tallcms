# TallCMS.com Website Plan

## Overview

Build the official TallCMS website using TallCMS itself to showcase its capabilities. The website serves as both marketing material and a live demonstration of the CMS features.

## Goals

1. **Showcase TallCMS features** - Live examples of blocks, themes, and functionality
2. **Provide documentation** - Help users get started and understand the system
3. **Drive adoption** - Clear value proposition and easy download/installation
4. **Demonstrate Pro features** - Show premium blocks and analytics capabilities

---

## Site Structure

### Primary Pages

| Page | Slug | Purpose |
|------|------|---------|
| Home | `/` | Hero, feature highlights, call-to-action |
| Features | `/features` | Detailed feature breakdown with live block examples |
| Pro | `/pro` | TallCMS Pro plugin features and pricing |
| Documentation | `/docs` | Getting started, installation, configuration |
| Blog | `/blog` | News, tutorials, release notes (uses Posts) |
| About | `/about` | Project story, team, mission |
| Contact | `/contact` | Contact form, support info |

### Documentation Sub-pages

| Page | Slug | Content |
|------|------|---------|
| Getting Started | `/docs/getting-started` | Installation, requirements, first steps |
| Themes | `/docs/themes` | Theme system, customization, creating themes |
| Blocks | `/docs/blocks` | Available blocks, usage, customization |
| Plugins | `/docs/plugins` | Plugin system, development, security |
| API Reference | `/docs/api` | Helper functions, services, interfaces |

---

## Page Content Details

### 1. Home Page (`/`)

**Blocks to use:**
- **HeroBlock** - Main headline with background image
- **ContentBlock** - Brief intro to TallCMS
- **CallToActionBlock** - Download/Get Started CTA
- **PricingBlock** (optional) - Quick Pro comparison

**Content outline:**
```
Hero:
- Headline: "The Modern CMS for Laravel Developers"
- Subheadline: "Build beautiful content-driven sites with the TALL stack"
- CTA: "Get Started Free" | "View Demo"

Features Grid (3-4 items):
- Block-based Editor
- Multi-theme System
- Plugin Architecture
- Pro Analytics

Social Proof:
- Built on Laravel 12 & Filament v4
- Open source core
- Active development

Final CTA:
- Download TallCMS Core (free)
- Get TallCMS Pro
```

### 2. Features Page (`/features`)

**Blocks to use:**
- **HeroBlock** - Features header
- **ContentBlock** - Feature sections with screenshots
- **ImageGalleryBlock** - Admin panel screenshots
- **CallToActionBlock** - Try it now

**Content sections:**
```
1. Block-Based Editor
   - Rich content blocks
   - Live preview in admin
   - Drag-and-drop ordering
   - Screenshot of block editor

2. Multi-Theme System
   - File-based themes
   - One-click activation
   - Theme preview
   - Template overrides
   - Screenshot of theme manager

3. Menu Management
   - Multiple menu locations
   - Nested items
   - Active state detection
   - Screenshot of menu builder

4. Plugin System
   - Secure plugin architecture
   - Easy installation
   - Auto-discovery
   - Screenshot of plugin manager

5. SEO & Meta
   - SEO fields on pages/posts
   - Open Graph support
   - Merge tags system
```

### 3. Pro Page (`/pro`)

**Blocks to use:**
- **HeroBlock** - Pro features header
- **ContentBlock** - Feature descriptions
- **PricingBlock** - Pricing tiers
- **CallToActionBlock** - Purchase CTA

**Content:**
```
Hero:
- "Unlock Advanced Features"
- "Analytics, premium blocks, and priority support"

Pro Features:
1. Analytics Dashboard
   - Visitor tracking
   - Traffic sources
   - Page performance
   - Trend visualization

2. Premium Blocks
   - Additional block types
   - Advanced customization

3. Priority Support
   - Direct support channel
   - Faster response times

4. Future Updates
   - All new Pro features included

Pricing:
- Single site license
- Unlimited sites option
- Comparison table (Core vs Pro)

FAQ:
- What happens if license expires?
- Can I use on staging sites?
- Refund policy
```

### 4. Documentation Landing (`/docs`)

**Blocks to use:**
- **ContentBlock** - Documentation overview with links

**Content:**
```
Quick Links:
- Getting Started Guide
- Installation Requirements
- Theme Development
- Block Reference
- Plugin Development
- API Documentation

Resources:
- GitHub Repository
- Issue Tracker
- Community Discord (future)
```

### 5. Getting Started (`/docs/getting-started`)

**Content:**
```
1. Requirements
   - PHP 8.2+
   - Node.js 18+
   - Composer
   - Database (SQLite, MySQL, PostgreSQL)

2. Installation
   - Via Composer create-project
   - Via Git clone
   - Web installer walkthrough

3. First Steps
   - Access admin panel
   - Create first page
   - Set up menus
   - Configure settings

4. Next Steps
   - Install a theme
   - Explore blocks
   - Consider Pro features
```

### 6. About Page (`/about`)

**Blocks to use:**
- **HeroBlock** - About header
- **ContentBlock** - Story and mission

**Content:**
```
The Story:
- Why TallCMS was built
- TALL stack philosophy
- Open source commitment

Mission:
- Empower Laravel developers
- Modern content management
- Developer experience first

Technology:
- Laravel 12
- Filament v4
- Tailwind CSS 4
- Livewire 3
- Alpine.js
```

### 7. Contact Page (`/contact`)

**Blocks to use:**
- **ContentBlock** - Contact info
- **ContactBlock** (if available) - Contact form

**Content:**
```
Get in Touch:
- Support: support@tallcms.com
- General: hello@tallcms.com

GitHub:
- Link to repository
- Link to issues

Social:
- Twitter/X
- GitHub
```

### 8. Blog (Posts)

**Initial posts:**
```
1. "Introducing TallCMS v1.0.0"
   - Release announcement
   - Key features
   - Getting started link

2. "Building with Blocks"
   - Tutorial on using blocks
   - Best practices

3. "Theme Development Guide"
   - Creating custom themes
   - Template overrides
```

---

## Menu Structure

### Header Menu
```
- Home
- Features
- Pro
- Docs (dropdown)
  - Getting Started
  - Themes
  - Blocks
  - Plugins
- Blog
- Contact
```

### Footer Menu
```
- About
- Contact
- GitHub
- Privacy Policy
- Terms of Service
```

---

## Theme Considerations

For the official site, we should either:
1. **Use the default theme** - Shows it works out of box
2. **Create a "TallCMS Official" theme** - Custom branding, polished look

Recommendation: Start with default theme, then iterate based on needs.

---

## Page-by-Page Content Guide

### HOME PAGE (`/`)

**Block 1: HeroBlock**
```
Heading: The Modern CMS for Laravel Developers
Subheading: Build beautiful, content-driven websites with the power of the TALL stack. Block-based editing, multi-theme support, and a secure plugin architecture.
Primary Button: Get Started Free → /docs/getting-started
Secondary Button: View on GitHub → https://github.com/tallcms/tallcms
Background: Use a gradient or abstract tech image
```

**Block 2: ContentBlock**
```
Heading: Why TallCMS?
Content:
TallCMS combines the best of modern Laravel development with an intuitive content management experience. Built on Laravel 12, Filament v4, Livewire 3, and Tailwind CSS 4, it gives developers full control while providing editors with a beautiful interface.

- **Block-Based Editing** - Compose pages with reusable content blocks
- **Multi-Theme System** - Switch themes instantly, customize with ease
- **Plugin Architecture** - Extend functionality securely
- **SEO Ready** - Built-in meta tags, Open Graph, and merge tags
```

**Block 3: CallToActionBlock**
```
Heading: Ready to Build Something Great?
Description: Download TallCMS for free and start building your next project today.
Primary Button: Download TallCMS → https://github.com/tallcms/tallcms/releases
Secondary Button: Get TallCMS Pro → /pro
Style: Centered, prominent background color
```

---

### FEATURES PAGE (`/features`)

**Block 1: HeroBlock**
```
Heading: Powerful Features, Developer Friendly
Subheading: Everything you need to build modern content-driven websites
Style: Shorter hero, no buttons needed
```

**Block 2: ContentBlock - Block Editor**
```
Heading: Block-Based Content Editor
Content:
Create rich, structured content using our intuitive block editor. Each block type is purpose-built for specific content needs:

- **Hero Blocks** - Eye-catching headers with background images and CTAs
- **Content Blocks** - Rich text with multiple width and heading options
- **Pricing Tables** - Display plans, features, and pricing clearly
- **Image Galleries** - Professional galleries with lightbox support
- **Call-to-Action** - Convert visitors with prominent CTAs

Every block includes live preview in the admin panel, so you see exactly what visitors will see.

[Screenshot: Block editor in Filament admin]
```

**Block 3: ContentBlock - Themes**
```
Heading: Multi-Theme System
Content:
Switch your site's look instantly with file-based themes. Each theme can override any template, include custom assets, and define its own color palette.

- One-click theme activation
- Live preview before committing
- Template override system
- Tailwind CSS integration
- Build tools included (Vite + npm)

Create your own themes or customize existing ones to match your brand.

[Screenshot: Theme manager page]
```

**Block 4: ContentBlock - Plugins**
```
Heading: Secure Plugin Architecture
Content:
Extend TallCMS with plugins while maintaining security. Our plugin system includes:

- **Sandboxed Execution** - Plugins run in controlled environments
- **Route Protection** - No unauthorized route registration
- **Namespace Isolation** - Plugins can't override core code
- **Easy Installation** - Upload ZIP or install from marketplace
- **Auto-Discovery** - Blocks, routes, and views register automatically

Build custom functionality without compromising your site's security.

[Screenshot: Plugin manager page]
```

**Block 5: ContentBlock - SEO**
```
Heading: SEO & Meta Tags
Content:
Every page and post includes built-in SEO fields:

- Meta title and description
- Open Graph tags for social sharing
- Canonical URLs
- Merge tags for dynamic content ({{site_name}}, {{current_year}}, etc.)

Your content is ready for search engines and social media out of the box.
```

**Block 6: CallToActionBlock**
```
Heading: See It In Action
Description: This entire website is built with TallCMS. Explore the features live.
Primary Button: Get Started → /docs/getting-started
```

---

### PRO PAGE (`/pro`)

**Block 1: HeroBlock**
```
Heading: TallCMS Pro
Subheading: Advanced features for professional websites. Analytics, premium blocks, and priority support.
Primary Button: Purchase Pro → https://tallcms.lemonsqueezy.com (or Anystack link)
Secondary Button: View Features → #features
```

**Block 2: ContentBlock - Analytics**
```
Heading: Built-in Analytics Dashboard
Content:
Understand your visitors without third-party tracking scripts:

- **Visitor Metrics** - Page views, unique visitors, sessions
- **Traffic Sources** - See where your visitors come from
- **Top Pages** - Know which content performs best
- **Trend Visualization** - Track growth over time
- **Privacy Focused** - No cookies, GDPR friendly

All data stays on your server. No external services required.

[Screenshot: Analytics dashboard widget]
```

**Block 3: ContentBlock - Premium Blocks**
```
Heading: Premium Content Blocks
Content:
Pro includes additional block types for advanced layouts:

- Advanced gallery layouts
- Testimonial carousels
- Team member grids
- FAQ accordions
- And more coming with each update

Your license includes all future Pro blocks at no extra cost.
```

**Block 4: PricingBlock**
```
Plans:
1. Single Site - $49/year
   - One production domain
   - All Pro features
   - One year of updates
   - Email support

2. Unlimited Sites - $149/year
   - Unlimited domains
   - All Pro features
   - One year of updates
   - Priority support

Features to highlight:
- Analytics dashboard
- Premium blocks
- Priority support
- Future updates included
```

**Block 5: ContentBlock - FAQ**
```
Heading: Frequently Asked Questions

**What happens when my license expires?**
Your site keeps working! You just won't receive new updates or support until you renew.

**Can I use Pro on staging/development sites?**
Yes! Your license covers unlimited staging and local development environments.

**Is there a refund policy?**
Yes, we offer a 14-day money-back guarantee if Pro doesn't meet your needs.

**Do I need to renew annually?**
Renewal is optional. Your Pro features continue working, but you'll need an active license for updates.
```

**Block 6: CallToActionBlock**
```
Heading: Upgrade to Pro Today
Description: Get analytics, premium blocks, and priority support for your TallCMS site.
Primary Button: Purchase Pro → (purchase link)
```

---

### DOCS LANDING PAGE (`/docs`)

**Block 1: ContentBlock**
```
Heading: Documentation
Content:
Welcome to the TallCMS documentation. Whether you're just getting started or building custom themes and plugins, you'll find what you need here.

## Getting Started
New to TallCMS? Start here:
- [Installation Guide](/docs/getting-started) - Get up and running in minutes
- [Your First Page](/docs/getting-started#first-page) - Create your first content

## Core Concepts
- [Blocks](/docs/blocks) - Understanding the block-based editor
- [Themes](/docs/themes) - Customizing your site's appearance
- [Plugins](/docs/plugins) - Extending functionality

## Developer Resources
- [API Reference](/docs/api) - Helper functions and services
- [GitHub Repository](https://github.com/tallcms/tallcms) - Source code and issues

## Need Help?
- [Contact Us](/contact) - Get in touch
- [GitHub Issues](https://github.com/tallcms/tallcms/issues) - Report bugs or request features
```

---

### GETTING STARTED PAGE (`/docs/getting-started`)

**Block 1: ContentBlock**
```
Heading: Getting Started with TallCMS

## Requirements

Before installing TallCMS, ensure your server meets these requirements:

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- Database: SQLite, MySQL 8.0+, or PostgreSQL 13+

## Installation

### Option 1: Composer (Recommended)

```bash
composer create-project tallcms/tallcms my-site
cd my-site
composer setup
```

### Option 2: Git Clone

```bash
git clone https://github.com/tallcms/tallcms.git my-site
cd my-site
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

## Web Installer

After installation, visit your site in a browser. If no `.env` file exists, you'll see the web installer which guides you through:

1. System requirements check
2. Database configuration
3. Admin account creation
4. Mail settings (optional)
5. Cloud storage (optional)

## First Steps

Once installed:

1. **Access Admin Panel** - Visit `/admin` and log in
2. **Configure Site Settings** - Set your site name, logo, and contact info
3. **Create Your First Page** - Go to Pages and click "New Page"
4. **Set Up Menus** - Configure header and footer navigation
5. **Choose a Theme** - Browse and activate themes in Appearance

## Next Steps

- Explore the [Blocks](/docs/blocks) available for content
- Learn about [Themes](/docs/themes) and customization
- Consider [TallCMS Pro](/pro) for analytics and premium features
```

---

### ABOUT PAGE (`/about`)

**Block 1: HeroBlock**
```
Heading: About TallCMS
Subheading: A modern CMS built by developers, for developers
Style: Simple, no buttons
```

**Block 2: ContentBlock**
```
Heading: The Story

TallCMS was born from a simple frustration: why isn't there a great CMS built on modern Laravel?

WordPress powers millions of sites, but it's PHP from another era. Headless CMS solutions are powerful but require separate frontends. Existing Laravel CMS packages often feel like afterthoughts.

We wanted something different:
- **Modern Stack** - Laravel 12, Livewire 3, Tailwind CSS 4
- **Beautiful Admin** - Powered by Filament v4
- **Developer Control** - Full access to code, no magic
- **Editor Friendly** - Intuitive block-based editing

So we built TallCMS.

## Open Source First

The core of TallCMS is completely free and open source. We believe great tools should be accessible to everyone.

TallCMS Pro exists to fund ongoing development while providing advanced features for professional use cases.

## The Technology

TallCMS is built on the TALL stack:
- **T**ailwind CSS - Utility-first CSS framework
- **A**lpine.js - Lightweight JavaScript framework
- **L**aravel - The PHP framework for web artisans
- **L**ivewire - Full-stack framework for Laravel

Plus Filament v4 for the admin panel, bringing a world-class administration experience.

## Get Involved

TallCMS is better with your help:
- [Star us on GitHub](https://github.com/tallcms/tallcms)
- [Report issues](https://github.com/tallcms/tallcms/issues)
- [Contribute code](https://github.com/tallcms/tallcms/pulls)
- Share TallCMS with other developers
```

---

### CONTACT PAGE (`/contact`)

**Block 1: ContentBlock**
```
Heading: Contact Us

## Get in Touch

**General Inquiries**
hello@tallcms.com

**Support (Pro customers)**
support@tallcms.com

## Community

- [GitHub Discussions](https://github.com/tallcms/tallcms/discussions) - Ask questions, share ideas
- [GitHub Issues](https://github.com/tallcms/tallcms/issues) - Report bugs, request features

## Follow Us

- [GitHub](https://github.com/tallcms)
- [Twitter/X](https://twitter.com/tallcms)

---

We typically respond within 1-2 business days. Pro customers receive priority support.
```

---

### BLOG POSTS

**Post 1: "Introducing TallCMS v1.0.0"**
```
Category: Announcements

We're excited to announce the release of TallCMS v1.0.0!

After months of development, TallCMS is ready for production use. This release includes:

- Block-based content editor with 5 core block types
- Multi-theme system with live preview
- Secure plugin architecture
- Full SEO support
- Web installer for easy setup

## What's Included

**Core Blocks:**
- Hero Block - Eye-catching headers
- Content Block - Rich text content
- Pricing Block - Display pricing tables
- Image Gallery - Photo galleries with lightbox
- Call-to-Action - Conversion-focused sections

**Admin Features:**
- Filament v4 powered admin panel
- Page and Post management
- Menu builder
- Theme manager
- Plugin manager

## Getting Started

Ready to try TallCMS? Check out our [Getting Started Guide](/docs/getting-started).

## What's Next

We're already working on TallCMS Pro with analytics, premium blocks, and more. Stay tuned!
```

**Post 2: "Building with Blocks"**
```
Category: Tutorials

Learn how to create beautiful pages using TallCMS blocks...

[Tutorial content about using the block editor effectively]
```

---

## Assets Needed

- [ ] TallCMS logo (SVG preferred)
- [ ] Favicon
- [ ] Hero background image (abstract/gradient works well)
- [ ] Screenshots for Features page:
  - Block editor in action
  - Theme manager
  - Plugin manager
  - Analytics dashboard (Pro)

---

## Build Order

1. **Home** - First impression, core messaging
2. **About** - Establishes credibility
3. **Features** - Detailed feature showcase
4. **Pro** - Monetization page
5. **Docs landing** - Documentation hub
6. **Getting Started** - Critical for adoption
7. **Contact** - Simple, quick to build
8. **Blog posts** - Ongoing content

---

## Menu Configuration

**Header Menu (location: header)**
| Label | URL | Type |
|-------|-----|------|
| Home | / | Page |
| Features | /features | Page |
| Pro | /pro | Page |
| Docs | /docs | Page |
| Blog | /blog | Link |
| Contact | /contact | Page |

**Footer Menu (location: footer)**
| Label | URL | Type |
|-------|-----|------|
| About | /about | Page |
| Contact | /contact | Page |
| GitHub | https://github.com/tallcms/tallcms | Link |
| Privacy | /privacy | Page (create later) |
| Terms | /terms | Page (create later) |

---

## Notes

- Use this site as a testbed - note any UX issues or missing features
- Screenshots can be taken from your local dev environment
- Start simple, iterate based on what you discover
- The content above is ready to copy/paste into blocks
