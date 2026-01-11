# TallCMS Theme Development Guide

Complete guide for creating and customizing themes in TallCMS using the file-based multi-theme system.

## 🏗️ Theme System Architecture

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

## 🚀 Quick Start

### 1. Create a New Theme

#### Interactive Mode (Recommended)
```bash
php artisan make:theme
```

The interactive mode will guide you through creating a theme with prompts for:
- Theme name and description
- Author information
- daisyUI mode (single preset, all presets with theme-controller, or custom)
- Optional dark mode preset (explicit opt-in)

#### Non-Interactive Mode
```bash
php artisan make:theme my-awesome-theme \
    --description="A modern, clean theme for business websites" \
    --author="Your Name" \
    --theme-version="1.0.0"
```

#### Force Interactive Mode
```bash
php artisan make:theme my-theme --interactive
```

This creates a complete theme structure with:
- Complete Laravel-compliant directory layout
- Properly populated theme metadata (`theme.json`)
- Package configuration with Vite + Tailwind v4
- daisyUI-ready CSS entry (`resources/css/app.css`)
- Example templates and components
- Proper build configuration

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
    },
    "build": {
        "css": "resources/css/app.css",
        "js": "resources/js/app.js",
        "output": "public"
    }
}
```

### 3. Customize daisyUI Theme Colors

Use a custom daisyUI theme in `resources/css/app.css`. Custom themes must define all required variables (20 color variables + 8 design tokens).

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
    --color-base-200: oklch(95% 0.03 240);
    --color-base-300: oklch(92% 0.04 240);
    --color-base-content: oklch(20% 0.05 240);
    --color-primary: oklch(55% 0.3 240);
    --color-primary-content: oklch(98% 0.01 240);
    --color-secondary: oklch(70% 0.25 200);
    --color-secondary-content: oklch(98% 0.01 200);
    --color-accent: oklch(65% 0.25 160);
    --color-accent-content: oklch(98% 0.01 160);
    --color-neutral: oklch(50% 0.05 240);
    --color-neutral-content: oklch(98% 0.01 240);
    --color-info: oklch(70% 0.2 220);
    --color-info-content: oklch(98% 0.01 220);
    --color-success: oklch(65% 0.25 140);
    --color-success-content: oklch(98% 0.01 140);
    --color-warning: oklch(80% 0.25 80);
    --color-warning-content: oklch(20% 0.05 80);
    --color-error: oklch(65% 0.3 30);
    --color-error-content: oklch(98% 0.01 30);

    --radius-selector: 1rem;
    --radius-field: 0.25rem;
    --radius-box: 0.5rem;
    --size-selector: 0.25rem;
    --size-field: 0.25rem;
    --border: 1px;
    --depth: 1;
    --noise: 0;
}
```

Tip: Use the daisyUI theme generator to produce the full variable set.

### 4. Build and Activate

```bash
# Install dependencies once at the project root
npm install

# Navigate to theme directory
cd themes/my-awesome-theme

# Build assets
npm run build

# Return to project root and activate theme
cd ../..
php artisan theme:activate my-awesome-theme
```

## 🎨 Theme Customization

### Template Overrides

Override any template by creating files in the same relative path structure:

```bash
# Override main layout
themes/my-theme/resources/views/layouts/app.blade.php

# Override specific blocks
themes/my-theme/resources/views/cms/blocks/content-block.blade.php
themes/my-theme/resources/views/cms/blocks/hero-block.blade.php
themes/my-theme/resources/views/cms/blocks/pricing-block.blade.php

# Override components  
themes/my-theme/resources/views/components/hero-section.blade.php
```

**Template Resolution Priority:**
1. **Theme Template**: `themes/{active}/resources/views/cms/blocks/pricing-block.blade.php`
2. **Application Default**: `resources/views/cms/blocks/pricing-block.blade.php`

### Block Styling

Blocks are daisyUI-first. Prefer component classes and semantic color utilities in Blade templates:

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

Use Tailwind utilities for layout and spacing. Avoid custom block CSS unless a layout cannot be achieved with daisyUI + utilities.

### Asset Management

Use theme-specific assets in templates:

```blade
{{-- Use @themeVite for compiled assets --}}
@themeVite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Use @themeAsset for static assets --}}
<img src="@themeAsset('images/logo.png')" alt="Logo">

{{-- Or use the helper function --}}
<img src="{{ theme_asset('images/hero-bg.jpg') }}" alt="Hero Background">
```

### Semantic Color Usage

Use daisyUI semantic colors so themes adjust automatically:

```blade
<section class="bg-base-200 text-base-content">
    <h2 class="text-primary">Heading</h2>
    <p class="text-base-content/80">Body text</p>
    <a class="link link-accent">Learn more</a>
</section>
```

## 🛠️ Development Workflow

### Development Mode

For active development with hot reloading:

```bash
cd themes/my-awesome-theme
npm run dev
```

This starts Vite's development server with hot module replacement for rapid iteration.
Make sure dependencies are installed at the project root (`npm install`).

### Production Build

For production-ready assets:

```bash
cd themes/my-awesome-theme
npm run build
```

