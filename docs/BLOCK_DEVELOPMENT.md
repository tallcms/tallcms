# Block Development Guide

This guide covers creating custom blocks for the TallCMS Rich Editor. Blocks are reusable content components that content editors can insert into pages and posts.

## Quick Start

Generate a new block using the Artisan command:

```bash
php artisan make:tallcms-block "product showcase"
```

This creates:
- **Block class**: `app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/ProductShowcaseBlock.php`
- **Template**: `resources/views/cms/blocks/product-showcase.blade.php`

The block is auto-discovered and immediately available in the Rich Editor.

## Block Architecture

### File Structure

```
app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/
└── MyCustomBlock.php           # Block class with form schema and rendering

resources/views/cms/blocks/
└── my-custom.blade.php         # Blade template for frontend display

resources/css/blocks.css        # Block styles (shared admin/frontend)
```

### Block Class Anatomy

```php
<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class ProductShowcaseBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;

    // Required: Unique identifier for this block
    public static function getId(): string
    {
        return 'product_showcase';
    }

    // Required: Display name in the block panel
    public static function getLabel(): string
    {
        return 'Product Showcase';
    }

    // Block panel metadata (from HasBlockMetadata trait)
    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getDescription(): string
    {
        return 'Showcase a product with image and details';
    }

    public static function getKeywords(): array
    {
        return ['product', 'showcase', 'feature', 'highlight'];
    }

    // Required: Configure the editor modal form
    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the product showcase')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(500),
            ])->slideOver();
    }

    // Required: Render preview in admin editor
    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.product-showcase', [
            'title' => $config['title'] ?? 'Sample Product',
            'description' => $config['description'] ?? 'Product description',
        ])->render();
    }

    // Required: Render on frontend
    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.product-showcase', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}
```

## Block Metadata (HasBlockMetadata Trait)

The `HasBlockMetadata` trait enables your block to appear properly in the enhanced block panel with search, categories, and icons.

### Available Methods

| Method | Return Type | Default | Description |
|--------|-------------|---------|-------------|
| `getCategory()` | `string` | `'content'` | Category for grouping in panel |
| `getIcon()` | `string` | `'heroicon-o-cube'` | Heroicon name for display |
| `getDescription()` | `string` | `''` | Brief description for search |
| `getKeywords()` | `array` | `[]` | Additional search terms |
| `getSortPriority()` | `int` | `50` | Sort order within category (lower = first) |

### Categories

| Key | Label | Use For |
|-----|-------|---------|
| `content` | Content | Text, headings, CTAs, pricing, features |
| `media` | Media | Images, videos, galleries, parallax |
| `social-proof` | Social Proof | Testimonials, team, logos, stats |
| `dynamic` | Dynamic | Posts, FAQ, data-driven content |
| `forms` | Forms | Contact forms, input blocks |
| `other` | Other | Fallback for uncategorized blocks |

### Icon Reference

Use any [Heroicon](https://heroicons.com/) name. Common choices:

```php
// Content
'heroicon-o-document-text'    // Articles, content
'heroicon-o-megaphone'        // Announcements, CTAs
'heroicon-o-currency-dollar'  // Pricing
'heroicon-o-sparkles'         // Features

// Media
'heroicon-o-photo'            // Images, galleries
'heroicon-o-play-circle'      // Videos
'heroicon-o-arrows-up-down'   // Parallax

// Social Proof
'heroicon-o-chat-bubble-left-right'  // Testimonials
'heroicon-o-user-group'              // Team
'heroicon-o-building-office'         // Logos, clients
'heroicon-o-chart-bar'               // Stats

// Dynamic
'heroicon-o-newspaper'               // Posts, articles
'heroicon-o-question-mark-circle'    // FAQ
'heroicon-o-clock'                   // Timeline

// Forms
'heroicon-o-envelope'         // Contact forms
```

## Form Schema

### Basic Components

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;

public static function configureEditorAction(Action $action): Action
{
    return $action
        ->modalDescription('Configure your block')
        ->modalWidth('4xl')  // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
        ->schema([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->placeholder('Enter title'),

            Textarea::make('description')
                ->maxLength(500)
                ->rows(3),

            Select::make('style')
                ->options([
                    'default' => 'Default',
                    'highlight' => 'Highlighted',
                    'minimal' => 'Minimal',
                ])
                ->default('default'),

            Toggle::make('show_button')
                ->label('Show CTA Button')
                ->default(true),

            FileUpload::make('image')
                ->image()
                ->disk(cms_media_disk())
                ->directory('blocks/my-block')
                ->visibility(cms_media_visibility()),
        ])->slideOver();
}
```

### Organized with Tabs

```php
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;

public static function configureEditorAction(Action $action): Action
{
    return $action
        ->modalWidth('6xl')
        ->schema([
            Tabs::make('Block Configuration')
                ->tabs([
                    Tab::make('Content')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            TextInput::make('heading'),
                            Textarea::make('body'),
                        ]),

                    Tab::make('Media')
                        ->icon('heroicon-m-photo')
                        ->schema([
                            FileUpload::make('image')->image(),
                        ]),

                    Tab::make('Styling')
                        ->icon('heroicon-m-paint-brush')
                        ->schema([
                            Section::make('Colors')
                                ->schema([
                                    Select::make('background'),
                                    Select::make('text_color'),
                                ])
                                ->columns(2),
                        ]),
                ]),
        ])->slideOver();
}
```

### Repeater for Multiple Items

```php
use Filament\Forms\Components\Repeater;

