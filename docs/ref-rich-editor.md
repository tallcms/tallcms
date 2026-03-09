---
title: "Rich Editor Reference"
slug: "rich-editor"
audience: "all"
category: "reference"
order: 40
---

# CMS Rich Editor Reference

The CMS Rich Editor is an enhanced version of Filament's `RichEditor` component with improved block panel, search, category grouping, and icon display.

## Features

- **Search**: Instant client-side filtering by block name, description, and keywords
- **Categories**: Blocks organized into collapsible panels
- **Icons**: Each block displays its icon
- **Media Library Browser**: Insert existing images without re-uploading
- **Auto Media Saving**: Attached files are saved to the Media Library
- **Dark Mode**: Full support via Filament-native styling
- **Backwards Compatible**: Works with existing blocks and plugin blocks

---

## Block Categories

TallCMS includes 16 custom blocks in 5 categories:

| Category | Blocks | Icon |
|----------|--------|------|
| **Content** | Hero, Content, CTA, Features, Pricing, Divider | `heroicon-o-document-text` |
| **Media** | Media Gallery, Document List, Parallax | `heroicon-o-photo` |
| **Social Proof** | Testimonials, Team, Logos, Stats | `heroicon-o-star` |
| **Dynamic** | Posts, FAQ, Timeline | `heroicon-o-newspaper` |
| **Forms** | Contact Form | `heroicon-o-envelope` |

---

## Content Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **HeroBlock** | Full-width hero section with background | banner, header, landing |
| **ContentBlock** | Rich text content with title | article, text, prose |
| **CallToActionBlock** | Promotional section with buttons | cta, button, action |
| **FeaturesBlock** | Feature grid with icons | features, benefits, list |
| **PricingBlock** | Pricing table with plans | plans, pricing, tiers |
| **DividerBlock** | Decorative spacing element | separator, spacing, line |

## Media Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **ImageGalleryBlock** | Gallery with lightbox support | images, photos, gallery |
| **ParallaxBlock** | Parallax scrolling section | scroll, background, effect |

## Social Proof Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **TestimonialsBlock** | Customer testimonials | reviews, quotes, customers |
| **TeamBlock** | Team member profiles | team, staff, members |
| **LogosBlock** | Client/partner logos | clients, partners, brands |
| **StatsBlock** | Key metrics display | numbers, metrics, statistics |

## Dynamic Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **PostsBlock** | Blog post listing | blog, articles, posts |
| **FaqBlock** | FAQ accordion | faq, questions, answers |
| **TimelineBlock** | Chronological events | history, events, chronology |

## Form Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **ContactFormBlock** | Contact form | contact, email, form |

---

## Adding Metadata to Custom Blocks

