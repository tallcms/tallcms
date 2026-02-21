---
title: "Comments"
slug: "comments"
audience: "all"
category: "site-management"
order: 30
---

# Comments

> **What you'll learn:** How to enable comments on posts, moderate submissions, and configure comment behavior.

**Time:** ~5 minutes

---

## Overview

TallCMS includes a native comment system for blog posts. Visitors can leave comments on published posts, and you manage them through the admin panel.

Key features:

- Guest and authenticated commenting
- Nested replies with configurable depth
- Spam protection (honeypot + rate limiting)
- Per-post-block toggle
- Manual or auto-approve moderation
- Email notifications for new comments and approvals

---

## 1. Enable Comments on a Page

Comments appear on individual post pages rendered by a **Posts block**.

1. Go to **Admin > Content > Pages**
2. Edit the page that contains your Posts block
3. Click the **Posts block** to open its settings
4. Under **Display Options**, toggle **Show Comments** on
5. Click **Save**

Comments now appear below each post when a visitor views it.

> **Note:** Comments only display on published posts. Draft, pending, and scheduled posts do not show the comment section.

---

## 2. Moderate Comments

Navigate to **Admin > Content Management > Comments**. The navigation badge shows the number of pending comments.

### Status Tabs

| Tab | Shows |
|-----|-------|
| **All** | Every comment regardless of status |
| **Pending** | Awaiting your review |
| **Approved** | Visible on the frontend |
| **Rejected** | Hidden from the frontend |
| **Spam** | Flagged as spam |

### Approve or Reject

**Single comment:**

1. Click a pending comment to view it
2. Click **Approve**, **Reject**, or **Mark as Spam** in the header

**Bulk moderation:**

1. Select multiple comments using the checkboxes
2. Use the **Bulk Actions** dropdown to approve, reject, or mark as spam

### Soft Delete & Restore

Deleted comments move to the trash. Use the **Trashed** filter to view and restore them.

---

## 3. Configure Comment Behavior

All comment settings live in `config/tallcms.php` under the `comments` key.

### Moderation Mode

```php
'comments' => [
    'moderation' => env('TALLCMS_COMMENTS_MODERATION', 'manual'),
],
```

| Value | Behavior |
|-------|----------|
| `manual` | All comments require admin approval before appearing (default) |
| `auto` | Comments appear immediately without review |

### Guest Comments

```php
'guest_comments' => true,
```

When `true`, visitors can comment without logging in by providing a name and email. When `false`, only authenticated users can comment and a login link is shown.

### Nesting Depth

```php
'max_depth' => 2,
```

Controls how deep reply threads can go:

| Value | Result |
|-------|--------|
| `1` | Top-level comments only, no replies |
| `2` | One level of replies (default) |
| `3` | Replies to replies |

### Rate Limiting

```php
'rate_limit' => 5,
'rate_limit_decay' => 600,
```

Limits each IP address to 5 comments per 10-minute window. Adjust both values to suit your traffic.

### Content Length

```php
'max_length' => 5000,
```

Maximum characters per comment.

### Global Kill Switch

```php
'enabled' => env('TALLCMS_COMMENTS_ENABLED', true),
```

Set to `false` to disable comments site-wide. This overrides the per-block toggle — no comment forms render and the submission endpoint returns 404.

---

## 4. Notifications

### New Comment Notifications

When a comment is submitted (in `manual` moderation mode), all users with the **Approve:CmsComment** permission receive a notification via the configured channels.

```php
'notification_channels' => ['mail', 'database'],
```

Database notifications appear as Filament notifications in the admin panel. Mail notifications link directly to the comment in the admin.

### Approval Notifications

When you approve a comment, the commenter receives an email with a link to their comment on the post.

```php
'notify_on_approval' => true,
```

Set to `false` to disable approval emails.

---

## Configuration Reference

| Key | Default | Description |
|-----|---------|-------------|
| `enabled` | `true` | Master switch for the comment system |
| `moderation` | `manual` | `manual` or `auto` |
| `max_depth` | `2` | Maximum reply nesting depth |
| `max_length` | `5000` | Maximum comment length in characters |
| `rate_limit` | `5` | Max comments per IP per window |
| `rate_limit_decay` | `600` | Rate limit window in seconds |
| `notification_channels` | `['mail', 'database']` | Channels for new comment alerts |
| `notify_on_approval` | `true` | Email commenter when approved |
| `guest_comments` | `true` | Allow unauthenticated comments |

### Environment Variables

| Variable | Maps To |
|----------|---------|
| `TALLCMS_COMMENTS_ENABLED` | `comments.enabled` |
| `TALLCMS_COMMENTS_MODERATION` | `comments.moderation` |

---

## Common Pitfalls

**"Comments section doesn't appear on my post"**
Check three things: the global `comments.enabled` is `true`, the Posts block has **Show Comments** toggled on, and the post status is **Published** with a `published_at` date in the past.

**"Comment form is visible but nothing happens on submit"**
Rebuild your theme's frontend assets. The comment form requires the Alpine.js `commentForm` component, which is bundled in `resources/js/tallcms/components/comments.js`. Run `npm run build` in both the root and your theme directory.

**"Guest gets 'You must log in' instead of the comment form"**
Set `tallcms.comments.guest_comments` to `true` in your config.

**"Comments appear in admin but not on the frontend"**
Comments must have **Approved** status to display publicly. Check the comment status in **Admin > Comments**.

---

## Next Steps

- [Managing Pages & Posts](pages-posts)
- [Posts Block](block-posts)
- [Roles & Authorization](roles-authorization)
