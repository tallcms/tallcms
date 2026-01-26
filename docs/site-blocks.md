---
title: "Using Content Blocks"
slug: "blocks"
audience: "site-owner"
category: "site-management"
order: 20
---

# Using Content Blocks

> **What you'll learn:** How to use TallCMS's content blocks to build rich, engaging pages.

---

## Overview

Blocks are reusable content components that you add to pages and posts. They provide structured layouts for common content patterns like heroes, pricing tables, and testimonials.

TallCMS includes 16 built-in blocks organized into categories.

---

## Adding Blocks

1. Edit a page or post
2. Click inside the content editor
3. Click the **+ Add Block** button (or type `/` to search)
4. Select a block from the picker
5. Fill in the block's fields in the modal
6. Click **Save** to add the block

### Block Picker

The block picker organizes blocks by category:
- **Content**: Hero, Content, CTA, Features, Pricing, Divider
- **Media**: Media Gallery, Document List, Parallax
- **Social Proof**: Testimonials, Team, Logos, Stats
- **Dynamic**: Posts, FAQ, Timeline
- **Forms**: Contact Form

Use the search box to quickly find blocks by name or keyword.

---

## Content Blocks

### Hero Block

Full-width section perfect for page headers and landing pages.

**Fields:**
- Headline and description
- Background image or color
- CTA buttons (up to 2)
- Text alignment and overlay options

**Best for:** Page headers, landing page intros, promotional banners

### Content Block

Rich text section with a title and body content.

**Fields:**
- Title with heading level (H2-H4)
- Rich text body with formatting
- Content width option

**Best for:** Article sections, about content, general text

### Call to Action Block

Promotional section designed to drive conversions.

**Fields:**
- Headline and description
- Button text and link
- Background style

**Best for:** Newsletter signups, special offers, page closers

### Features Block

Grid layout showcasing product or service features.

**Fields:**
- Section title
- Feature items (icon, title, description)
- Column layout (2, 3, or 4 columns)

**Best for:** Product features, service highlights, benefits lists

### Pricing Block

Pricing table with plan comparison.

**Fields:**
- Plans (name, price, features, CTA)
- Highlight "recommended" plan
- Billing toggle (monthly/yearly)

**Best for:** SaaS pricing, service packages, membership tiers

### Divider Block

Visual separator between content sections.

**Fields:**
- Style (line, dots, space only)
- Size and color options

**Best for:** Breaking up long pages, visual rhythm

---

## Media Blocks

### Media Gallery Block

Responsive media gallery with lightbox supporting images and videos.

**Fields:**
- **Media Source**: Manual upload or Media Collection(s)
- **Media Type** (collection mode): Images only, Videos only, or both
- Layout: Grid (2-4 columns), Masonry, or Carousel
- Size options
- Lightbox with keyboard navigation and video playback

**Collection Mode:**
Select one or more media collections and the gallery automatically displays their media. Choose ordering (newest, oldest, random) and optionally limit the count. The gallery updates when you add media to the collection.

**Video Support:**
Videos display with a play button overlay. Click to open in lightbox with full playback controls.

**Best for:** Portfolio showcases, photo galleries, product images, video showcases, mixed media displays

### Document List Block

List of downloadable documents from media collections.

**Fields:**
- **Collections**: Select collections containing documents
- **File Types**: Filter by PDF, Word, Excel, ZIP (or show all)
- **Layout**: List, Cards, or Compact
- **Order**: Newest, Oldest, or Alphabetical
- Show/hide file size and type badges

**Features:**
- Automatic file type icons (PDF, DOC, XLS, ZIP)
- Download on click
- Pulls from same collections as Media Gallery

**Best for:** Resource downloads, document libraries, attachments, file archives

### Parallax Block

Full-width section with parallax scrolling effect.

**Fields:**
- Background image
- Overlay content (text, buttons)
- Parallax intensity

**Best for:** Visual breaks, impactful statements, mood sections

---

## Social Proof Blocks

### Testimonials Block

Customer testimonials and reviews.

**Fields:**
- Testimonial items (quote, author, photo, company)
- Layout style (cards, slider, grid)

**Best for:** Customer reviews, social proof, case study quotes

### Team Block

Team member profiles.

**Fields:**
- Team members (name, role, photo, bio)
- Social links
- Layout style

**Best for:** About pages, team introductions

### Logos Block

Client or partner logo showcase.

**Fields:**
- Logo images
- Display style (grid, scrolling)
- Grayscale option

**Best for:** Trust signals, client lists, partner showcases

### Stats Block

Key metrics and statistics display.

**Fields:**
- Stat items (number, label)
- Animated counting option
- Layout style

**Best for:** Achievements, impact metrics, company stats

---

## Dynamic Blocks

### Posts Block

Display blog posts from your site.

**Fields:**
- Post count
- Category filter
- Display style (grid, list)
- Show featured posts only option

**Best for:** Blog sections on pages, related posts, news widgets

### FAQ Block

Frequently asked questions accordion.

**Fields:**
- FAQ items (question, answer)
- Expand/collapse behavior

**Best for:** Help sections, product FAQs, support pages

### Timeline Block

Chronological events or milestones.

**Fields:**
- Timeline items (date, title, description)
- Layout style (vertical, alternating)

**Best for:** Company history, project timelines, roadmaps

---

## Form Blocks

### Contact Form Block

Contact form with customizable fields.

**Fields:**
- Form fields (name, email, message, custom)
- Success message
- Email notification settings

**Best for:** Contact pages, inquiry forms, feedback collection

---

## Block Settings

Most blocks share common settings:

### Content Width

Controls how wide the block content appears:
- **Inherit from Page**: Uses the page's width setting
- **Narrow** (672px): Focused reading
- **Standard** (1152px): Default for most content
- **Wide** (1280px): Image-heavy layouts
- **Full Width**: Edge-to-edge

### Background Options

Many blocks support background customization:
- Solid colors (using theme palette)
- Images with overlay
- Gradient options

### Alignment

Control text and content alignment:
- Left, Center, Right
- Some blocks have vertical alignment too

---

## Tips for Using Blocks

**Start with Hero**
Most pages benefit from a Hero block at the top to set the tone.

**Alternate block types**
Vary your blocks to create visual interest. Don't stack multiple Content blocks without variety.

**Use consistent styling**
Stick to 2-3 background colors throughout your site for a cohesive look.

**Preview often**
Use the Preview button to see how blocks look on the frontend.

---

## Common Pitfalls

**"Block looks different in preview"**
Admin preview shows structure but may not match exact theme styling. Always check the frontend.

**"Images are blurry"**
Upload high-resolution images. Most blocks work best with images at least 1200px wide.

**"Block not saving"**
Ensure all required fields are filled. Required fields are marked with an asterisk.

---

## Next Steps

- [Block development guide](block-development) - Create custom blocks
- [Managing media](media)
- [Page settings reference](page-settings)
