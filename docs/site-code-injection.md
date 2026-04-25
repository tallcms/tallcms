---
title: "Embed Code"
slug: "code-injection"
audience: "site-owner"
category: "site-management"
order: 65
prerequisites:
  - "installation"
---

# Embed Code

> **What you'll learn:** How to embed analytics, tracking pixels, chat widgets, and other third-party scripts into your site without editing theme files.

**Time:** ~5 minutes

---

## Overview

Embed Code lets you add custom HTML, CSS, and JavaScript to every page of a site. Common uses:

| Use Case        | Example                              | Zone       |
|-----------------|--------------------------------------|------------|
| Analytics       | Google Analytics, Plausible, Fathom  | Head       |
| Tag managers    | Google Tag Manager `<noscript>`      | Body Start |
| Chat widgets    | Intercom, Crisp, Tawk.to             | Body End   |
| Tracking pixels | Meta Pixel, LinkedIn Insight         | Body End   |
| Custom CSS      | Brand overrides, font imports        | Head       |
| Meta tags       | Verification tags, custom `<meta>`   | Head       |

Embed code is **scoped per site**: each site has its own head/body snippets. Multisite installs no longer share a single installation-wide block.

---

## Injection Zones

Three zones control where your code appears in the page source:

| Zone           | Location                            | Typical Content                              |
|----------------|-------------------------------------|----------------------------------------------|
| **Head**       | Inside `<head>` before `</head>`    | Analytics scripts, CSS, meta tags            |
| **Body Start** | Right after `<body>`                | GTM noscript fallbacks, early scripts        |
| **Body End**   | Before `</body>`                    | Chat widgets, tracking pixels, deferred JS   |

---

## Adding Code

1. Navigate to **Admin > Sites > {your site} > Edit**.
2. Open the **Embed Code** tab.
3. Paste your snippet into the appropriate zone.
4. Click **Save**.

The code appears on that site's frontend pages immediately. Admin panel pages are never affected. Other sites in a multisite install are unaffected unless you set their embed code separately.

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

## Inheritance

Each site's embed code starts blank. If a site has no override, the field is empty and nothing is injected for that zone. Embed code does **not** automatically inherit from a global default — set it explicitly per site.

---

## Permissions

**Access follows Site edit permission.** Anyone allowed to edit a Site is allowed to set its embed code — there is no separate `Manage:CodeInjection` permission.

| Role            | Can set embed code? |
|-----------------|---------------------|
| `super_admin`   | Yes (any site)      |
| Site owner      | Yes (sites they own) |
| `editor`        | Only if SitePolicy allows them to edit the Site |
| `author`        | Only if SitePolicy allows them to edit the Site |

In a SaaS multisite context this is a deliberate choice: site owners are expected to be able to add their own analytics and chat widgets. If you want stricter gating in your install, override the Embed Code tab visibility in `SiteForm::embedCodeTab()` (e.g. limit it to `super_admin`).

---

## Plugin Mode

When using TallCMS as a Filament plugin in your own Laravel app, add the embed component to your layout so the saved code actually renders:

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

The component reads the current site's embed code via `SiteSetting::get()` and renders nothing if the field is empty.

---

## Caching

Each site's embed code is cached per site via `SiteSetting::get()` for 1 hour. After saving, the cache is cleared automatically. No extra database queries occur on each page load.

---

## Common Pitfalls

**"Code not appearing on the site"**
Check that you saved on the right site (the URL should be `/admin/sites/{id}/edit`, not a different site). View the page source (not the rendered DOM) to confirm — some scripts load asynchronously and won't appear in the inspector. Also confirm your theme layout includes the `<x-tallcms::code-injection>` zone components.

**"Code appears on the wrong site"**
Embed code is per-site. If a snippet shows on Site A but not Site B, you saved it on A. Open Site B's edit page and paste it there too if you need the same snippet on both.

**"Broken site after saving"**
A syntax error in injected JavaScript can break page functionality. Open the Site's Embed Code tab and clear the problematic field. The admin panel is never affected by injected code, so you can always reach the editor.

**"I can't see the Embed Code tab"**
Your role must be allowed to edit the Site (via SitePolicy). Ask a super_admin to grant you Site edit access for this site, or to make you the site's owner.

---

## Migration from v4.3 and Earlier

In v4.3 and earlier, embed code lived on a standalone `/admin/code-injection` page and was installation-wide. In v4.4 it moved to a per-site tab on the Site edit page. The standalone page was removed.

- Existing **global rows** in `tallcms_site_settings` (`code_head`, `code_body_start`, `code_body_end`) are no longer read by the frontend — each site needs its own override now.
- After upgrading, copy the previous global value into each site that should keep it. The Embed Code tab starts empty.
- The `Manage:CodeInjection` permission is no longer used. Existing installs may still have an orphan row in the permissions table; it's harmless and can be deleted by hand.
- `TallCmsPlugin::make()->withoutCodeInjection()` is kept as a no-op for backwards compatibility but does nothing — the standalone page no longer exists.

---

## Next Steps

- [Site settings](site-settings)
- [SEO settings](seo)
- [Roles & authorization](roles-authorization)
