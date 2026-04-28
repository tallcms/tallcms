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

### From the Rich Editor

When editing a page or post in the rich editor, you have two options for adding images:

**Attach a new file:**

1. Click the **paperclip** icon in the toolbar
2. Select a file from your computer
3. Add alt text and click **Submit**

The file is automatically saved to the Media Library.

**Insert from Media Library:**

1. Click the **photo** icon in the toolbar (next to the paperclip)
2. Browse or search the list of existing images
3. Click a row to select it — alt text pre-fills from the media record
4. Edit the alt text if needed and click **Submit**

The image is inserted inline with a reference to the Media Library record, so it stays in sync if you update the media later.

### From Block Fields

When editing a block that accepts images (Hero, Gallery, etc.):

1. Click the image upload field
2. Upload a new file or select from existing media

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

## File Storage

### How TallCMS Stores Files

By default, TallCMS saves uploaded files to your server's local disk at `storage/app/public/media`, served via a symlink at `public/storage`. This works out of the box with no configuration required.

Each media record stores the disk name it was uploaded to, so files are always read from the correct location regardless of future configuration changes.

### Switching to Cloud Storage

TallCMS automatically uses S3-compatible storage when it detects a valid S3 configuration in your `.env`. No other change is needed:

```env
# Your storage credentials
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name

# Tell Laravel (and TallCMS) to use S3
FILESYSTEM_DISK=s3
```

Once set, all new uploads go to S3. Existing local files are not migrated automatically.

**Supported providers:**
- Amazon S3
- DigitalOcean Spaces
- Cloudflare R2
- Backblaze B2
- Wasabi
- MinIO (self-hosted)
- Any S3-compatible service

### Using a Dedicated Media Disk

If you want TallCMS media on a separate disk — without changing your app's default filesystem — define a named disk in `config/filesystems.php` and point TallCMS at it with `TALLCMS_MEDIA_DISK`.

**Step 1** — add the disk to `config/filesystems.php`:

```php
'disks' => [
    // ... your existing disks ...

    'cms-media' => [
        'driver' => 's3',
        'key'    => env('CMS_MEDIA_KEY'),
        'secret' => env('CMS_MEDIA_SECRET'),
        'region' => env('CMS_MEDIA_REGION', 'us-east-1'),
        'bucket' => env('CMS_MEDIA_BUCKET'),
        'url'    => env('CMS_MEDIA_URL'),         // optional CDN URL
        'visibility' => 'public',
    ],
],
```

**Step 2** — set the disk name in `.env`:

```env
TALLCMS_MEDIA_DISK=cms-media
```

`TALLCMS_MEDIA_DISK` takes priority over the auto-detection logic. Any disk registered in `config/filesystems.php` works — local, S3, or any custom driver.

### Provider Quick-Reference

**DigitalOcean Spaces:**
```env
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=my-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
FILESYSTEM_DISK=s3
```

**Cloudflare R2:**
```env
AWS_ACCESS_KEY_ID=your-r2-access-key
AWS_SECRET_ACCESS_KEY=your-r2-secret-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=my-bucket
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
FILESYSTEM_DISK=s3
```

**MinIO (self-hosted):**
```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

> Image optimization works with all storage backends — variants are generated locally and uploaded to your configured disk.

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

**"Images uploaded to the wrong disk after changing storage config"**
Each media record stores the disk it was uploaded to. Changing `TALLCMS_MEDIA_DISK` or `FILESYSTEM_DISK` only affects new uploads — existing records still reference their original disk. To move existing files, re-upload them after updating the configuration.

**"Uploaded file is inaccessible after setting TALLCMS_MEDIA_DISK"**
The disk name in `TALLCMS_MEDIA_DISK` must match a key in the `disks` array in `config/filesystems.php`. Check for typos and run `php artisan config:clear` after any config change.

**"Can't delete image"**
Images used in published content may be protected. Remove the image from content first.

---

## Next Steps

- [Using content blocks](blocks)
- [SEO and featured images](seo)
- [Site settings](site-settings)
