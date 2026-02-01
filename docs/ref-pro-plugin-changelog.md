---
title: "Pro Plugin Changelog"
slug: "pro-plugin-changelog"
audience: "developer"
category: "reference"
order: 50
---

# Pro Plugin Changelog

> **What you'll learn:** Track all changes, new features, and fixes in the TallCMS Pro Plugin across versions.

---

## Version 1.5.1

**Released:** 2026-02-01

- Fix radial/gauge progress stroke color not rendering (SVG doesn't support oklch CSS syntax)

---

## Version 1.5.0

**Released:** 2026-02-01

Differentiate Pro Counter from core Stats block with premium visual styles.

### Counter Block Display Styles

| Style | Description |
|-------|-------------|
| Classic | Standard animated numbers (backwards compatible) |
| Radial Progress | Full circle SVG with animated progress ring |
| Gauge Meter | Half-circle speedometer with tick marks |

### New Options

- Add `display_style` selection: classic, radial, gauge
- Add `radial_size`: sm (120px), md (160px), lg (200px)
- Add `stroke_width`: thin, normal, thick
- Add `default_max` for percentage calculations
- Add per-counter `max_value` override

---

## Version 1.4.2

**Released:** 2026-02-01

- Fix ComparisonBlock error with unresolvable `$index` in nested repeater

---

## Version 1.4.1

**Released:** 2026-02-01

- Fix CodeSnippet line highlighting not visible against dark mockup-code background

---

## Version 1.4.0

**Released:** 2026-02-01

Enhance all Pro blocks with premium customization options. All changes maintain backwards compatibility.

### Accordion Block

- Add icon styles: arrow (default), plus/minus, chevron, numbered, or none
- Add icon position: left or right placement
- Add accent color selection for icons and badges
- Add per-item heroicons from curated list
- Fix duplicate array keys in `toHtml()`

### Counter Block

- Add per-counter icons (heroicon or emoji)
- Add number formatting: plain (1000), thousands (1,000), abbreviated (1K/1M)

### Tabs Block

- Add icon position: left of text, above text, or icon-only
- Add active indicator styles: default, underline, or filled

### Comparison Block

- Support 2-5 dynamic columns (previously hardcoded to 2)
- Add highlight toggle to mark columns as "recommended"
- Auto-migrate old 2-column configs to new schema

### Code Snippet Block

- Add theme selection: dark (default) or light
- Add line highlighting with `1,3-5,10` syntax

### Infrastructure

- Create `icon.blade.php` component for consistent rendering
- Create `HasIconSelection` trait with 30 curated heroicons
- Add helper functions: `tallcms_pro_parse_highlight_lines()`, `tallcms_pro_abbreviate_number()`

---

## Version 1.3.0

**Released:** 2026-01-31

Add scroll-triggered animations with stagger support to all Pro blocks.

### Features

- Add animation types: fade, slide, zoom, and flip
- Add stagger support with configurable delays
- Add `HasContentWidth` trait for page-level width control
- Standardize all blocks to `max-w-6xl` width

### Blocks Updated

All 9 Pro blocks now support animations:
- Accordion, Tabs, Counter, Table, Comparison
- Video, Before/After, Code Snippet, Map

---

## Version 1.2.2

**Released:** 2026-01-25

- Add anchor ID support to all blocks for deep linking
- Allow HTML content in Table block cells

---

## Version 1.2.1

**Released:** 2026-01-24

### Fixes

- Fix block form reactivity issues
- Fix `ProSetting` to handle missing migrations gracefully
- Improve security for block rendering

### Developer Experience

- Add `CLAUDE.md` with release checklist
- Update TallCMS compatibility requirements

---

## Version 1.2.0

**Released:** 2026-01-14

### Google Analytics Integration

- Add simple gtag tracking via Measurement ID
- Add Dashboard Analytics widget with setup guide
- Add middleware for automatic gtag injection

### Security

- Remove test license bypass
- Enhance license validation

---

## Version 1.1.0

**Released:** 2026-01-12

Refactor all Pro blocks to use daisyUI semantic classes.

### Block Updates

| Block | daisyUI Component |
|-------|-------------------|
| Accordion | `collapse` |
| Tabs | `tabs` |
| Counter | `card` |
| Table | `table` |
| Comparison | `table` with semantic colors |
| Before/After | `diff` |
| Code Snippet | `mockup-code` |

### Enhancements

- Add daisyUI color options (primary, secondary, accent, etc.)
- Add size options (xs, sm, md, lg)
- Fix Comparison block grid layout

---

## Version 1.0.5

**Released:** 2026-01-10

- Migrate license management to core `PluginLicenseService`
- Remove duplicate license logic from plugin

---

## Version 1.0.4

**Released:** 2026-01-10

- Fix watermark to only show for never-licensed users
- Previously licensed users no longer see watermarks after expiration

---

## Version 1.0.3

**Released:** 2026-01-10

- Refactor license management to use core TallCMS services
- Improve license validation flow

---

## Version 1.0.2

**Released:** 2026-01-09

- Add Anystack product ID for license management
- Configure license proxy integration

---

## Version 1.0.1

**Released:** 2026-01-09

- Minor fixes after initial release

---

## Version 1.0.0

**Released:** 2026-01-09

Initial public release of TallCMS Pro Plugin.

### Pro Blocks

| Block | Description |
|-------|-------------|
| Accordion | Collapsible FAQ-style sections |
| Tabs | Tabbed content panels |
| Counter | Animated number counters |
| Table | Data tables with sorting |
| Comparison | Side-by-side feature comparison |
| Video | YouTube/Vimeo embeds with lazy loading |
| Before/After | Image comparison slider |
| Code Snippet | Syntax-highlighted code blocks |
| Map | OpenStreetMap/Google Maps integration |

### Features

- License management with Anystack integration
- Pro Settings page in admin panel
- License status widget
- Watermark overlay for unlicensed usage

### Requirements

- PHP ^8.2
- TallCMS >=2.0

---

## Next Steps

- [Pro Blocks Overview](site-blocks)
- [Plugin Development](dev-plugins)
- [Block Animations](site-blocks-animations)
