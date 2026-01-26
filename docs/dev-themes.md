---
title: "Theme Development"
slug: "themes"
audience: "developer"
category: "developers"
order: 10
time: 30
prerequisites:
  - "installation"
---

# TallCMS Theme Development Guide

Complete guide for creating and customizing themes in TallCMS using the file-based multi-theme system.

## Theme System Architecture

TallCMS uses a **file-based theme system** where each theme is a self-contained directory with its own configuration, assets, and template overrides. Themes are automatically discovered and can override any template in the application.

### Core Concepts

- **File-Based Themes**: Themes live in `themes/{slug}/` directories with Laravel-compliant structure
- **Template Override Resolution**: Themes override templates by path priority (theme → app default)
- **Asset Management**: Each theme has its own Vite build process with automatic asset compilation
- **Configuration**: Themes use `theme.json` for metadata and daisyUI/Tailwind v4 CSS config in `resources/css/app.css`
- **Zero Code Changes**: Existing blocks and templates work unchanged with theme overrides

### Directory Structure

```
themes/{slug}/                       # Theme root directory
├── theme.json                       # Theme metadata and configuration
├── package.json                     # NPM dependencies and build scripts
├── vite.config.js                   # Vite build configuration
├── public/                          # Static assets (images, fonts, etc.)
│   ├── css/
│   ├── js/
│   ├── img/
│   └── build/                       # Compiled assets (auto-generated)
│       ├── manifest.json            # Vite asset manifest
│       └── assets/                  # Hashed asset files
└── resources/                       # Source files
    ├── views/                       # Template overrides
    │   ├── layouts/                 # Layout templates
    │   ├── cms/blocks/              # Block template overrides
    │   └── components/              # Component overrides
    ├── css/
    │   └── app.css                  # Main stylesheet (Tailwind v4 + daisyUI)
    ├── js/
    │   └── app.js                   # JavaScript entry point
    └── img/                         # Source images
```

## Quick Start

### 1. Create a New Theme

```bash
# Interactive mode (recommended)
php artisan make:theme

# Or with options
php artisan make:theme my-awesome-theme \
    --description="A modern theme" \
    --author="Your Name"
```

### 2. Customize Theme Configuration

Edit `themes/my-awesome-theme/theme.json`:

```json
{
    "name": "My Awesome Theme",
    "slug": "my-awesome-theme",
    "version": "1.0.0",
    "description": "A modern, clean theme for business websites",
    "author": "Your Name",
    "daisyui": {
        "preset": "corporate",
        "prefersDark": "business"
    },
    "supports": {
        "dark_mode": true,
        "responsive": true,
        "theme_controller": false
    }
}
```

### 3. Build and Activate

```bash
# Install dependencies
npm install

# Navigate to theme
cd themes/my-awesome-theme

# Build assets
npm run build

# Return and activate
cd ../..
php artisan theme:activate my-awesome-theme
```

## Theme Customization

### Template Overrides

Override any template by creating files in the same relative path structure:

```bash
# Override main layout
themes/my-theme/resources/views/layouts/app.blade.php

# Override blocks
themes/my-theme/resources/views/cms/blocks/hero-block.blade.php

# Override components
themes/my-theme/resources/views/components/hero-section.blade.php
```

**Resolution Priority:**
1. Theme Template: `themes/{active}/resources/views/...`
2. Application Default: `resources/views/...`

### Block Styling

Use daisyUI component classes and semantic colors:

```blade
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">Pricing</h2>
        <p class="text-base-content">Simple, predictable pricing.</p>
        <div class="card-actions">
            <a href="#" class="btn btn-primary">Get started</a>
        </div>
    </div>
</div>
```

### Asset Management

```blade
{{-- Compiled assets --}}
@themeVite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Static assets --}}
<img src="@themeAsset('images/logo.png')" alt="Logo">
```

## Custom daisyUI Theme

Define custom colors in `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "@tailwindcss/typography";

@plugin "daisyui" {
    themes: mybrand --default;
}

@plugin "daisyui/theme" {
    name: "mybrand";
    default: true;
    color-scheme: light;

    --color-base-100: oklch(98% 0.02 240);
    --color-base-content: oklch(20% 0.05 240);
    --color-primary: oklch(55% 0.3 240);
    --color-primary-content: oklch(98% 0.01 240);
    /* ... additional color variables */
}
```

## Development Workflow

### Development Mode

```bash
cd themes/my-theme
npm run dev  # Hot reloading
```

### Production Build

```bash
cd themes/my-theme
npm run build
```

### Theme Commands

```bash
php artisan theme:list              # List themes
php artisan theme:list --detailed   # Detailed info
php artisan theme:build my-theme    # Build assets
php artisan theme:activate my-theme # Activate theme
```

## Best Practices

1. **daisyUI-First**: Use component classes before custom CSS
2. **Semantic Colors**: Use `bg-base-*`, `text-base-content`, `btn-primary`
3. **Mobile-First**: Design for mobile, enhance for larger screens
4. **Override Minimally**: Only override what needs to change
5. **Test Both**: Check admin preview and frontend display

## Troubleshooting

**Theme not activating:**
- Ensure `theme.json` is valid JSON
- Check `npm run build` completed
- Verify symlinks in `public/themes/`

**Styles not loading:**
- Check Vite manifest exists
- Use `@themeVite` directive
- Clear view cache: `php artisan view:clear`

**Template overrides not working:**
- Verify file path matches exactly
- Ensure theme is activated
- Clear caches

## Reference

### Helper Functions

```php
$themeManager = app('theme.manager');
$activeTheme = active_theme();
$assetUrl = theme_asset('images/logo.png');
```

### Blade Directives

```blade
@themeVite(['resources/css/app.css'])
@themeAsset('images/logo.png')
```

---

## Next Steps

- [Block development](block-development)
- [Block styling](block-styling)
- [Plugin development](plugins)
