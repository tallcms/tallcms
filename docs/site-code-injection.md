---
title: "Code Injection"
slug: "code-injection"
audience: "site-owner"
category: "site-management"
order: 65
prerequisites:
  - "installation"
---

# Code Injection

> **What you'll learn:** How to embed analytics, tracking pixels, chat widgets, and other third-party scripts into your site without editing theme files.

**Time:** ~5 minutes

---

## Overview

Code Injection lets you add custom HTML, CSS, and JavaScript to every page on your site. Common uses include:

| Use Case | Example | Zone |
|----------|---------|------|
| Analytics | Google Analytics, Plausible, Fathom | Head |
| Tag managers | Google Tag Manager `<noscript>` | Body Start |
| Chat widgets | Intercom, Crisp, Tawk.to | Body End |
| Tracking pixels | Meta Pixel, LinkedIn Insight | Body End |
| Custom CSS | Brand overrides, font imports | Head |
| Meta tags | Verification tags, custom `<meta>` | Head |

---

## Injection Zones

Three zones control where your code appears in the page source:

| Zone | Location | Typical Content |
|------|----------|-----------------|
| **Head** | Inside `<head>` before `</head>` | Analytics scripts, CSS, meta tags |
| **Body Start** | Right after `<body>` | GTM noscript fallbacks, early scripts |
| **Body End** | Before `</body>` | Chat widgets, tracking pixels, deferred JS |

---

## Adding Code

1. Navigate to **Admin > Settings > Code Injection**
2. Paste your snippet into the appropriate zone
3. Click **Save**

The code appears on every frontend page immediately. Admin panel pages are not affected.

### Example: Google Analytics

Paste this in the **Head Code** field:

```html
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>
```

### Example: Chat Widget

Paste this in the **Body End Code** field:

```html
<script>
  window.$crisp = [];
  window.CRISP_WEBSITE_ID = "your-website-id";
  (function(){
    var d = document;
    var s = d.createElement("script");
    s.src = "https://client.crisp.chat/l.js";
    s.async = 1;
    d.getElementsByTagName("head")[0].appendChild(s);
  })();
</script>
```

---

## Audit Trail

Every save records who made the change and when. This information appears below each textarea as "Last modified by {name} on {date}".

---

## Permissions

Code Injection requires the **`Manage:CodeInjection`** permission. By default, only **super_admin** and **administrator** roles have this permission.

Editors and authors cannot access the page or save code, even if they navigate to the URL directly.

| Role | Access |
|------|--------|
| `super_admin` | Full access |
| `administrator` | Full access |
| `editor` | No access |
| `author` | No access |

To grant access to a custom role, assign the `Manage:CodeInjection` permission in **Admin > User Management > Roles**.

---

## Plugin Mode

When using TallCMS as a Filament plugin, add the code-injection component to your own layout:

```blade
<head>
    {{-- your existing head content --}}
    <x-tallcms::code-injection zone="head" />
</head>
<body>
    <x-tallcms::code-injection zone="body_start" />

    {{-- your page content --}}

    <x-tallcms::code-injection zone="body_end" />
</body>
```

### Disabling Code Injection

To remove the admin page entirely in plugin mode:

```php
// In your PanelProvider
TallCmsPlugin::make()
    ->withoutCodeInjection()
```

---

## Caching

Injected code is cached for 1 hour via `SiteSetting::get()`. After saving, the cache is cleared automatically. No extra database queries occur on each page load.

---

## Common Pitfalls

**"Code not appearing on the site"**
View the page source (not the rendered page) to confirm. Some scripts load asynchronously and won't be visible in the DOM inspector. Also check that your theme layout includes the `<x-tallcms::code-injection>` components.

**"Code appears in the wrong location"**
Check which zone you pasted into. Head Code appears in `<head>`, Body Start after `<body>`, Body End before `</body>`.

**"Broken site after saving"**
A syntax error in injected JavaScript can break page functionality. Navigate to **Admin > Settings > Code Injection** and clear the problematic field. The admin panel is never affected by injected code.

**"Access denied"**
Your role needs the `Manage:CodeInjection` permission. Ask a super_admin to assign it.

---

## Next Steps

- [Site settings](site-settings)
- [SEO settings](seo)
- [Roles & authorization](roles-authorization)
