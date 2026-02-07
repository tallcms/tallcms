# TallCMS Pro Plugin — Premium Menu System

## Claude Code Implementation Plan

> Save this file as `PRO_MENU_PLAN.md` in your TallCMS Pro plugin repo root.
> Usage: `"Read PRO_MENU_PLAN.md and implement Phase 1"`

---

## Architecture Overview

The Pro Menu system extends the existing TallCMS core menu management (menus, menu items, locations, nesting, SPA mode, translations) with premium visual design controls, layout options, and advanced menu item types.

### Design Principle: Extend, Don't Replace

The core `Menu` and `MenuItem` models remain untouched. Pro adds:

1. **A `MenuSettings` model** — stores header layout, logo position, sticky behavior, and design tokens per menu
2. **A `MenuItemPro` extension** — additional columns for mega menu config, badges, descriptions, images, visibility rules
3. **Pro Blade components** — enhanced rendering components that wrap core menu output
4. **Filament form extensions** — additional fields injected into existing Menu/MenuItem resources

### File Structure

```
tallcms-pro/
├── src/
│   ├── ProMenuServiceProvider.php
│   ├── Models/
│   │   ├── MenuSettings.php
│   │   └── MenuItemPro.php          # 1:1 extension of core MenuItem
│   ├── Filament/
│   │   └── Extensions/
│   │       ├── MenuFormExtension.php       # Extends core Menu form
│   │       └── MenuItemFormExtension.php   # Extends core MenuItem form
│   ├── Services/
│   │   ├── MegaMenuRenderer.php
│   │   ├── HeaderLayoutManager.php
│   │   └── MenuVisibilityResolver.php
│   ├── Enums/
│   │   ├── LogoPosition.php
│   │   ├── StickyBehavior.php
│   │   ├── MobileMenuStyle.php
│   │   ├── DropdownAnimation.php
│   │   └── DropdownTrigger.php
│   └── View/
│       └── Components/
│           ├── ProHeader.php
│           ├── MegaMenu.php
│           ├── MobileDrawer.php
│           ├── TopBar.php
│           └── StickyWrapper.php
├── resources/
│   └── views/
│       └── components/
│           ├── pro-header.blade.php
│           ├── mega-menu.blade.php
│           ├── mega-menu-column.blade.php
│           ├── mobile-drawer.blade.php
│           ├── mobile-overlay.blade.php
│           ├── top-bar.blade.php
│           ├── sticky-wrapper.blade.php
│           └── templates/
│               ├── centered-logo.blade.php
│               ├── split-menu.blade.php
│               ├── sidebar-nav.blade.php
│               ├── fullscreen-overlay.blade.php
│               └── hamburger-only.blade.php
├── database/
│   └── migrations/
│       ├── create_menu_settings_table.php
│       └── create_menu_item_pro_table.php
└── config/
    └── pro-menu.php
```

---

## Database Schema

### `menu_settings` table

Stores per-menu design configuration. One-to-one with core `menus` table.

