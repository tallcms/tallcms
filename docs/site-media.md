---
title: "Media Library"
slug: "media"
audience: "site-owner"
category: "site-management"
order: 30
---

# Media Library

> **What you'll learn:** How to upload, organize, and use images and files in TallCMS.

---

## Overview

The Media Library stores all images and files uploaded to your site. Access it via **Admin > Content > Media Library**.

Key features:
- **Collections**: Organize media into reusable groups
- **Image Optimization**: Automatic WebP conversion for faster loading
- **Bulk Operations**: Upload multiple files and edit in bulk
- **Smart Filters**: Find media by type, collection, or status

---

## Uploading Files

### Single or Bulk Upload

1. Go to **Admin > Content > Media Library**
2. Click **New tallcms media** in the top-right
3. Drag and drop files or click to browse
4. Select one or more **Collections** to organize the files
5. Click **Create** to upload

All uploaded files are automatically optimized in the background.

### From Content Editor

When editing a page or post:

1. Add a block that accepts images (Hero, Gallery, etc.)
2. Click the image upload field
3. Choose **Upload** to add a new file, or select from existing media

### Supported File Types

| Type | Extensions |
|------|------------|
| Images | JPG, PNG, GIF, WebP, SVG |
| Video | MP4, WebM |
| Audio | MP3, WAV |
| Documents | PDF |

Maximum file size: 20MB per file, 50 files per upload.

---

## Collections

Collections help you organize media into logical groups for easy reuse.

### Creating a Collection

1. Go to **Admin > Content > Collections**
2. Click **New collection**
3. Enter a **Name** (e.g., "Product Photos", "Team Headshots")
4. Optionally add a **Color** for visual identification
5. Click **Create**

### Assigning Media to Collections

**During upload:**
1. Select collections in the upload form
2. All uploaded files are assigned to selected collections

**For existing media:**
1. Click on a media item to edit
2. Select collections in the **Collections** field
3. Click **Save**

**Bulk assignment:**
1. Select multiple items using checkboxes
2. Click **Bulk actions > Update Alt Text** (collections coming soon)

### Using Collections in Blocks

The Media Gallery block can pull media directly from collections:

1. Add a **Media Gallery** block to a page
2. Set **Image Source** to **Media Collection(s)**
3. Select one or more collections
4. Choose ordering: **Newest First**, **Oldest First**, or **Random**
5. Optionally set a **Maximum Images** limit

This creates a dynamic gallery that updates when you add media to the collection.

---

## Image Optimization

TallCMS automatically optimizes images for web performance.

### How It Works

When you upload an image:
1. The original file is preserved
2. WebP variants are generated in multiple sizes:
   - **Thumbnail**: 300×300 (cropped square)
   - **Medium**: 800×600 (fit within bounds)
   - **Large**: 1200×800 (fit within bounds)
3. Your site serves the optimal format and size

### Benefits

- **Faster loading**: WebP images are 25-35% smaller than JPEG
- **Automatic**: No manual resizing required
- **Fallback support**: Original format served to older browsers

### Optimization Status

In the Media Library, use the **Optimized** filter:
- **Optimized**: Images with generated variants
- **Unoptimized**: Images pending or failed optimization

---

## Managing Media

### Filtering and Finding Media

**Tabs** filter by content type:
- **All Media**: Everything in the library
- **Unassigned**: Media not in any collection
- **Images**: Photos and graphics
- **Videos**: Video files
- **Documents**: PDFs and other files

**Filters** provide additional options:
- **Collection**: Show media from a specific collection
- **File Type**: Images, Videos, Audio, PDFs
- **Alt Text**: Find images missing alt text
- **Optimized**: Filter by optimization status
- **Recently Uploaded**: Last 7 days

Use the **Search** box to find files by name.

### Editing Media

Click on any media item to:
- View full-size preview
- Edit the **File Name**
- Update **Alt Text** and **Caption**
- Manage **Collections**
- Replace the file
- Delete the file

### Bulk Actions

Select multiple items using checkboxes:
- **Update Alt Text**: Apply the same alt text to all selected images
- **Delete**: Remove selected files

---

## Alt Text Management

Alt text describes images for screen readers and when images fail to load.

### Best Practices

**Good alt text:**
- "Team photo showing five employees in the office lobby"
- "Product dashboard displaying monthly sales chart"

**Poor alt text:**
- "image1.jpg"
- "photo"
- Empty

Keep alt text under 125 characters for optimal accessibility.

### Generate from Filename

When editing media, click the **sparkle icon** next to Alt Text to generate a description from the filename:
- `team-photo-2024.jpg` becomes "Team Photo 2024"

### Finding Missing Alt Text

1. Go to **Admin > Content > Media Library**
2. Open the **Filters** panel
3. Set **Alt Text** to **Missing alt text**
4. Review and update each image

### Bulk Update Alt Text

For images that share the same description:
1. Select multiple images using checkboxes
2. Click **Bulk actions > Update Alt Text**
3. Enter the alt text
4. Click **Update**

---

## Using Media in Content

### In Blocks

1. Edit a block with an image field
2. Click the upload area
3. Upload a new file or select from existing media
4. Save the block

### Media Gallery with Collections

For dynamic galleries that update automatically:

1. Add a **Media Gallery** block
2. Set **Image Source** to **Media Collection(s)**
3. Select collections containing your images
4. Configure layout and ordering
5. Save

When you add or remove images from the collection, the gallery updates automatically.

### Featured Images

Posts and pages can have featured images:

1. Edit the post/page
2. Go to the **SEO** tab
3. Upload or select a Featured Image
4. This image appears in social shares and content listings

### Recommended Sizes

| Usage | Recommended Size |
|-------|-----------------|
| Featured Images | 1200 × 630 px |
| Hero Backgrounds | 1920 × 1080 px |
| Gallery Images | 800 × 600 px minimum |
| Team/Testimonial Photos | 400 × 400 px (square) |
| Logos | 200 × 100 px |

---

## Cloud Storage

TallCMS can store media in cloud storage instead of locally.

### Supported Providers

- Amazon S3
- DigitalOcean Spaces
- Cloudflare R2
- Any S3-compatible service

### Benefits

- **Scalability**: No local disk limits
- **Performance**: CDN delivery for faster loading
- **Reliability**: Cloud provider handles redundancy

### Setup

Cloud storage is configured during installation or in your `.env` file. See the [installation guide](installation) for details.

Image optimization works with cloud storage—variants are generated locally and uploaded to your configured storage.

---

## Common Pitfalls

**"Image won't upload"**
Check file size (max 20MB). Verify the file type is supported.

**"Image looks blurry"**
Upload a higher resolution image. TallCMS generates smaller variants but can't upscale.

**"Gallery not showing collection images"**
Verify the collection contains images (not videos/documents). Check that images are assigned to the selected collection.

**"Optimization not running"**
Image optimization runs in the background queue. Ensure your queue worker is running: `php artisan queue:work`

**"Can't delete image"**
Images used in published content may be protected. Remove the image from content first.

---

## Next Steps

- [Using content blocks](blocks)
- [SEO and featured images](seo)
- [Site settings](site-settings)
