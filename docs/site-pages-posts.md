---
title: "Managing Pages & Posts"
slug: "pages-posts"
audience: "site-owner"
category: "site-management"
order: 10
---

# Managing Pages & Posts

> **What you'll learn:** The difference between pages and posts, and how to organize your content effectively.

---

## Overview

TallCMS separates content into two types:

| Content Type | Best For | Features |
|--------------|----------|----------|
| **Pages** | Evergreen content (About, Contact, Services) | Hierarchy, homepage designation, custom templates |
| **Posts** | Time-sensitive content (News, Blog, Updates) | Categories, author attribution, RSS feeds |

---

## Working with Pages

### Creating a Page

1. Go to **Admin > Content > Pages**
2. Click **New Page**
3. Enter a title and add content using blocks
4. Configure settings in the **Settings** tab
5. Click **Save**

### Page Hierarchy

Pages can have parent-child relationships:

1. Edit a page
2. Go to the **Settings** tab
3. Select a **Parent Page**
4. Set **Sort Order** to control position among siblings

Child pages inherit breadcrumb structure and can have nested URLs depending on your theme.

### Setting a Homepage

1. Edit the page you want as your homepage
2. Go to the **Settings** tab
3. Toggle **Set as Homepage**
4. Save the page

Only one page can be the homepage. Setting a new homepage automatically removes the flag from the previous one.

### Page Templates

For custom layouts, you can assign a custom template:

1. Go to the **Settings** tab
2. Enter a template name in **Custom Template** (e.g., `pages.landing`)
3. Create the template file at `resources/views/pages/landing.blade.php`

---

## Working with Posts

### Creating a Post

1. Go to **Admin > Content > Posts**
2. Click **New Post**
3. Fill in:
   - **Title**: The post headline
   - **Excerpt**: A brief summary for listings
   - **Content**: Your post body using blocks
4. Assign categories in the **Settings** tab
5. Add a featured image in the **SEO** tab
6. Click **Save**

### Categories

Categories help organize posts and enable filtered browsing:

**Creating Categories:**
1. Go to **Admin > Content > Categories**
2. Click **New Category**
3. Enter a name, slug, and optional description
4. For nested categories, select a **Parent Category**

**Assigning Categories:**
1. Edit a post
2. Go to the **Settings** tab
3. Select one or more categories

### Featured Posts

Mark posts as featured to highlight them:

1. Edit a post
2. Go to the **Settings** tab
3. Toggle **Featured**

Featured posts can be displayed prominently by themes and the Posts block.

---

## Content Status Workflow

Both pages and posts follow the same status workflow:

| Status | Description |
|--------|-------------|
| **Draft** | Work in progress, not visible to the public |
| **Pending Review** | Submitted for approval (if workflow enabled) |
| **Published** | Live and accessible to visitors |
| **Archived** | Hidden but preserved for reference |

### Scheduled Publishing

To schedule content for future publication:

1. Set **Status** to "Published"
2. Set **Publish Date** to a future date/time
3. Save the content

The content becomes visible automatically when the publish date arrives.

---

## Bulk Actions

### Pages

From the Pages list:
- **Delete**: Remove selected pages
- **Archive**: Move to archived status
- **Publish**: Change status to published

### Posts

From the Posts list:
- **Delete**: Remove selected posts
- **Archive**: Move to archived status
- **Publish**: Change status to published

---

## Content Organization Tips

**Use pages for:**
- About, Contact, Services pages
- Legal pages (Privacy Policy, Terms)
- Landing pages
- Any content that doesn't have a publication date

**Use posts for:**
- Blog articles
- News updates
- Announcements
- Any time-sensitive content

**Organize with categories:**
- Keep category hierarchy shallow (2 levels max)
- Use descriptive names
- Avoid too many categories (5-10 is ideal for most sites)

---

## Common Pitfalls

**"My page isn't showing up"**
Check that the status is **Published** and the publish date isn't in the future.

**"Posts appear in wrong order"**
Posts are sorted by publish date (newest first) by default. Adjust publish dates to reorder.

**"Category archive shows no posts"**
Categories only show posts that are published and have a past publish date.

---

## Next Steps

- [Using content blocks](blocks)
- [SEO settings](seo)
- [Publishing workflow details](publishing)