```php
Schema::create('menu_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
    $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();

    // Header Layout
    $table->string('template')->default('default');         // default, centered-logo, split-menu, sidebar-nav, fullscreen-overlay, hamburger-only
    $table->string('logo_position')->default('left');       // left, center, right, hidden
    $table->integer('logo_max_height')->default(40);        // px

    // Sticky Behavior
    $table->string('sticky_behavior')->default('none');     // none, always, smart (hide-on-down/show-on-up), shrink
    $table->integer('sticky_offset')->default(0);           // px scroll offset before sticky kicks in
    $table->string('sticky_bg_color')->nullable();          // override bg when sticky
    $table->integer('sticky_logo_max_height')->nullable();  // shrink logo height when sticky

    // Transparent Header
    $table->boolean('transparent_enabled')->default(false);
    $table->string('transparent_text_color')->default('#ffffff');
    $table->string('transparent_bg_color')->nullable();     // null = fully transparent

    // Top Bar
    $table->boolean('topbar_enabled')->default(false);
    $table->string('topbar_bg_color')->default('#1a202c');
    $table->string('topbar_text_color')->default('#ffffff');
    $table->text('topbar_left_content')->nullable();        // HTML/text: phone, email, etc.
    $table->text('topbar_right_content')->nullable();       // HTML/text: social icons, language switcher
    $table->boolean('topbar_dismissible')->default(false);

    // Dropdown Behavior (Desktop)
    $table->string('dropdown_trigger')->default('hover');   // hover, click
    $table->string('dropdown_animation')->default('fade');  // fade, slide-down, grow, none
    $table->integer('dropdown_delay')->default(200);        // ms hover delay

    // Mobile Menu
    $table->string('mobile_style')->default('drawer-left'); // drawer-left, drawer-right, fullscreen, accordion, bottom-sheet
    $table->integer('mobile_breakpoint')->default(768);     // px
    $table->string('mobile_bg_color')->nullable();
    $table->boolean('mobile_show_search')->default(false);
    $table->boolean('mobile_show_social')->default(false);

    // Search Integration
    $table->boolean('search_enabled')->default(false);
    $table->string('search_style')->default('expandable');  // expandable, inline, modal

    // Design Tokens
    $table->string('font_family')->nullable();
    $table->integer('font_size')->default(16);              // px
    $table->string('text_color')->default('#1a202c');
    $table->string('hover_color')->default('#2563eb');
    $table->string('active_color')->default('#1d4ed8');
    $table->string('bg_color')->default('#ffffff');
    $table->integer('height')->default(64);                 // px
    $table->integer('padding_x')->default(16);              // px
    $table->string('border_bottom')->nullable();            // e.g., "1px solid #e5e7eb"
    $table->string('shadow')->nullable();                   // e.g., "0 1px 3px rgba(0,0,0,0.1)"

    $table->timestamps();

    $table->unique('menu_id');
});
```

### `menu_item_pro` table

Extends core `menu_items` with Pro-only fields. One-to-one.

```php
Schema::create('menu_item_pro', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();

    // Mega Menu
    $table->boolean('is_mega_menu')->default(false);
    $table->integer('mega_columns')->default(3);            // 2, 3, 4, 5
    $table->string('mega_width')->default('full');          // full, contained, custom
    $table->integer('mega_custom_width')->nullable();       // px, only if mega_width = custom
    $table->string('mega_bg_color')->nullable();
    $table->string('mega_bg_image')->nullable();            // path to image
    $table->text('mega_footer_html')->nullable();           // optional footer row (e.g., CTA banner)

    // Item Enhancements
    $table->string('description')->nullable();              // subtitle text under label
    $table->string('image')->nullable();                    // thumbnail/icon image path
    $table->string('badge_text')->nullable();               // "New", "Hot", "Sale"
    $table->string('badge_color')->default('#ef4444');      // badge bg color
    $table->string('badge_text_color')->default('#ffffff');

    // CTA Button Styling
    $table->boolean('is_cta')->default(false);              // render as button
    $table->string('cta_bg_color')->default('#2563eb');
    $table->string('cta_text_color')->default('#ffffff');
    $table->string('cta_border_radius')->default('6px');
    $table->string('cta_hover_bg_color')->nullable();

    // Conditional Visibility
    $table->string('visibility')->default('always');        // always, logged-in, logged-out, role-based, date-range
    $table->json('visibility_roles')->nullable();           // ['admin', 'member']
    $table->date('visible_from')->nullable();
    $table->date('visible_until')->nullable();

    // Column Assignment (for mega menus — which column is this child in)
    $table->integer('mega_column_index')->nullable();       // 0-based column assignment

    $table->timestamps();

    $table->unique('menu_item_id');
});
```

---

## Implementation Phases

### Phase 1: Foundation — Header Layout & Logo Controls

**Priority: HIGH | Effort: 2-3 days | Impact: Immediate visual differentiation**

#### Tasks

