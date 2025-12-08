# Custom Block Styling Approach

## Overview

Our CMS uses a **hybrid styling approach** for custom blocks that ensures consistent rendering across both admin preview and frontend contexts while maintaining the flexibility for future theming systems.

## Architecture Principles

### 1. Single Template Philosophy
- **One template per block**: Both `toPreviewHtml()` and `toHtml()` methods use the same Blade template
- **No duplication**: Eliminates the need to maintain separate preview and frontend templates
- **Perfect accuracy**: Admin previews show exactly what the frontend will render

### 2. Hybrid Styling Strategy
Each element uses both Tailwind CSS classes and inline styles:

```blade
<div class="bg-gray-50 py-16 px-6" 
     style="background-color: #f9fafb; padding: 4rem 1.5rem;">
```

**Why both?**
- **Tailwind classes**: Primary styling for environments with full Tailwind CSS support
- **Inline styles**: Fallback styling ensuring consistent rendering in any context
- **Future-proof**: Theming systems can override either approach

### 3. TALL Stack Native
- **Tailwind CSS**: All styling uses native Tailwind utility classes
- **Alpine.js**: Interactive components use Alpine.js directives when needed
- **Vanilla JavaScript**: Complex interactions (like lightboxes) use clean vanilla JS
- **Laravel Blade**: Template logic handled through Blade directives

## Implementation Pattern

### Basic Structure
```blade
<div class="tailwind-classes" style="fallback-css-properties;">
    @if($variable)
        <h2 class="text-classes" style="fallback-typography;">
            {{ $variable }}
        </h2>
    @endif
</div>
```

### Color Variations
For components with multiple styles (like buttons):

```php
@php
    $styleClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
        // ...
    ];
    
    $styleInlines = [
        'primary' => 'background-color: #2563eb; color: white;',
        'secondary' => 'background-color: #4b5563; color: white;',
        // ...
    ];
    
    $buttonClass = $styleClasses[$style] ?? $styleClasses['primary'];
    $buttonStyle = $styleInlines[$style] ?? $styleInlines['primary'];
@endphp
```

## File Structure

```
resources/views/cms/blocks/
├── hero.blade.php              # Hero section with background images
├── call-to-action.blade.php    # CTA with multiple button styles
└── image-gallery.blade.php     # Gallery with layouts & lightbox
```

## Block Class Pattern

Each custom block follows this structure:

```php
class CustomBlock extends RichContentCustomBlock
{
    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.block-name', [
            'variable' => $config['variable'] ?? 'default',
            // ... all variables
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.block-name', [
            'variable' => $config['variable'] ?? '',
            // ... same template, different defaults
        ])->render();
    }
}
```

## Benefits

### For Developers
1. **Single source of truth**: One template to maintain per block
2. **Predictable styling**: Inline styles ensure consistent rendering
3. **Responsive design**: Tailwind classes handle breakpoints
4. **Clean codebase**: No duplicate template logic

### For Content Creators
1. **Accurate previews**: Admin interface shows exact frontend appearance
2. **Immediate feedback**: Changes are visible instantly in preview
3. **Professional output**: Consistent styling across all contexts

### For Future Theming
1. **Override flexibility**: Themes can target classes or inline styles
2. **Clean foundation**: Pure Tailwind makes customization straightforward
3. **Backward compatibility**: Inline styles provide safe fallbacks
4. **Template replacement**: Complete template override possible

## Adding New Blocks

### Using the TallCMS Command (Recommended)

Use our custom Artisan command to generate blocks following the TallCMS pattern:

```bash
php artisan make:tallcms-block MyCustomBlock
```

This will create:
- `app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/MyCustomBlockBlock.php`
- `resources/views/cms/blocks/my-custom-block.blade.php`

The generated files include:
- ✅ Hybrid styling approach already implemented
- ✅ Proper class structure with slideOver modal
- ✅ Both `toPreviewHtml()` and `toHtml()` using same template
- ✅ Inline documentation and customization guide

### Manual Creation

When creating new custom blocks manually:

1. **Create single template** in `resources/views/cms/blocks/`
2. **Use hybrid styling** with both classes and inline styles
3. **Follow TALL stack** principles for interactivity
4. **Maintain consistency** with existing block patterns
5. **Test both contexts** - admin preview and frontend rendering

### Auto-Discovery (No Registration Required!)

TallCMS automatically discovers and registers all custom blocks! Simply create your block using the command above, and it will be immediately available in the rich editor.

**How it works:**
- `CustomBlockDiscoveryService` scans the custom blocks directory
- Automatically finds all classes extending `RichContentCustomBlock`
- Registers them in all RichEditor instances
- Caches results for performance

**No manual registration needed!** ✨

## Example: Simple Text Block

```blade
{{-- resources/views/cms/blocks/text-block.blade.php --}}
<div class="py-8 px-4 max-w-4xl mx-auto" 
     style="padding: 2rem 1rem; max-width: 56rem; margin: 0 auto;">
    @if($title)
        <h2 class="text-2xl font-bold text-gray-900 mb-4" 
            style="font-size: 1.5rem; font-weight: bold; color: #111827; margin-bottom: 1rem;">
            {{ $title }}
        </h2>
    @endif
    
    @if($content)
        <div class="prose prose-lg text-gray-700" 
             style="line-height: 1.75; color: #374151;">
            {!! $content !!}
        </div>
    @endif
</div>
```

## Color Reference

Common colors used in inline styles:
- **Gray-50**: `#f9fafb` (backgrounds)
- **Gray-600**: `#4b5563` (secondary text)
- **Gray-900**: `#111827` (primary text)
- **Blue-600**: `#2563eb` (primary buttons)
- **White**: `#ffffff` (button text, overlays)

This approach ensures consistent, beautiful rendering across all contexts while maintaining the flexibility needed for future enhancements.

## Quick Reference

### Commands
```bash
# Create a new TallCMS block
php artisan make:tallcms-block BlockName

# Old Filament command (creates unused files)
php artisan make:filament-rich-content-custom-block BlockName
```

### File Locations
```
app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/  # Block classes
resources/views/cms/blocks/                                        # Block templates
```

### Key Differences from Standard Filament Blocks
- ❌ **No separate preview templates** - Uses single template for both contexts
- ✅ **Hybrid styling** - Tailwind classes + inline styles for compatibility
- ✅ **slideOver modals** - Better UX for block configuration
- ✅ **Consistent patterns** - All blocks follow same structure

### Benefits Over Default Filament Approach
1. **No file duplication** - Single template maintained
2. **Perfect previews** - Inline styles ensure admin previews always render correctly
3. **Future-ready** - Easy to extend with theming systems
4. **Developer-friendly** - Clear patterns and automated generation