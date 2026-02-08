---
title: "Theme Switcher Development"
slug: "theme-switcher"
audience: "developer"
category: "developers"
order: 15
time: 10
prerequisites:
  - "themes"
---

# Theme Switcher Development

> **What you'll learn:** How the runtime theme switcher works and how to enable it in your themes.

---

## Overview

TallCMS supports **runtime theme switching** using daisyUI's theme system. Users can switch between light, dark, and other color schemes without page reloads. The theme preference is stored in `localStorage` and persists across sessions.

### How It Works

1. Theme declares available presets in `theme.json`
2. TallCMS detects if multiple presets are available
3. Theme renders a switcher UI (drawer, dropdown, etc.)
4. JavaScript sets `data-theme` attribute on `<html>` and stores preference

---

## Enabling Theme Switching

### 1. Configure theme.json

Add multiple presets to enable the switcher:

```json
{
    "name": "My Theme",
    "slug": "my-theme",
    "daisyui": {
        "preset": "light",
        "prefersDark": "dark",
        "presets": ["light", "dark", "corporate", "business"]
    },
    "supports": {
        "theme_controller": true
    }
}
```

**Preset Options:**

| Value | Description |
|-------|-------------|
| `"all"` | All 29 daisyUI presets available |
| `["light", "dark"]` | Specific presets only |
| `["light"]` | Single preset (disables switcher) |

### 2. Update Theme CSS

Include all configured presets in `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "@tailwindcss/typography";

@plugin "daisyui" {
    themes: light --default, dark --prefersdark, corporate, business;
}
```

### 3. Build Theme

```bash
cd themes/my-theme
npm run build
```

---

## Helper Functions

### Check Theme Controller Support

```php
// Returns true if theme has multiple presets
supports_theme_controller(): bool

// Get available presets
daisyui_presets(): array
```

### Usage in Blade

```blade
@if(supports_theme_controller())
    {{-- Render theme switcher UI --}}
    @include('theme.my-theme::components.theme-switcher')
@endif
```

---

## Implementing the Switcher UI

### Drawer Pattern (Recommended)

The drawer pattern provides a full list of available themes:

```blade
{{-- Layout wrapper --}}
@if(supports_theme_controller())
<div class="drawer drawer-end">
    <input id="theme-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
@endif
        {{-- Page content here --}}
@if(supports_theme_controller())
    </div>

    {{-- Theme drawer sidebar --}}
    <div class="drawer-side z-[60]">
        <label for="theme-drawer" class="drawer-overlay"></label>
        <div class="bg-base-200 min-h-full w-80 p-4">
            <h2 class="text-lg font-bold mb-4">Choose Theme</h2>
            <ul class="menu w-full p-0 gap-1">
                @foreach(daisyui_presets() as $preset)
                    <li>
                        <button type="button" class="theme-btn" data-theme-value="{{ $preset }}">
                            <span class="badge badge-sm" data-theme="{{ $preset }}">
                                <span class="w-2 h-2 rounded-full bg-primary"></span>
                                <span class="w-2 h-2 rounded-full bg-secondary"></span>
                                <span class="w-2 h-2 rounded-full bg-accent"></span>
                            </span>
                            {{ ucfirst($preset) }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif
```

### Toggle Button

```blade
{{-- Opens the theme drawer --}}
<label for="theme-drawer" class="btn btn-ghost btn-sm btn-circle">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
    </svg>
</label>
```

---

## JavaScript Implementation

Add this script to handle theme switching and persistence:

```javascript
(function() {
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        // Update active button styling
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.toggle('btn-active', btn.dataset.themeValue === theme);
        });

        // Close drawer after selection
        const drawer = document.getElementById('theme-drawer');
        if (drawer) drawer.checked = false;
    }

    function initThemeButtons() {
        const savedTheme = localStorage.getItem('theme') ||
                          document.documentElement.getAttribute('data-theme') ||
                          'light';

        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.toggle('btn-active', btn.dataset.themeValue === savedTheme);
            btn.addEventListener('click', function() {
                setTheme(this.dataset.themeValue);
            });
        });
    }

    // Initialize on page load
    initThemeButtons();

    // Re-initialize after Livewire navigation
    document.addEventListener('livewire:navigated', initThemeButtons);
})();
```

### Prevent Flash of Wrong Theme

Add this in `<head>` before any stylesheets:

```html
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
</script>
```

---

## Mega Menu Integration

The Mega Menu plugin automatically includes theme switcher support when `supports_theme_controller()` returns `true`. The switcher appears in:

- Desktop navbar (via `theme-switcher` partial)
- Mobile drawer footer

No additional configuration needed - install the Mega Menu plugin and it works with your theme's presets.

---

## Configuration Reference

### theme.json Fields

| Field | Type | Description |
|-------|------|-------------|
| `daisyui.preset` | string | Default theme preset |
| `daisyui.prefersDark` | string | Preset for `prefers-color-scheme: dark` |
| `daisyui.presets` | string\|array | Available presets (`"all"` or array) |
| `supports.theme_controller` | bool | Declares switcher support (informational) |

### Available daisyUI Presets

```
light, dark, cupcake, bumblebee, emerald, corporate, synthwave,
retro, cyberpunk, valentine, halloween, garden, forest, aqua,
lofi, pastel, fantasy, wireframe, black, luxury, dracula, cmyk,
autumn, business, acid, lemonade, night, coffee, winter, dim, nord, sunset
```

---

## Common Pitfalls

**"Theme switcher not appearing"**
Ensure `daisyui.presets` has more than one preset. Single preset = no switcher.

**"Theme resets on page load"**
Add the flash-prevention script in `<head>` before stylesheets load.

**"Theme not persisting after Livewire navigation"**
Re-apply theme in `livewire:navigated` event handler.

**"Preset colors not matching"**
Ensure CSS `@plugin "daisyui"` includes all presets listed in `theme.json`.

---

## Next Steps

- [Theme development](themes)
- [Mega Menu plugin](mega-menu)
- [Block styling](block-styling)