```
1. Create migrations for menu_settings and menu_item_pro tables
2. Create MenuSettings model with relationship to core Menu model
3. Create MenuItemPro model with relationship to core MenuItem model
4. Create PHP Enums: LogoPosition, StickyBehavior
5. Create MenuFormExtension — inject "Pro Settings" tab into core Menu Filament form:
   - Template selector (visual picker with thumbnails)
   - Logo position (radio buttons with visual preview)
   - Logo max height (slider)
   - Height, padding, colors (color pickers)
   - Font family (select from system fonts + Google Fonts)
   - Border bottom and shadow presets
6. Create HeaderLayoutManager service:
   - resolveTemplate(Menu $menu): returns template name
   - buildHeaderClasses(MenuSettings $settings): generates Tailwind/inline styles
   - buildLogoClasses(MenuSettings $settings): logo positioning CSS
7. Create ProHeader Blade component that wraps core menu output
8. Create 5 template Blade files:
   - default.blade.php (logo left, nav right — enhanced version of core)
   - centered-logo.blade.php (nav split around centered logo)
   - split-menu.blade.php (different items left/right of logo)
   - hamburger-only.blade.php (always hamburger, menu on click/tap)
   - sidebar-nav.blade.php (vertical sidebar navigation)
9. Wire up: when Pro is active, frontend rendering uses ProHeader instead of core menu component
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 1. Create the following:

1. Migration: create_menu_settings_table (use the exact schema from the plan)
2. Migration: create_menu_item_pro_table (use the exact schema from the plan)
3. MenuSettings model with:
   - belongsTo Menu relationship
   - Casts for all color fields as strings, booleans as boolean
   - A static getOrCreate(Menu $menu) method
4. MenuItemPro model with:
   - belongsTo MenuItem relationship
   - Casts: json for visibility_roles, boolean for is_mega_menu, is_cta
   - A static getOrCreate(MenuItem $item) method
5. LogoPosition enum: Left, Center, Right, Hidden
6. StickyBehavior enum: None, Always, Smart, Shrink
7. HeaderLayoutManager service that reads MenuSettings and outputs template name + CSS classes
8. MenuFormExtension that adds a "Pro Design" tab to the existing Menu Filament resource form
   with fields for: template (Select), logo_position (Radio), logo_max_height (TextInput),
   height, padding_x, text_color, hover_color, active_color, bg_color (all ColorPicker),
   font_family (Select), border_bottom (TextInput), shadow (Select with presets)
9. ProHeader Blade component that accepts a Menu, loads its MenuSettings, and renders
   the appropriate template
10. Five template blade files under resources/views/components/templates/

Use Filament v3 conventions. Use Tailwind classes. Follow Laravel 11 patterns.
Ensure MenuFormExtension hooks into the existing MenuResource via Filament's form extension API.
```

---

### Phase 2: Sticky Header & Transparent Mode

**Priority: HIGH | Effort: 1-2 days | Impact: Professional-grade header behavior**

#### Tasks

```
1. Create StickyWrapper Blade component (Alpine.js powered):
   - Listens to scroll events
   - Applies sticky behavior based on MenuSettings->sticky_behavior
   - "always": fixed position, always visible
   - "smart": tracks scroll direction, hides on scroll down, shows on scroll up
   - "shrink": reduces height and logo size on scroll
   - Transitions between transparent and solid bg on scroll
   - Applies sticky_bg_color and sticky_logo_max_height when scrolled
2. Add Alpine.js data/logic:
   - x-data="stickyHeader({ behavior: '...', offset: N, ... })"
   - Handles scroll listeners with requestAnimationFrame for performance
   - Manages CSS class toggling for transitions
3. Extend MenuFormExtension with sticky fields:
   - sticky_behavior (Select)
   - sticky_offset (NumberInput)
   - sticky_bg_color (ColorPicker)
   - sticky_logo_max_height (NumberInput)
4. Add transparent header fields:
   - transparent_enabled (Toggle)
   - transparent_text_color (ColorPicker)
   - transparent_bg_color (ColorPicker, nullable)
5. Wire StickyWrapper into ProHeader templates — all templates wrap in StickyWrapper
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 2. Implement sticky header and transparent mode:

1. Create StickyWrapper Blade component using Alpine.js:
   - Accept props: behavior (none|always|smart|shrink), offset, bgColor, stickyBgColor,
     logoMaxHeight, stickyLogoMaxHeight, transparentEnabled, transparentTextColor
   - Alpine.js x-data handles scroll detection with requestAnimationFrame
   - For "smart": track lastScrollY, show header when scrolling up, hide when scrolling down
   - For "shrink": interpolate height and logo size between scroll 0 and offset
   - For transparent: start transparent, transition to solid bg after offset
   - Use CSS transitions for smooth animations (transform, background-color, height)
2. Add sticky and transparent fields to the existing MenuFormExtension Pro Design tab
3. Update all 5 ProHeader templates to wrap content in <x-tallcms-pro::sticky-wrapper>
4. Test that SPA mode anchor scrolling works correctly with sticky offset compensation
```