This creates optimized, hashed assets in the `public/build/` directory.
Make sure dependencies are installed at the project root (`npm install`).

### Theme Commands

```bash
# List available themes
php artisan theme:list
php artisan theme:list --detailed

# Build theme assets
php artisan theme:build my-theme
php artisan theme:build --install    # Install dependencies first

# Activate theme  
php artisan theme:activate my-theme
```

## 🎯 Best Practices

### Design Guidelines

1. **daisyUI-First Approach**: Use component classes; add Tailwind utilities only for layout and spacing
2. **Semantic HTML Structure**: Follow proper heading hierarchy (H1 → H2 → H3 → H4)
3. **Responsive Design**: Design mobile-first, enhance for larger screens
4. **Accessibility**: Ensure proper contrast ratios and keyboard navigation
5. **Performance**: Optimize images and minimize custom CSS

### Development Guidelines

1. **Follow Laravel Conventions**: Use standard directory structure and naming
2. **Theme Inheritance**: Override only what needs to change, inherit the rest
3. **Semantic Colors**: Prefer `bg-base-*`, `text-base-content`, `btn-primary`, etc.
4. **Asset Optimization**: Leverage Vite for efficient asset compilation
5. **Testing**: Test theme in both admin preview and frontend display

### File Organization

```bash
# Keep source files organized
resources/
├── css/
│   ├── app.css              # Main entry point
│   └── components/          # Optional component-specific styles
│       ├── hero.css
│       └── navigation.css
├── js/
│   ├── app.js               # Main entry point
│   └── components/          # Component scripts
└── views/
    ├── layouts/             # Layout overrides
    ├── cms/blocks/          # Block overrides
    └── components/          # Component overrides
```

## 🔧 Advanced Configuration

### Custom Vite Configuration

Extend the default Vite setup for advanced needs:

```javascript
// themes/my-theme/vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            publicDirectory: 'public',
        }),
        tailwindcss(),
    ],
    build: {
        outDir: 'public/build',
    },
});
```

### Theme-Specific JavaScript

Add theme-specific JavaScript functionality:

```javascript
// themes/my-theme/resources/js/app.js
import Alpine from 'alpinejs'

// Theme-specific Alpine components
Alpine.data('heroSlider', () => ({
    currentSlide: 0,
    slides: [],
    
    init() {
        this.slides = this.$refs.slides.children
        setInterval(() => {
            this.nextSlide()
        }, 5000)
    },
    
    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length
    }
}))

// Initialize Alpine
Alpine.start()
```

## 🚨 Troubleshooting

### Common Issues

**Theme not activating:**
- Ensure `theme.json` exists and is valid JSON
- Check that build assets exist (`npm run build`)
- Verify symlinks are created in `public/themes/{slug}/`

**Styles not loading:**
- Check that Vite manifest exists (`public/build/manifest.json`)
- Ensure `@themeVite` directive is used in layout
- Verify CSS files are properly imported in `app.css`

**Template overrides not working:**
- Check file path matches exactly (`cms/blocks/content-block.blade.php`)
- Ensure theme is properly activated
- Clear view cache: `php artisan view:clear`

**Asset compilation errors:**
- Check Node.js version compatibility
- Clear root node_modules and reinstall: `rm -rf node_modules && npm install`
- Verify `resources/css/app.css` includes `@import "tailwindcss";` and `@plugin "daisyui"`

### Debug Commands

```bash
# Check theme status
php artisan theme:list --detailed

# Clear caches
php artisan view:clear
php artisan config:clear

# Check symlinks
ls -la public/themes/

# Verify theme configuration
php artisan tinker
>>> $theme = app('theme.manager')->getActiveTheme()
>>> ['slug' => $theme->slug, 'name' => $theme->name, 'version' => $theme->version]
```

## 📚 Reference

### Helper Functions

```php
// Theme Management
$themeManager = app('theme.manager');
$activeTheme = active_theme();
$themeAssetUrl = theme_asset('images/logo.png');

// daisyUI helpers
$preset = daisyui_preset();
$presets = daisyui_presets();
$supportsController = supports_theme_controller();
```

### Blade Directives

```blade
{{-- Theme assets with automatic fallback --}}
@themeVite(['resources/css/app.css', 'resources/js/app.js'])
@themeAsset('images/logo.png')

{{-- Access theme information --}}
@theme
@theme('version')
@theme('author')
```

### File Structure Reference

```
themes/my-theme/
├── theme.json                       # Required: Theme metadata
├── package.json                     # Required: NPM configuration
├── vite.config.js                   # Required: Build configuration  
├── .gitignore                       # Recommended: Git ignore rules
├── public/                          # Static assets
│   ├── build/                       # Generated: Compiled assets
│   ├── images/                      # Theme images
│   └── fonts/                       # Custom fonts
└── resources/                       # Source files
    ├── css/
    │   └── app.css                  # Required: Main stylesheet
    ├── js/
    │   └── app.js                   # Required: JavaScript entry
    └── views/                       # Optional: Template overrides
        ├── layouts/
        ├── cms/blocks/
        └── components/
```

This guide provides everything you need to create beautiful, functional themes for TallCMS using the modern file-based theme system.
