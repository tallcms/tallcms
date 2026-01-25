# CMS Rich Editor

The CMS Rich Editor is an enhanced version of Filament's `RichEditor` component, specifically designed for TallCMS Page and Post content editors. It features an improved block panel with search, category grouping, and icon display.

## Features

- **Search**: Instant client-side filtering by block name, description, and keywords
- **Categories**: Blocks organized into collapsible panels for easy discovery
- **Icons**: Each block displays its icon for quick visual identification
- **Dark Mode**: Full support via Filament-native styling
- **Backwards Compatible**: Works with existing blocks and plugin blocks

## Block Categories

TallCMS includes 16 custom blocks organized into 5 categories:

| Category | Blocks | Icon |
|----------|--------|------|
| **Content** | Hero, Content, CTA, Features, Pricing, Divider | `heroicon-o-document-text` |
| **Media** | Image Gallery, Parallax | `heroicon-o-photo` |
| **Social Proof** | Testimonials, Team, Logos, Stats | `heroicon-o-star` |
| **Dynamic** | Posts, FAQ, Timeline | `heroicon-o-newspaper` |
| **Forms** | Contact Form | `heroicon-o-envelope` |

### Content Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **HeroBlock** | Full-width hero section with background image | banner, header, landing |
| **ContentBlock** | Rich text content with title and body | article, text, prose |
| **CallToActionBlock** | Promotional section with action buttons | cta, button, action |
| **FeaturesBlock** | Feature grid with icons and descriptions | features, benefits, list |
| **PricingBlock** | Pricing table with plans and features | plans, pricing, tiers |
| **DividerBlock** | Decorative spacing or line separator | separator, spacing, line |

### Media Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **ImageGalleryBlock** | Image gallery with lightbox support | images, photos, gallery |
| **ParallaxBlock** | Full-width parallax scrolling section | scroll, background, effect |

### Social Proof Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **TestimonialsBlock** | Customer testimonials and reviews | reviews, quotes, customers |
| **TeamBlock** | Team member profiles with photos | team, staff, members |
| **LogosBlock** | Client or partner logo showcase | clients, partners, brands |
| **StatsBlock** | Key metrics and statistics display | numbers, metrics, statistics |

### Dynamic Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **PostsBlock** | Display blog posts and articles | blog, articles, posts |
| **FaqBlock** | Frequently asked questions accordion | faq, questions, answers |
| **TimelineBlock** | Chronological events or milestones | history, events, chronology |

### Form Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **ContactFormBlock** | Contact form with customizable fields | contact, email, form |

## Adding Metadata to Custom Blocks

To integrate your custom block with the enhanced panel, use the `HasBlockMetadata` trait:

```php
<?php

namespace App\Filament\Blocks;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class MyCustomBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;

    public static function getId(): string
    {
        return 'my-custom-block';
    }

    public static function getLabel(): string
    {
        return 'My Custom Block';
    }

    // Category: content, media, social-proof, dynamic, forms, other
    public static function getCategory(): string
    {
        return 'content';
    }

    // Any valid Heroicon name
    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    // Brief description for search
    public static function getDescription(): string
    {
        return 'A custom block that does something amazing';
    }

    // Additional search terms
    public static function getKeywords(): array
    {
        return ['custom', 'amazing', 'special'];
    }

    // Lower numbers appear first within category (default: 50)
    public static function getSortPriority(): int
    {
        return 25;
    }

    // ... rest of block implementation
}
```

### Trait Methods

| Method | Return Type | Default | Description |
|--------|-------------|---------|-------------|
| `getCategory()` | `string` | `'content'` | Category key for grouping |
| `getIcon()` | `string` | `'heroicon-o-cube'` | Heroicon name for display |
| `getDescription()` | `string` | `''` | Brief description for search |
| `getKeywords()` | `array` | `[]` | Additional search terms |
| `getSortPriority()` | `int` | `50` | Sort order within category |

### Available Categories

| Key | Label | Use For |
|-----|-------|---------|
| `content` | Content | Text, headings, CTAs, pricing |
| `media` | Media | Images, videos, galleries |
| `social-proof` | Social Proof | Testimonials, team, logos, stats |
| `dynamic` | Dynamic | Posts, FAQ, data-driven content |
| `forms` | Forms | Contact forms, input blocks |
| `other` | Other | Fallback for uncategorized blocks |

## Plugin Blocks

Plugin blocks are automatically discovered and displayed in the block panel. If a plugin block doesn't implement `HasBlockMetadata`, it will:

- Appear in the "Other" category
- Use the default cube icon (`heroicon-o-cube`)
- Be searchable by its label and ID

To enhance plugin blocks, simply add the `HasBlockMetadata` trait and implement the metadata methods.

## Architecture

```
packages/tallcms/cms/
├── src/
│   ├── Filament/
│   │   ├── Blocks/
│   │   │   └── Concerns/
│   │   │       └── HasBlockMetadata.php       # Trait for block metadata
│   │   └── Forms/
│   │       └── Components/
│   │           └── CmsRichEditor.php          # Enhanced RichEditor component
│   └── Services/
│       ├── BlockCategoryRegistry.php          # Category definitions
│       └── CustomBlockDiscoveryService.php    # Block discovery with metadata
└── resources/
    └── views/
        └── filament/
            └── forms/
                └── components/
                    └── cms-rich-editor.blade.php  # Enhanced block panel view
```

### Key Classes

#### `CmsRichEditor`

Extends Filament's `RichEditor` with:
- Custom view for enhanced block panel
- `getGroupedBlocks()` - Returns blocks organized by category
- `getBlockCategories()` - Returns category definitions
- `isFilamentCompatible()` - Version check for graceful fallback

#### `BlockCategoryRegistry`

Static registry defining:
- Category keys, labels, icons, and sort order
- Fallback icon constant (`FALLBACK_ICON`)

#### `CustomBlockDiscoveryService`

Enhanced discovery service with:
- `getBlocksWithMetadata()` - Returns blocks with full metadata
- `getBlocksGroupedByCategory()` - Returns blocks organized by category
- Icon validation via `svg()` helper
- Precomputed search strings for performance

## Customization

### Adding a New Category

Edit `BlockCategoryRegistry.php`:

```php
public static function getCategories(): array
{
    return [
        // ... existing categories
        'ecommerce' => [
            'label' => 'E-Commerce',
            'icon' => 'heroicon-o-shopping-cart',
            'order' => 45, // Between dynamic (40) and forms (50)
        ],
    ];
}
```

### Using CmsRichEditor in Custom Resources

The `CmsRichEditor` is automatically used in `CmsPageForm` and `CmsPostForm`. To use it in your own resources:

```php
use TallCms\Cms\Filament\Forms\Components\CmsRichEditor;
use TallCms\Cms\Services\CustomBlockDiscoveryService;

CmsRichEditor::make('content')
    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
    ->activePanel('customBlocks')
    // ... other configuration
```

## Filament Compatibility

The enhanced block panel requires Filament v4.x. The component includes automatic version detection:

- **Filament 4.x**: Full enhanced panel with search, categories, and icons
- **Other versions**: Graceful fallback to standard Filament block panel

Version check uses `Composer\InstalledVersions` with proper error handling.