---

### Phase 3: Top Bar

**Priority: MEDIUM | Effort: 1 day | Impact: Common premium feature**

#### Tasks

```
1. Create TopBar Blade component:
   - Renders above the main header
   - Left content slot + right content slot
   - Supports HTML content (phone, email, social icons, announcement text)
   - Optional dismiss button (sets cookie/localStorage to hide)
   - Responsive: collapses to single line or hides on mobile
2. Add topbar fields to MenuFormExtension:
   - topbar_enabled (Toggle)
   - topbar_bg_color, topbar_text_color (ColorPicker)
   - topbar_left_content (RichEditor or Textarea)
   - topbar_right_content (RichEditor or Textarea)
   - topbar_dismissible (Toggle)
3. Wire TopBar into ProHeader — renders above StickyWrapper when enabled
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 3. Implement the top bar:

1. Create TopBar Blade component with Alpine.js:
   - Props: enabled, bgColor, textColor, leftContent, rightContent, dismissible
   - If dismissible, Alpine tracks dismissed state in localStorage
   - Responsive: stack content vertically below mobile_breakpoint
   - Smooth slide-up animation on dismiss
2. Add topbar fields to MenuFormExtension
3. Integrate TopBar into ProHeader templates — render above sticky wrapper
4. Ensure sticky header offset accounts for topbar height when present
```

---

### Phase 4: Mega Menu Builder

**Priority: HIGH | Effort: 3-4 days | Impact: Hero feature that sells the plugin**

#### Tasks

```
1. Extend MenuItemFormExtension to add "Mega Menu" section (only for top-level items):
   - is_mega_menu (Toggle) — shows/hides mega menu config
   - mega_columns (Select: 2, 3, 4, 5)
   - mega_width (Select: full, contained, custom)
   - mega_custom_width (NumberInput, shown if mega_width = custom)
   - mega_bg_color (ColorPicker)
   - mega_bg_image (FileUpload)
   - mega_footer_html (RichEditor)
2. Add column assignment to child items:
   - mega_column_index (Select: dynamically shows columns 1-N based on parent's mega_columns)
   - This lets the admin control which column each child item falls into
3. Create MegaMenuRenderer service:
   - Takes a MenuItem with is_mega_menu = true
   - Groups children by mega_column_index
   - Renders columns with equal width (or custom layout)
   - Supports header items as column titles
   - Renders footer row if mega_footer_html is set
4. Create MegaMenu Blade component:
   - Full-width or contained dropdown
   - CSS Grid layout for columns
   - Smooth animation (from dropdown_animation setting)
   - Items show: image, label, description, badge
5. Create mega-menu-column Blade component:
   - Renders a single column of items
   - Supports header item as column title
   - Rich item display: icon/image + label + description
6. Ensure keyboard accessibility:
   - Arrow key navigation within mega menu
   - Escape closes mega menu
   - Focus trap within open mega menu
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 4. Build the mega menu system:

1. Create MenuItemFormExtension that adds a "Mega Menu" section to the MenuItem form.
   Only show mega menu fields when the item has no parent (top-level).
   Fields: is_mega_menu (Toggle), mega_columns (Select 2-5), mega_width (Select),
   mega_custom_width (NumberInput conditional on mega_width=custom),
   mega_bg_color (ColorPicker), mega_bg_image (FileUpload), mega_footer_html (RichEditor).

2. For child items of a mega menu parent, show mega_column_index (Select).
   The options should dynamically reflect the parent's mega_columns count.

3. Create MegaMenuRenderer service:
   - groupChildrenByColumn(MenuItem $parent): returns Collection keyed by column index
   - renderMegaMenu(MenuItem $parent, MenuSettings $settings): returns rendered HTML
   - Each column can have a "header" type item as its title

4. Create MegaMenu Blade component:
   - Alpine.js for open/close behavior
   - Uses CSS Grid: grid-template-columns based on mega_columns
   - Full viewport width if mega_width=full, max-width container if contained
   - Background color/image support
   - Footer row spanning all columns
   - Dropdown animation from parent menu's dropdown_animation setting

5. Create mega-menu-column Blade component:
   - Renders items with: optional image (32x32), label, description, badge
   - Header items render as bold column titles with bottom border
   - Separator items render as <hr>

6. Keyboard accessibility: arrow keys navigate items, Escape closes, focus trap.
```

