# Custom Block Styling (daisyUI)

## Overview

TallCMS blocks now use **daisyUI component classes** plus Tailwind utilities. This keeps block templates small, semantic, and theme-aware without inline style fallbacks.

## Architecture Principles

### 1. Single Template Philosophy
- **One template per block**: Both `toPreviewHtml()` and `toHtml()` render the same Blade file
- **No duplication**: Avoids preview/frontend template drift
- **Semantic parity**: Admin preview and frontend use identical markup

### 2. daisyUI-First Styling
- **Component classes**: Use `btn`, `card`, `hero`, `collapse`, etc.
- **Semantic colors**: Prefer `bg-base-*`, `text-base-content`, `btn-primary` over hard-coded colors
- **Tailwind utilities**: Use utilities for layout, spacing, and responsiveness
- **Avoid inline styles**: Only use inline styles when a value is truly dynamic (e.g., user-defined image URL)

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
    'btn-neutral' => $style === 'neutral',
    'btn-ghost' => $style === 'ghost',
])>
```

## Admin Preview Behavior

- Admin preview loads daisyUI styles for blocks only
- Preview focuses on **semantic consistency** (structure/layout) rather than pixel-perfect theme matching
- Colors may differ from the active frontend theme depending on preview configuration

## File Structure

```
resources/views/cms/blocks/
├── hero.blade.php
├── call-to-action.blade.php
└── image-gallery.blade.php
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

## Benefits

### For Developers
1. **Single source of truth**: One template per block
2. **Theme-friendly**: Semantic classes adapt to any daisyUI theme
3. **Predictable builds**: Explicit class maps keep Tailwind scanning reliable
4. **Clean templates**: Minimal inline styles and custom CSS

### For Content Creators
1. **Consistent previews**: Admin preview matches frontend structure
2. **Immediate feedback**: Changes appear instantly in preview
3. **Stable design system**: Blocks look consistent across pages

### For Theming
1. **Preset or custom**: Works with built-in daisyUI themes or custom themes
2. **Semantic color tokens**: One template adapts to multiple palettes
3. **No inline overrides**: Themes remain in control

## Adding New Blocks

### Using the TallCMS Command (Recommended)

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

## Example: Simple Text Block

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

## Quick Reference

### Commands
```bash
# Create a new TallCMS block
php artisan make:tallcms-block BlockName
```

### File Locations
```
app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/  # Block classes
resources/views/cms/blocks/                                        # Block templates
```

### Key Differences from Standard Filament Blocks
- ✅ Single template for preview + frontend
- ✅ daisyUI component classes
- ✅ Explicit `@class` mappings for dynamic variants
- ✅ No inline styling fallbacks

If you need a custom component style that daisyUI does not provide, use Tailwind utilities directly.
