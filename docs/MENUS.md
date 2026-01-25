# Menu Management

TallCMS includes a flexible menu system for managing site navigation. Create menus for different locations (header, footer, sidebar, mobile) with support for nested items, page links, external URLs, and multi-language labels.

## Features

- **Multiple Locations** - Header, footer, sidebar, mobile, or custom locations
- **Nested Items** - Up to 5 levels of hierarchy with drag-and-drop reordering
- **Item Types** - Page links, external URLs, custom paths, headers, separators
- **Active State** - Automatic highlighting of current page in navigation
- **SPA Mode** - Automatic anchor link conversion for single-page sites
- **Translations** - Multi-language menu labels
- **Theme Integration** - Customizable styles with theme overrides

## Admin Interface

### Creating Menus

Navigate to **Content > Menus** in the admin panel.

1. Click **New Menu**
2. Fill in the form:
   - **Name** - Internal identifier (e.g., "Main Navigation")
   - **Location** - Where the menu appears (header, footer, sidebar, mobile)
   - **Description** - Optional notes for administrators
   - **Active** - Enable/disable the menu
3. Click **Create**

### Menu Locations

| Location | Purpose | Recommended Style |
|----------|---------|-------------------|
| `header` | Main site navigation | `horizontal` |
| `footer` | Footer links | `footer` or `footer-vertical` |
| `sidebar` | Sidebar navigation | `sidebar` |
| `mobile` | Mobile menu (falls back to header) | `mobile` |

### Managing Menu Items

Click **Manage Items** on any menu to open the item manager.

#### Adding Items

1. Click **New Item**
2. Configure the item:
   - **Label** - Display text (translatable)
   - **Type** - Item type (see below)
   - **Page** - Select a CMS page (for page type)
   - **URL** - Enter URL (for external/custom types)
   - **Active** - Enable/disable item

#### Item Types