---

### Phase 5: Advanced Menu Item Enhancements

**Priority: MEDIUM | Effort: 2 days | Impact: Polished professional feel**

#### Tasks

```
1. Extend MenuItemFormExtension with enhancement fields:
   - description (Textarea) — subtitle text
   - image (FileUpload) — thumbnail/icon
   - badge_text (TextInput)
   - badge_color, badge_text_color (ColorPicker)
   - is_cta (Toggle) — render as button
   - cta_bg_color, cta_text_color, cta_border_radius, cta_hover_bg_color (shown if is_cta)
2. Update menu rendering components:
   - Regular dropdown items: show image + label + description + badge
   - CTA items: render as <a> styled as button with custom colors
   - Badges: absolute-positioned pill on top-right of label
3. Add conditional visibility:
   - visibility (Select: always, logged-in, logged-out, role-based, date-range)
   - visibility_roles (CheckboxList, shown if role-based)
   - visible_from, visible_until (DatePicker, shown if date-range)
4. Create MenuVisibilityResolver service:
   - resolve(MenuItem $item): bool — checks auth state, roles, date range
   - Filters items before rendering
   - Caches auth check per request
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 5. Implement menu item enhancements:

1. Extend MenuItemFormExtension with sections:
   - "Appearance" section: description (Textarea), image (FileUpload),
     badge_text (TextInput), badge_color (ColorPicker), badge_text_color (ColorPicker)
   - "CTA Button" section: is_cta (Toggle), cta_bg_color, cta_text_color,
     cta_border_radius, cta_hover_bg_color (all conditional on is_cta)
   - "Visibility" section: visibility (Select), visibility_roles (CheckboxList
     conditional on role-based), visible_from and visible_until (DatePicker
     conditional on date-range)

2. Create MenuVisibilityResolver service:
   - shouldShow(MenuItem $item): bool
   - Check visibility rules against current auth state and date
   - Cache auth()->user() and roles per request

3. Update all menu rendering Blade components to:
   - Filter items through MenuVisibilityResolver
   - Show description text below label in smaller font
   - Show image as 24x24 or 32x32 inline before label
   - Show badge as colored pill after label
   - Render CTA items as styled <a> buttons

4. Ensure badge, description, and image are also rendered in mega menu columns.
```

---

### Phase 6: Mobile Menu Styles

**Priority: MEDIUM | Effort: 2 days | Impact: Differentiator on mobile**

#### Tasks

