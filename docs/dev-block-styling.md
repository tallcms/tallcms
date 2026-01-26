---
title: "Block Styling"
slug: "block-styling"
audience: "developer"
category: "developers"
order: 40
prerequisites:
  - "block-development"
---

# Custom Block Styling (daisyUI)

## Overview

TallCMS blocks use **daisyUI component classes** plus Tailwind utilities. This keeps block templates small, semantic, and theme-aware without inline style fallbacks.

## Architecture Principles

### 1. Single Template Philosophy

- **One template per block**: Both `toPreviewHtml()` and `toHtml()` render the same Blade file
- **No duplication**: Avoids preview/frontend template drift
- **Semantic parity**: Admin preview and frontend use identical markup

### 2. daisyUI-First Styling

- **Component classes**: Use `btn`, `card`, `hero`, `collapse`, etc.
- **Semantic colors**: Prefer `bg-base-*`, `text-base-content`, `btn-primary` over hard-coded colors
- **Tailwind utilities**: Use utilities for layout, spacing, and responsiveness
- **Avoid inline styles**: Only use inline styles when a value is truly dynamic

### 3. Predictable Class Scanning

Tailwind v4 scans templates to discover classes. Avoid dynamic class interpolation.

**Bad (not scanned):**
```blade
<a class="btn btn-{{ $style }}">
```

**Good (explicit mapping):**
```blade
<a @class([
    'btn',
    'btn-primary' => ($style ?? 'primary') === 'primary',
    'btn-secondary' => $style === 'secondary',
    'btn-accent' => $style === 'accent',
])>
```

## Block Class Pattern

Each custom block uses the same template for preview and frontend:

```php
class CustomBlock extends RichContentCustomBlock
{
    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.block-name', [
            'variable' => $config['variable'] ?? 'default',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.block-name', [
            'variable' => $config['variable'] ?? '',
        ])->render();
    }
}
```

## Example Templates

### Simple Text Block

```blade
{{-- resources/views/cms/blocks/text-block.blade.php --}}
<section class="py-10">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if($title)
                <h2 class="card-title text-base-content">
                    {{ $title }}
                </h2>
            @endif

            @if($content)
                <div class="prose max-w-none text-base-content/90">
                    {!! $content !!}
                </div>
            @endif
        </div>
    </div>
</section>
```

### Button Variants

```blade
<a @class([
    'btn',
    'btn-primary' => ($variant ?? 'primary') === 'primary',
    'btn-secondary' => $variant === 'secondary',
    'btn-accent' => $variant === 'accent',
    'btn-neutral' => $variant === 'neutral',
    'btn-ghost' => $variant === 'ghost',
    'btn-lg' => ($size ?? 'md') === 'lg',
    'btn-sm' => $size === 'sm',
])>
    {{ $label }}
</a>
```

### Background Options

```blade
<section @class([
    'py-16 px-4',
    'bg-base-100' => ($background ?? 'base-100') === 'base-100',
    'bg-base-200' => $background === 'base-200',
    'bg-base-300' => $background === 'base-300',
    'bg-primary text-primary-content' => $background === 'primary',
    'bg-secondary text-secondary-content' => $background === 'secondary',
])>
    {{-- content --}}
</section>
```

## Benefits

### For Developers

1. **Single source of truth**: One template per block
2. **Theme-friendly**: Semantic classes adapt to any daisyUI theme
3. **Predictable builds**: Explicit class maps keep Tailwind scanning reliable
4. **Clean templates**: Minimal inline styles and custom CSS

### For Theming

1. **Preset or custom**: Works with built-in daisyUI themes or custom themes
2. **Semantic color tokens**: One template adapts to multiple palettes
3. **No inline overrides**: Themes remain in control

## Creating New Blocks

### Using the Command (Recommended)

```bash
php artisan make:tallcms-block MyCustomBlock
```

Generated files include:
- Block class in `app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/`
- Template in `resources/views/cms/blocks/`

### Manual Creation Guidelines

1. **Use a single template** for preview + frontend
2. **Use daisyUI component classes** for UI elements
3. **Map dynamic variants** with `@class([...])`
4. **Use Tailwind utilities** for layout and spacing
5. **Avoid inline styles** unless the value is truly dynamic

## File Locations

```
app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/  # Block classes
resources/views/cms/blocks/                                        # Block templates
```

## Key Differences from Standard Filament Blocks

- Single template for preview + frontend
- daisyUI component classes
- Explicit `@class` mappings for dynamic variants
- No inline styling fallbacks

---

## Next Steps

- [Block development](block-development)
- [Theme development](themes)
- [Rich editor reference](rich-editor)
