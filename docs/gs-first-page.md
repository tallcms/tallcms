---
title: "Create Your First Page"
slug: "first-page"
audience: "site-owner"
category: "getting-started"
order: 20
time: 5
prerequisites:
  - "installation"
---

# Create Your First Page

> **What you'll learn:** How to create a page, add content blocks, and publish it live.

**Time:** ~5 minutes

---

## 1. Open the Pages panel

Navigate to **Admin > Content > Pages** in the sidebar.

## 2. Click "New Page"

Click the **New Page** button in the top-right corner. You'll see the page editor with several tabs.

## 3. Add a title

Enter a title like "About Us" in the **Title** field. The slug (URL path) is auto-generated from your title.

## 4. Add your first block

1. Click inside the content editor
2. Click the **+ Add Block** button
3. Select **Hero** from the block picker
4. Fill in the fields:
   - **Headline**: "Welcome to Our Company"
   - **Description**: A brief introduction
   - **Button Text**: "Learn More" (optional)

## 5. Add more content

Click **+ Add Block** again and select **Content Block** to add a rich text section:

1. Enter a subtitle like "Our Story"
2. Write your content in the body field
3. Use the toolbar for formatting (bold, lists, links)

## 6. Save and publish

1. Click the **Settings** tab
2. Change **Status** from "Draft" to "Published"
3. Click **Save** in the top-right corner

## 7. View your page

Click the **Preview** button to see your page, or visit `yoursite.com/about-us` directly.

---

## Common Pitfalls

**"My page isn't showing up"**
Check the status is set to **Published**, not Draft. Also ensure the publish date isn't set in the future.

**"The URL looks wrong"**
The slug is auto-generated from the title. Edit it manually in the **Slug** field on the Content tab.

**"Blocks look different on frontend"**
Clear your browser cache. If using a theme, ensure the theme is built (`npm run build` in the theme directory).

**"I can't find the save button"**
The Save button is in the top-right corner of the page header, above the tabs.

---

## Next Steps

- [Publish your first post](first-post)
- [Set up navigation menus](quick-menus)
- [Explore all content blocks](blocks)