```
1. Create MobileDrawer component (Alpine.js):
   - Slide-in panel from left or right
   - Overlay backdrop with click-to-close
   - Smooth CSS transform animation
   - Shows logo, search (optional), menu items, social icons (optional)
   - Nested items as accordion or drill-down
2. Create mobile-overlay component:
   - Full-screen overlay menu
   - Centered navigation links, large text
   - Staggered fade-in animation for items
3. Add mobile fields to MenuFormExtension:
   - mobile_style (Select with visual picker)
   - mobile_breakpoint (NumberInput)
   - mobile_bg_color (ColorPicker)
   - mobile_show_search (Toggle)
   - mobile_show_social (Toggle)
4. Create DropdownAnimation and MobileMenuStyle enums
5. Wire mobile components: based on mobile_style setting, ProHeader renders
   the appropriate mobile component inside a responsive Alpine.js wrapper
   that detects viewport width against mobile_breakpoint
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 6. Implement mobile menu styles:

1. Create MobileDrawer Blade component with Alpine.js:
   - Props: side (left|right), bgColor, showSearch, showSocial, items
   - x-data="mobileMenu()" with open/close state
   - Slide transform animation with backdrop overlay
   - Render menu items as nested accordion (click to expand children)
   - Optional search input at top
   - Optional social icon row at bottom
   - Body scroll lock when open

2. Create mobile-overlay Blade component with Alpine.js:
   - Full-screen fixed overlay
   - Large centered nav links
   - Staggered animation: items fade in sequentially on open
   - Close button top-right

3. Create MobileMenuStyle enum: DrawerLeft, DrawerRight, Fullscreen, Accordion, BottomSheet
4. Create DropdownAnimation enum: Fade, SlideDown, Grow, None

5. Add mobile fields to MenuFormExtension
6. Update ProHeader to conditionally render the correct mobile component
   based on mobile_style, using Alpine.js to detect window.innerWidth vs mobile_breakpoint

7. Ensure hamburger button is customizable (color from design tokens).
```

---

### Phase 7: Search & Dropdown Animation

**Priority: LOW | Effort: 1 day | Impact: Polish**

#### Tasks

```
1. Create expandable search component:
   - Icon button that expands to search input on click
   - Overlays nav items or pushes them aside
   - Submits to site search route
   - Alpine.js with focus management
2. Create modal search component:
   - Full-screen or centered modal with large search input
   - Triggered by search icon in nav
   - Keyboard shortcut (Cmd/Ctrl + K)
3. Implement dropdown animations:
   - Read dropdown_animation from MenuSettings
   - Apply CSS animation classes to all dropdown containers
   - Fade: opacity transition
   - SlideDown: translateY(-10px) to 0 + opacity
   - Grow: scale(0.95) to scale(1) + opacity
   - None: instant show/hide
4. Implement hover vs click trigger:
   - Read dropdown_trigger from MenuSettings
   - Hover: Alpine mouseenter/mouseleave with configurable delay
   - Click: Alpine click handler with click-outside-to-close
```

#### Claude Code Prompt

```
Read PRO_MENU_PLAN.md Phase 7. Implement search integration and dropdown animations:

1. Create expandable search component (Alpine.js):
   - Starts as a search icon button
   - On click: expands to show text input with smooth width animation
   - On Escape or click-outside: collapses back to icon
   - On submit: navigates to /search?q={query}

2. Create modal search component (Alpine.js):
   - Triggered by search icon or Cmd+K
   - Centered modal with large input
   - Focus trapped in modal, Escape closes

3. Apply dropdown animations to all dropdown menus and mega menus:
   - Read dropdown_animation from MenuSettings
   - Use CSS transition classes: opacity, transform (translateY/scale)
   - Alpine x-transition directives for enter/leave

4. Implement hover vs click dropdown triggers:
   - hover: x-on:mouseenter with setTimeout(delay), x-on:mouseleave to close
   - click: x-on:click.prevent to toggle, x-on:click.outside to close
```

---

## Config File