Use the `HasBlockMetadata` trait:

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

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getDescription(): string
    {
        return 'A custom block that does something amazing';
    }

    public static function getKeywords(): array
    {
        return ['custom', 'amazing', 'special'];
    }

    public static function getSortPriority(): int
    {
        return 25;  // Lower = appears first
    }
}
```

---

## Trait Methods

| Method | Return Type | Default | Description |
|--------|-------------|---------|-------------|
| `getCategory()` | `string` | `'content'` | Category key |
| `getIcon()` | `string` | `'heroicon-o-cube'` | Heroicon name |
| `getDescription()` | `string` | `''` | Brief description |
| `getKeywords()` | `array` | `[]` | Search terms |
| `getSortPriority()` | `int` | `50` | Sort order |

---

## Available Categories

| Key | Label | Use For |
|-----|-------|---------|
| `content` | Content | Text, headings, CTAs, pricing |
| `media` | Media | Images, videos, galleries |
| `social-proof` | Social Proof | Testimonials, team, logos |
| `dynamic` | Dynamic | Posts, FAQ, data-driven |
| `forms` | Forms | Contact forms, inputs |
| `other` | Other | Uncategorized blocks |

---

## Plugin Blocks

Plugin blocks are automatically discovered. Without `HasBlockMetadata`:
- Appear in "Other" category
- Use default cube icon
- Searchable by label and ID

---

## Adding a Custom Category

Edit `BlockCategoryRegistry.php`:

```php
public static function getCategories(): array
{
    return [
        // ... existing categories
        'ecommerce' => [
            'label' => 'E-Commerce',
            'icon' => 'heroicon-o-shopping-cart',
            'order' => 45,
        ],
    ];
}
```

---

## Media Library Integration

The editor integrates with the [Media Library](media) via two features:

### Insert from Media Library

Click the **photo** icon in the toolbar to open a searchable browser of existing images. Select an image, optionally edit the alt text, and insert it directly into the editor.

- Images are inserted with a reference to the Media Library record ID
- Alt text pre-fills from the media record but can be overridden
- The browser shows the 100 most recent images with client-side search

### File Attachments Saved to Media Library

When you attach a file using the **paperclip** icon, the file is automatically saved as a Media Library record:

- Alt text is inferred from the filename (e.g., `team-photo.jpg` → "Team Photo")
- Image optimization runs in the background
- The image node stores the media record ID, so URLs resolve from the Media Library at render time

### Architecture

The integration is implemented as a Filament `RichContentPlugin`:

```
src/Filament/Forms/Components/
├── Actions/
│   └── InsertMediaAction.php         # Modal action for browsing media
├── Plugins/
│   └── MediaLibraryPlugin.php        # Registers toolbar button and action
├── CmsRichEditor.php                 # Registers plugin, adds toolbar button
└── MediaLibraryFileAttachmentProvider.php  # Saves attachments as media records
```

| Class | Role |
|-------|------|
| `MediaLibraryPlugin` | Implements `RichContentPlugin`, provides the `insertMedia` tool and action |
| `InsertMediaAction` | Filament `Action` with modal schema, queries `TallcmsMedia`, runs `insertContent` editor command |
| `MediaLibraryFileAttachmentProvider` | Saves uploaded files as `TallcmsMedia` records, resolves media IDs to URLs |

---

## Using CmsRichEditor

The `CmsRichEditor` is automatically used in page and post forms. For custom resources:

```php
use TallCms\Cms\Filament\Forms\Components\CmsRichEditor;
use TallCms\Cms\Services\CustomBlockDiscoveryService;

CmsRichEditor::make('content')
    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
    ->activePanel('customBlocks')
```

---

## Architecture

```
packages/tallcms/cms/
├── src/
│   ├── Filament/
│   │   ├── Blocks/
│   │   │   └── Concerns/
│   │   │       └── HasBlockMetadata.php
│   │   └── Forms/
│   │       └── Components/
│   │           ├── Actions/
│   │           │   └── InsertMediaAction.php
│   │           ├── Plugins/
│   │           │   └── MediaLibraryPlugin.php
│   │           ├── CmsRichEditor.php
│   │           └── MediaLibraryFileAttachmentProvider.php
│   └── Services/
│       ├── BlockCategoryRegistry.php
│       └── CustomBlockDiscoveryService.php
└── resources/
    └── views/
        └── filament/
            └── forms/
                └── components/
                    ├── cms-rich-editor.blade.php
                    └── media-library-picker.blade.php
```

---

## Key Classes

### CmsRichEditor

Extends Filament's `RichEditor`:
- `getGroupedBlocks()` — Returns blocks by category
- `getBlockCategories()` — Returns category definitions
- `getDefaultToolbarButtons()` — Adds `insertMedia` after `attachFiles`
- `isFilamentCompatible()` — Version check for fallback

### BlockCategoryRegistry

Static registry:
- Category keys, labels, icons, sort order
- Fallback icon constant

### CustomBlockDiscoveryService

Enhanced discovery:
- `getBlocksWithMetadata()` - Full metadata
- `getBlocksGroupedByCategory()` - Grouped output
- Icon validation
- Precomputed search strings

---

## Filament Compatibility

Requires Filament v5.x. Automatic version detection:
- **Filament 5.x**: Full enhanced panel
- **Other versions**: Standard Filament block panel

---

## Next Steps

- [Block development](block-development)
- [Block styling](block-styling)
- [Using blocks](blocks)
