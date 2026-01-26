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

The Media Library stores all images and files uploaded to your site. Access it via **Admin > Content > Media**.

---

## Uploading Files

### From the Media Library

1. Go to **Admin > Content > Media**
2. Click **Upload** in the top-right
3. Select files from your computer or drag and drop
4. Files are uploaded and ready to use

### From Content Editor

When editing a page or post:

1. Add a block that accepts images (Hero, Gallery, etc.)
2. Click the image upload field
3. Choose **Upload** to add a new file, or **Browse** to select existing media

### Supported File Types

| Type | Extensions |
|------|------------|
| Images | JPG, PNG, GIF, WebP, SVG |
| Documents | PDF, DOC, DOCX, XLS, XLSX |
| Archives | ZIP |

Maximum file size depends on your server configuration (typically 2-10MB).

---

## Managing Media

### Viewing Media

The Media Library shows all uploaded files with:
- Thumbnail preview
- Filename
- Upload date
- File size

Use the search box to find files by name.

### Editing Media

Click on any media item to:
- View full-size preview
- Edit the **Title** (display name)
- Edit the **Alt Text** (accessibility description)
- See the file URL
- Delete the file

### Alt Text Best Practices

Alt text describes images for screen readers and when images fail to load:

**Good alt text:**
- "Team photo showing five employees in the office"
- "Product screenshot displaying the dashboard"

**Poor alt text:**
- "image1.jpg"
- "photo"
- Empty (no alt text)

---

## Using Media in Content

### In Blocks

1. Edit a block with an image field
2. Click the upload area
3. Choose **Browse** to open the Media Library
4. Click on the image you want to use
5. Save the block

### Featured Images

Posts and pages can have featured images:

1. Edit the post/page
2. Go to the **SEO** tab
3. Upload or select a Featured Image
4. This image appears in social shares and content listings

### Recommended Sizes

| Usage | Recommended Size |
|-------|-----------------|
| Featured Images | 1200 x 630 px |
| Hero Backgrounds | 1920 x 1080 px |
| Gallery Images | 800 x 600 px minimum |
| Team/Testimonial Photos | 400 x 400 px (square) |
| Logos | 200 x 100 px |

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

---

## Organizing Media

### File Naming

Upload files with descriptive names:
- Good: `team-photo-2024.jpg`, `product-dashboard.png`
- Poor: `IMG_0042.jpg`, `screenshot.png`

### Cleanup

Periodically review and delete unused media:

1. Go to **Admin > Content > Media**
2. Sort by date to find old files
3. Delete files no longer in use

---

## Common Pitfalls

**"Image won't upload"**
Check file size (may exceed server limit). Verify the file type is supported.

**"Image looks blurry"**
Upload a higher resolution image. Check that the image isn't being stretched beyond its original size.

**"Wrong image appears"**
Clear your browser cache. If using a CDN, the old image may be cached.

**"Can't delete image"**
Images used in published content may be protected. Remove the image from content first.

---

## Next Steps

- [Using content blocks](blocks)
- [SEO and featured images](seo)
- [Site settings](site-settings)