```php
// config/pro-menu.php
return [
    // Available templates
    'templates' => [
        'default'             => 'Default (Logo Left)',
        'centered-logo'       => 'Centered Logo',
        'split-menu'          => 'Split Menu',
        'hamburger-only'      => 'Hamburger Only',
        'sidebar-nav'         => 'Sidebar Navigation',
    ],

    // Google Fonts available in font picker
    'google_fonts' => [
        'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
        'Poppins', 'Nunito', 'Raleway', 'Source Sans Pro', 'DM Sans',
    ],

    // Shadow presets for header
    'shadow_presets' => [
        'none'   => 'None',
        'sm'     => 'Subtle',
        'md'     => 'Medium',
        'lg'     => 'Large',
        'xl'     => 'Extra Large',
    ],

    // Max mega menu columns
    'max_mega_columns' => 5,

    // Default mobile breakpoint
    'default_mobile_breakpoint' => 768,

    // Cache TTL for resolved menu visibility (seconds)
    'visibility_cache_ttl' => 60,
];
```

---

## Filament Admin UX Notes

### Menu Form — Pro Tab Layout

When Pro is active, the Menu edit form should have these tabs:

```
[General] [Items] [Pro Design] [Header Layout] [Mobile] [Top Bar]
```

- **Pro Design** — Colors, fonts, shadows, height, border
- **Header Layout** — Template picker (visual cards), logo position, sticky, transparent
- **Mobile** — Mobile style, breakpoint, mobile-specific colors, search/social toggles
- **Top Bar** — Enable, content fields, colors, dismissible

### MenuItem Form — Pro Sections

When editing a menu item with Pro active:

```
[Basic]                    ← Existing: label, type, page, URL, target, active
[Appearance]               ← Pro: description, image, badge
[CTA Button]               ← Pro: is_cta + button styling
[Mega Menu]                ← Pro: only for top-level items
[Visibility]               ← Pro: conditional display rules
```

### Visual Template Picker

The template selector should render as a grid of clickable cards, each showing a mini wireframe preview of the layout. Use Filament's `Grid` layout with custom `ViewField` components.

---

## Testing Notes

### Manual Test Checklist

```
□ Each template renders correctly with sample menu items
□ Logo position works across all templates
□ Sticky behaviors work: always, smart, shrink
□ Transparent header transitions to solid on scroll
□ Top bar renders above header and dismiss works
□ Mega menu opens with correct columns and content
□ Badges display on menu items
□ CTA button styling applies correctly
□ Conditional visibility: logged-in/out items show/hide correctly
□ Date-range visibility respects from/until dates
□ Mobile drawer opens/closes with animation
□ Mobile fullscreen overlay works
□ Dropdown animations: fade, slide, grow
□ Hover vs click triggers work on desktop
□ Search expandable and modal both work
□ SPA mode: all features work with anchor links
□ Multi-language: labels translate, Pro fields don't need translation
□ Keyboard navigation works through mega menu
□ Screen reader announces menu states correctly
□ No layout shift on page load (CLS)
□ Menu works with 0 items, 1 item, 50+ items
```

---

## Implementation Order Summary

| Phase | Feature | Days | Cumulative |
|-------|---------|------|------------|
| 1 | Header Layout & Logo Controls | 2-3 | 3 |
| 2 | Sticky & Transparent Header | 1-2 | 5 |
| 3 | Top Bar | 1 | 6 |
| 4 | Mega Menu Builder | 3-4 | 10 |
| 5 | Menu Item Enhancements | 2 | 12 |
| 6 | Mobile Menu Styles | 2 | 14 |
| 7 | Search & Animations | 1 | 15 |

**Total estimated: ~15 working days**

Phase 1-2 alone (5 days) gives you a shippable MVP with immediate visual impact. Phase 4 (mega menu) is the hero feature — prioritize it right after the header foundation.

---

## Key Decisions for You

Before starting, decide:

1. **How does Pro extend core Filament forms?** — Filament supports form extensions via plugins. Confirm your existing TallCMS plugin architecture pattern for extending resources.

2. **Template override mechanism** — How does Pro override core menu Blade components? Likely via Laravel's view composer or a `@if(pro_active)` check in the core header component that delegates to Pro's component.

3. **Image storage** — Where do menu item images and mega menu backgrounds get stored? Likely the existing TallCMS media library with Filament's FileUpload.

4. **Google Fonts loading** — When a custom Google Font is selected, the Pro header component needs to inject a `<link>` tag. Decide if this goes in the component or via a middleware/view composer.