Repeater::make('items')
    ->schema([
        TextInput::make('title')->required(),
        Textarea::make('description'),
        FileUpload::make('icon')->image(),
    ])
    ->defaultItems(3)
    ->minItems(1)
    ->maxItems(6)
    ->collapsible()
    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Item')
    ->reorderableWithButtons()
```

### Conditional Fields

```php
use Filament\Schemas\Components\Utilities\Get;

Select::make('link_type')
    ->options([
        'page' => 'Internal Page',
        'external' => 'External URL',
    ])
    ->default('page')
    ->live(),  // Required for reactivity

Select::make('page_id')
    ->label('Select Page')
    ->options(CmsPage::pluck('title', 'id'))
    ->visible(fn (Get $get): bool => $get('link_type') === 'page'),

TextInput::make('external_url')
    ->label('URL')
    ->url()
    ->visible(fn (Get $get): bool => $get('link_type') === 'external'),
```

## Template Development

### Basic Template

```blade
{{-- resources/views/cms/blocks/product-showcase.blade.php --}}

@php
    $textPresets = theme_text_presets();
    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];

    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
    ])->join('; ') . ';';
@endphp

<section class="product-showcase-block py-12 px-4" style="{{ $customProperties }}">
    <div class="max-w-4xl mx-auto">
        @if($title)
            <h2 class="text-3xl font-bold mb-4" style="color: var(--block-heading-color)">
                {{ $title }}
            </h2>
        @endif

        @if($description)
            <p class="text-lg" style="color: var(--block-text-color)">
                {{ $description }}
            </p>
        @endif
    </div>
</section>
```

### Theme Integration

TallCMS uses CSS custom properties for theme integration. This ensures blocks look consistent across different themes.

```php
// Available theme helpers
$textPresets = theme_text_presets();      // Text color presets
$buttonPresets = theme_button_presets();  // Button styles
$colorPalette = theme_color_palette();    // Full color palette
$paddingPresets = theme_padding_presets(); // Spacing presets
```

### DaisyUI Integration

Core blocks use DaisyUI classes for consistent styling:

```blade
{{-- Button with DaisyUI classes --}}
<a href="{{ $url }}" class="btn btn-primary btn-lg">
    {{ $button_text }}
</a>

{{-- Card component --}}
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">{{ $title }}</h2>
        <p>{{ $description }}</p>
    </div>
</div>
```

### Image Handling

```blade
@if($image)
    <img
        src="{{ Storage::disk(cms_media_disk())->url($image) }}"
        alt="{{ $title }}"
        class="w-full h-auto rounded-lg"
        loading="lazy"
    >
@endif
```

## Block Styling

### CSS Architecture

Block styles are defined in `resources/css/blocks.css` and shared between admin preview and frontend.

```css
/* resources/css/blocks.css */