| Type | Description | URL Source |
|------|-------------|------------|
| **Page** | Link to a CMS page | Auto-generated from page slug |
| **External** | Link to external website | Full URL (https://...) |
| **Custom** | Custom URL or path | Relative path (/contact) or anchor (#section) |
| **Header** | Section heading (no link) | None |
| **Separator** | Visual divider | None |

#### Reordering Items

- **Drag and drop** items to reorder
- **Nest items** by dragging onto a parent item
- Maximum nesting depth: 5 levels

### Preview

Click **Preview** on a menu to see its structure in a modal, including:
- Menu metadata (name, location, status)
- Visual tree of all items
- Linked page names
- Active/inactive status indicators

---

## Using Menus in Themes

### Basic Usage

```blade
{{-- Header menu --}}
<x-menu location="header" style="horizontal" />

{{-- Footer menu --}}
<x-menu location="footer" style="footer" />

{{-- Sidebar menu --}}
<x-menu location="sidebar" style="sidebar" />

{{-- Mobile menu with fallback --}}
@if(menu('mobile'))
    <x-menu location="mobile" style="mobile" />
@else
    <x-menu location="header" style="mobile" />
@endif
```

### Available Styles

| Style | Description | Best For |
|-------|-------------|----------|
| `horizontal` | Inline items with dropdowns | Headers |
| `vertical` | Stacked items | Sidebars |
| `sidebar` | Collapsible sections with background | Sidebars |
| `mobile` | Large touch targets, collapsible | Mobile menus |
| `footer` | Compact horizontal links | Footer |
| `footer-vertical` | Vertical stack for footer columns | Footer columns |

### Menu Helper Function

Access menu data programmatically:

```php
// Get menu items as array
$items = menu('header');

// Check if menu exists
if (menu('mobile')) {
    // Mobile menu exists
}

// Iterate over items
@foreach(menu('header') as $item)
    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
@endforeach
```

### Menu Item Structure

Each menu item returned by `menu()` contains:

```php
[
    'id' => 1,
    'label' => 'About Us',
    'url' => '/about',
    'type' => 'page',           // page, external, custom, header, separator
    'target' => '_self',        // _self or _blank
    'icon' => 'heroicon-o-info', // Optional icon class
    'css_class' => 'featured',  // Optional CSS class
    'is_active' => true,        // Current page match
    'has_active_child' => false, // Child is active
    'children' => [...]         // Nested items
]
```

### Active State Styling

The menu component automatically detects active items:

```blade
{{-- Active items receive these attributes --}}
<a href="/about"
   class="text-primary-600 bg-primary-50"
   aria-current="page">
    About
</a>

{{-- Parents with active children --}}
<details open>
    <summary class="text-primary-600">Services</summary>
    <ul>
        <li><a href="/services/web" aria-current="page">Web Design</a></li>
    </ul>
</details>
```

### Custom Rendering

For complete control, use the helper function directly:

```blade
@php
    $menuItems = menu('header');
@endphp

@if($menuItems)
    <nav class="my-custom-nav">
        @foreach($menuItems as $item)
            @if($item['type'] === 'separator')
                <hr class="my-divider">
            @elseif($item['type'] === 'header')
                <span class="my-header">{{ $item['label'] }}</span>
            @else
                <a
                    href="{{ $item['url'] }}"
                    target="{{ $item['target'] }}"
                    @class([
                        'my-link',
                        'active' => $item['is_active'],
                        $item['css_class'] ?? '',
                    ])
                    @if($item['is_active']) aria-current="page" @endif
                >
                    @if($item['icon'])
                        <x-dynamic-component :component="$item['icon']" class="w-4 h-4" />
                    @endif
                    {{ $item['label'] }}
                </a>

                @if(!empty($item['children']))
                    {{-- Render children recursively --}}
                @endif
            @endif
        @endforeach
    </nav>
@endif
```

---

## Theme Overrides

Themes can customize menu appearance by overriding component views.

### Override Location

```
themes/{your-theme}/resources/views/components/menu/
├── horizontal.blade.php
├── vertical.blade.php
├── sidebar.blade.php
├── mobile.blade.php
├── footer.blade.php
└── footer-vertical.blade.php
```

### Creating a Custom Style

1. Create a new style file in your theme:

```blade
{{-- themes/my-theme/resources/views/components/menu/mega.blade.php --}}

@props(['items' => []])

<nav class="mega-menu">
    @foreach($items as $item)
        @if($item['children'])
            <div class="mega-menu-group">
                <span class="mega-menu-heading">{{ $item['label'] }}</span>
                <div class="mega-menu-items">
                    @foreach($item['children'] as $child)
                        <a href="{{ $child['url'] }}">{{ $child['label'] }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @endif
    @endforeach
</nav>
```

2. Use in your layout:

```blade
<x-menu location="header" style="mega" />
```

---

## SPA Mode Integration

When **Single-Page Mode** is enabled in Site Settings, menu links automatically convert to anchor links.

### How It Works

| Regular Mode | SPA Mode |
|--------------|----------|
| `/about` | `#about-5` |
| `/services` | `#services-12` |
| `/contact` | `#contact-8` |

The anchor format is `{slug}-{page_id}` to avoid collisions.

### Custom Anchors

For custom menu items, you can manually set anchors:

- **Type**: Custom
- **URL**: `#my-section`

These are preserved as-is in both modes.

---

## Multi-Language Support

Menu labels support translations when internationalization is enabled.

### Editing Translations

1. Open **Manage Items** for a menu
2. Use the **locale switcher** in the header
3. Edit labels in each language
4. Labels fall back to default language if not translated

### Programmatic Access

```php
// Labels are automatically translated based on current locale
$items = menu('header');

// Each item's label is in the current language
echo $items[0]['label']; // "About Us" or "Über uns" etc.
```

---

## Database Structure

### Menus Table (`tallcms_menus`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Internal menu name |
| `location` | string | Unique location identifier |
| `description` | text | Admin notes |
| `is_active` | boolean | Enable/disable menu |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update |

### Menu Items Table (`tallcms_menu_items`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `menu_id` | bigint | Parent menu reference |
| `label` | string (JSON) | Translatable label |
| `type` | enum | page, external, custom, separator, header |
| `page_id` | bigint | Linked CMS page (nullable) |
| `url` | text | URL for external/custom (nullable) |
| `meta` | JSON | icon, css_class, open_in_new_tab |
| `is_active` | boolean | Enable/disable item |
| `_lft`, `_rgt`, `parent_id` | int | Nested set columns |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update |

---

## API Reference

### Helper Functions

```php
// Get menu items by location
menu(string $location): ?array

// Check if menu item URL matches current page
isMenuItemActive(?string $itemUrl): bool

// Render menu as HTML string
render_menu(string $location, array $options = []): string
```

### Menu Model

```php
use TallCms\Cms\Models\TallcmsMenu;

// Get menu by location
$menu = TallcmsMenu::byLocation('header');

// Access items
$items = $menu->items;        // Root-level items
$allItems = $menu->allItems;  // All items (flat)
$active = $menu->activeItems; // Active items only
```

### Menu Item Model

```php
use TallCms\Cms\Models\TallcmsMenuItem;

// Get resolved URL
$url = $item->getResolvedUrl();

// Access metadata
$icon = $item->icon;
$cssClass = $item->css_class;
$newTab = $item->open_in_new_tab;

// Get children
$children = $item->activeChildren;
```

### URL Resolver Service

```php
use TallCms\Cms\Services\MenuUrlResolver;

$resolver = app('menu.url.resolver');

// Resolve item URL
$url = $resolver->resolve($menuItem);

// Check if opens in new tab
$newTab = $resolver->shouldOpenInNewTab($menuItem);

// Get target attribute
$target = $resolver->getTargetAttribute($menuItem); // '_self' or '_blank'
```

---

## Permissions

Menu management requires these permissions (managed via Filament Shield):

| Permission | Description |
|------------|-------------|
| `view_any_tallcms::menu` | View menu list |
| `view_tallcms::menu` | View single menu |
| `create_tallcms::menu` | Create new menus |
| `update_tallcms::menu` | Edit menus and items |
| `delete_tallcms::menu` | Delete menus |
| `reorder_tallcms::menu` | Reorder menu items |

---

## Best Practices

### Menu Organization

1. **One menu per location** - Avoid multiple menus for the same location
2. **Limit nesting** - Keep hierarchy to 2-3 levels for usability
3. **Use headers** - Group related items with header type
4. **Mobile consideration** - Test nested menus on mobile devices

### Performance

1. **Cache menus** in production (automatic via Laravel's query cache)
2. **Limit items** - Large menus impact page load
3. **Eager loading** - Menu helper automatically eager loads relationships

### Accessibility

1. **Descriptive labels** - Use clear, concise text
2. **Keyboard navigation** - Built-in styles support keyboard nav
3. **ARIA attributes** - `aria-current="page"` added automatically
4. **Focus indicators** - DaisyUI provides default focus styles

---

## Troubleshooting

### Menu Not Appearing

1. Verify menu is set to **Active**
2. Check **location** matches your component usage
3. Ensure menu has **active items**
4. Clear view cache: `php artisan view:clear`

### Items Not Showing

1. Check items are set to **Active**
2. For page links, verify the page is **Published**
3. Check parent items are active (inactive parents hide children)

### Wrong URL Generated

1. For page links, check page slug in admin
2. For custom links, ensure URL starts with `/` or `http`
3. In SPA mode, verify page has correct ID

### Active State Not Working

1. Ensure URLs match exactly (trailing slashes matter)
2. Check for query string differences
3. For SPA mode, verify anchor format

---

## Related Documentation

- [Site Settings](SITE_SETTINGS.md) - SPA mode configuration
- [Theme Development](THEME_DEVELOPMENT.md) - Custom menu styles
- [Internationalization](INTERNATIONALIZATION.md) - Multi-language setup
