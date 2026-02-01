# TallCMS Pro Plugin Changelog

All notable changes to the TallCMS Pro Plugin are documented in this file.

---

## [1.4.0] - 2026-02-01

### Enhanced Pro Blocks with Premium Customization

Enhanced all Pro blocks with premium customization options while maintaining full backwards compatibility.

#### AccordionBlock
- **Icon Styles**: Choose from arrow (default), plus/minus, chevron, numbered, or no icon
- **Icon Position**: Place collapse icons on left or right
- **Accent Colors**: Apply theme colors to icons and badges
- **Per-Item Icons**: Add heroicons to individual accordion items
- **Bug Fix**: Removed duplicate array keys in `toHtml()`

#### CounterBlock
- **Per-Counter Icons**: Add heroicons or emojis to each counter
- **Number Formatting**: Plain (1000), thousands (1,000), or abbreviated (1K/1M)

#### TabsBlock
- **Icon Position**: Left of text, above text, or icon-only mode
- **Active Indicator**: Default theme, underline, or filled background styles

#### ComparisonBlock
- **Dynamic Columns**: Support 2-5 columns (previously hardcoded to 2)
- **Highlight Toggle**: Mark any column as "recommended" with visual badge
- **Backwards Compatible**: Old 2-column configs automatically migrate

#### CodeSnippetBlock
- **Themes**: Dark (default) or light background
- **Line Highlighting**: Highlight specific lines with `1,3-5,10` syntax

#### Infrastructure
- New `icon.blade.php` component for consistent icon rendering
- New `HasIconSelection` trait with 30 curated heroicons
- Helper functions: `tallcms_pro_parse_highlight_lines()`, `tallcms_pro_abbreviate_number()`

---

## [1.3.0] - 2026-01-31

### Animation Support

Added scroll-triggered animations with stagger support to all Pro blocks.

#### Features
- **Animation Types**: Fade, slide, zoom, and flip animations
- **Stagger Support**: Items animate sequentially with configurable delays
- **Content Width**: Added `HasContentWidth` trait for page-level width control
- **Standardized Widths**: All blocks now use consistent `max-w-6xl` width

#### Blocks Updated
- AccordionBlock
- TabsBlock
- CounterBlock
- TableBlock
- ComparisonBlock
- VideoBlock
- BeforeAfterBlock
- CodeSnippetBlock
- MapBlock

---

## [1.2.2] - 2026-01-25

### Anchor IDs & Table Enhancements

- **Anchor ID Support**: All blocks now support custom anchor IDs for deep linking
- **Table Block**: Allow HTML content in table cells for richer formatting

---

## [1.2.1] - 2026-01-24

### Block Fixes & Flexible Versioning

#### Fixes
- Fixed block form reactivity issues
- Fixed `ProSetting` to handle missing migrations gracefully
- Security improvements for block rendering

#### Developer Experience
- Added `CLAUDE.md` with release checklist and development guide
- Updated TallCMS compatibility requirements

---

## [1.2.0] - 2026-01-14

### Google Analytics & Security

#### Features
- **Google Analytics Integration**: Simple gtag tracking via Measurement ID
- **Dashboard Analytics Widget**: Step-by-step setup guide included
- **Analytics Middleware**: Automatic gtag injection on frontend pages

#### Security
- Removed test license bypass
- Enhanced license validation

#### Housekeeping
- Added `.DS_Store` to gitignore

---

## [1.1.0] - 2026-01-12

### daisyUI Integration

Major refactor of all Pro blocks to use daisyUI semantic classes for better theming and consistency.

#### Block Refactors
- **AccordionBlock**: Uses daisyUI collapse component
- **TabsBlock**: Uses daisyUI tabs component
- **CounterBlock**: Styled with daisyUI cards
- **TableBlock**: Uses daisyUI table component
- **ComparisonBlock**: Refactored to daisyUI table with semantic colors
- **BeforeAfterBlock**: Uses daisyUI diff component
- **CodeSnippetBlock**: Uses daisyUI mockup-code component

#### Enhancements
- Added daisyUI color options (primary, secondary, accent, etc.)
- Added size options (xs, sm, md, lg)
- Fixed comparison block grid layout with cards style

---

## [1.0.5] - 2026-01-10

### Core License Migration

- Migrated license management to core TallCMS `PluginLicenseService`
- Simplified plugin architecture by removing duplicate license logic

---

## [1.0.4] - 2026-01-10

### Watermark Fix

- Fixed watermark to only show for users who have never been licensed
- Previously licensed users no longer see watermarks after expiration

---

## [1.0.3] - 2026-01-10

### License Refactor

- Refactored license management to use core TallCMS services
- Improved license validation flow

---

## [1.0.2] - 2026-01-09

### Anystack Integration

- Added Anystack product ID for license management
- Configured license proxy integration

---

## [1.0.1] - 2026-01-09

### Patch Release

- Minor fixes and improvements after initial release

---

## [1.0.0] - 2026-01-09

### Initial Release

First public release of TallCMS Pro Plugin.

#### Pro Blocks (9 total)
- **AccordionBlock**: Collapsible FAQ-style sections
- **TabsBlock**: Tabbed content panels
- **CounterBlock**: Animated number counters
- **TableBlock**: Data tables with sorting
- **ComparisonBlock**: Side-by-side feature comparison
- **VideoBlock**: YouTube/Vimeo embeds with lazy loading
- **BeforeAfterBlock**: Image comparison slider
- **CodeSnippetBlock**: Syntax-highlighted code blocks
- **MapBlock**: OpenStreetMap/Google Maps integration

#### Features
- License management with Anystack integration
- Pro Settings page in Filament admin
- License status widget
- Watermark overlay for unlicensed usage

#### Requirements
- PHP ^8.2
- TallCMS >=2.0