.product-showcase-block {
    /* Use CSS custom properties for theme integration */
    color: var(--block-text-color, #374151);
}

.product-showcase-block h2 {
    color: var(--block-heading-color, #111827);
}

/* Responsive design */
@media (max-width: 768px) {
    .product-showcase-block {
        padding: 2rem 1rem;
    }
}
```

### Build Process

After adding styles, rebuild assets:

```bash
npm run build
```

For development with hot reload:

```bash
npm run dev
```

## Advanced Patterns

### Preview vs Frontend Rendering

```php
public static function toPreviewHtml(array $config): string
{
    // Provide sample data for empty fields in preview
    return static::renderBlock(array_merge($config, [
        'title' => $config['title'] ?? 'Sample Title',
        'items' => $config['items'] ?? self::getSampleItems(),
    ]), isPreview: true);
}

public static function toHtml(array $config, array $data): string
{
    // Frontend uses actual data, no samples
    return static::renderBlock($config, isPreview: false);
}

protected static function renderBlock(array $config, bool $isPreview = false): string
{
    return view('cms.blocks.my-block', [
        'isPreview' => $isPreview,
        'title' => $config['title'] ?? '',
        // ... other variables
    ])->render();
}
```

### Link Resolution

Use the `BlockLinkResolver` service for handling page/external links:

```php
use TallCms\Cms\Services\BlockLinkResolver;

protected static function renderBlock(array $config): string
{
    $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
    // Handles button_link_type, button_page_id, button_url fields

    return view('cms.blocks.my-block', [
        'button_url' => $buttonUrl,
        // ...
    ])->render();
}
```

### Shared Options (HasDaisyUIOptions)

Core blocks use the `HasDaisyUIOptions` trait for consistent styling options:

```php
use TallCms\Cms\Filament\Blocks\Concerns\HasDaisyUIOptions;

class MyBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;
    use HasDaisyUIOptions;

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            Select::make('background')
                ->options(static::getBackgroundOptions())
                ->default('bg-base-100'),

            Select::make('text_alignment')
                ->options(static::getTextAlignmentOptions())
                ->default('text-center'),

            Select::make('padding')
                ->options(static::getPaddingOptions())
                ->default('py-16'),

            Select::make('button_variant')
                ->options(static::getButtonVariantOptions())
                ->default('btn-primary'),
        ]);
    }
}
```

## Testing Blocks

### Manual Testing Checklist

1. **Admin Preview**: Insert block in editor, verify preview renders correctly
2. **Form Validation**: Test required fields, max lengths, file uploads
3. **Frontend Display**: Publish page, verify block renders on frontend
4. **Responsive Design**: Test on mobile, tablet, desktop breakpoints
5. **Dark Mode**: Verify styling works in both light and dark modes
6. **Theme Consistency**: Check colors match theme settings

### Common Issues

| Issue | Solution |
|-------|----------|
| Block not appearing | Verify class extends `RichContentCustomBlock` and has `getId()` |
| Preview not updating | Check `toPreviewHtml()` returns valid HTML |
| Styles not applying | Run `npm run build`, check class names match CSS |
| Images not loading | Verify `cms_media_disk()` and file path |

## Core Blocks Reference

TallCMS includes 16 built-in blocks:

### Content Blocks
| Block | ID | Description |
|-------|----|-------------|
| Hero | `hero` | Full-width hero section with background |
| Content | `content_block` | Rich text content section |
| Call to Action | `call_to_action` | Promotional section with buttons |
| Features | `features` | Feature grid with icons |
| Pricing | `pricing` | Pricing tables with plans |
| Divider | `divider` | Decorative spacing element |

### Media Blocks
| Block | ID | Description |
|-------|----|-------------|
| Image Gallery | `image_gallery` | Gallery with lightbox |
| Parallax | `parallax` | Parallax scrolling section |

### Social Proof Blocks
| Block | ID | Description |
|-------|----|-------------|
| Testimonials | `testimonials` | Customer testimonials |
| Team | `team` | Team member profiles |
| Logos | `logos` | Client/partner logos |
| Stats | `stats` | Key metrics display |

### Dynamic Blocks
| Block | ID | Description |
|-------|----|-------------|
| Posts | `posts` | Blog post listing |
| FAQ | `faq` | Accordion FAQ section |
| Timeline | `timeline` | Chronological events |

### Form Blocks
| Block | ID | Description |
|-------|----|-------------|
| Contact Form | `contact_form` | Contact form with fields |

## Plugin Blocks

Plugin developers can create blocks that integrate with TallCMS:

```php
// plugins/vendor/my-plugin/src/Blocks/CustomBlock.php

namespace Vendor\MyPlugin\Blocks;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class CustomBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;

    public static function getCategory(): string
    {
        return 'other';  // Or create a custom category
    }

    // ... rest of implementation
}
```

Plugin blocks are auto-discovered and appear in the "Other" category by default.

## Related Documentation

- [CMS Rich Editor](CMS_RICH_EDITOR.md) - Enhanced block panel features
- [Theme Development](../themes/README.md) - Theme system integration
