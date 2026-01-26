---
title: "Internationalization"
slug: "i18n"
audience: "all"
category: "reference"
order: 30
---

# Internationalization (i18n) Reference

Complete reference for multilingual content support in TallCMS.

---

## Overview

TallCMS provides comprehensive multilingual support:

- Translate pages, posts, categories, and menu items
- Locale-prefixed URLs (e.g., `/zh-CN/about`)
- Hide default locale from URLs
- Admin panel locale switcher
- Frontend language switchers and hreflang tags

Built on [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable).

---

## Enabling i18n

### Quick Start

1. Add to `.env`:
   ```env
   TALLCMS_I18N_ENABLED=true
   ```

2. Configure locales in `config/tallcms.php`

3. Run migrations:
   ```bash
   php artisan migrate
   ```

4. Clear caches:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

### Verification

```bash
php artisan tinker --execute="echo tallcms_i18n_enabled() ? 'Enabled' : 'Disabled';"
```

---

## Configuration

All settings in `config/tallcms.php` under `i18n`:

```php
'i18n' => [
    'enabled' => env('TALLCMS_I18N_ENABLED', false),

    'locales' => [
        'en' => [
            'label' => 'English',
            'native' => 'English',
            'rtl' => false,
        ],
        'zh_CN' => [
            'label' => 'Chinese (Simplified)',
            'native' => '简体中文',
            'rtl' => false,
        ],
        'ar' => [
            'label' => 'Arabic',
            'native' => 'العربية',
            'rtl' => true,
        ],
    ],

    'default_locale' => env('TALLCMS_DEFAULT_LOCALE', 'en'),
    'url_strategy' => 'prefix',
    'hide_default_locale' => env('TALLCMS_HIDE_DEFAULT_LOCALE', true),
],
```

### Locale Configuration

| Key | Description |
|-----|-------------|
| `label` | Display name in admin |
| `native` | Native language name |
| `rtl` | Right-to-left text direction |

### URL Strategy

| Strategy | Example |
|----------|---------|
| `prefix` | `/zh-CN/about` |
| `none` | `/about?lang=zh-CN` |

---

## Managing Locales

### Adding a Locale

Add to `config/tallcms.php`:

```php
'locales' => [
    // Existing locales...
    'de' => [
        'label' => 'German',
        'native' => 'Deutsch',
        'rtl' => false,
    ],
],
```

### Removing a Locale

Remove from config. Existing translations are preserved in the database.

---

## Translating Content

### Admin Panel

1. Edit a page/post
2. Use the **locale switcher** in the header
3. Translate fields for each locale
4. Save changes

### Translatable Fields

**Pages:**
- Title
- Slug
- Content

**Posts:**
- Title
- Slug
- Excerpt
- Content

**Categories:**
- Name
- Slug
- Description

**Menu Items:**
- Label

### Fallback Behavior

If translation doesn't exist, falls back to default locale.

---

## URL Strategies

### Prefix Strategy (Default)

URLs include locale prefix:

| URL | Locale |
|-----|--------|
| `/about` | Default locale |
| `/zh-CN/about` | Chinese |
| `/de/about` | German |

### Hide Default Locale

When `hide_default_locale = true`:
- Default locale: `/about`
- Other locales: `/zh-CN/about`

### Query Parameter Strategy

When `url_strategy = 'none'`:
- All pages: `/about?lang=zh-CN`

---

## Admin Panel Usage

### Locale Switcher

A dropdown appears in the admin header showing:
- Current locale
- All available locales
- Completion status (fields translated)

### Translation Status

Visual indicators show:
- ✓ Complete (all fields translated)
- ○ Partial (some fields translated)
- ✗ Missing (no translations)

---

## Frontend Components

### Language Switcher

```blade
<x-tallcms::i18n.language-switcher />
```

Options:
- `style`: dropdown, inline, flags
- `showNative`: Show native names

### Hreflang Tags

```blade
<x-tallcms::seo.hreflang :page="$page" />
```

Outputs:
```html
<link rel="alternate" hreflang="en" href="https://example.com/about">
<link rel="alternate" hreflang="zh-CN" href="https://example.com/zh-CN/about">
<link rel="alternate" hreflang="x-default" href="https://example.com/about">
```

---

## Helper Functions

```php
// Check if i18n is enabled
tallcms_i18n_enabled(): bool

// Get current locale
tallcms_current_locale(): string

// Get all locales
tallcms_locales(): array

// Get default locale
tallcms_default_locale(): string

// Check if locale is RTL
tallcms_is_rtl(?string $locale = null): bool

// Get localized URL
tallcms_localized_url(string $path, string $locale): string
```

---

## Programmatic Access

### Translatable Models

```php
// Get translation
$page->getTranslation('title', 'zh_CN');

// Set translation
$page->setTranslation('title', 'zh_CN', '关于我们');

// Check if translation exists
$page->hasTranslation('title', 'zh_CN');

// Get all translations
$page->getTranslations('title');
// ['en' => 'About Us', 'zh_CN' => '关于我们']
```

### LocaleRegistry Service

```php
use TallCms\Cms\Services\LocaleRegistry;

$registry = app(LocaleRegistry::class);

$registry->isEnabled();
$registry->getLocales();
$registry->getDefaultLocale();
$registry->getCurrentLocale();
$registry->setCurrentLocale('zh_CN');
```

---

## Database Schema

Translatable fields are stored as JSON:

```json
{
    "en": "About Us",
    "zh_CN": "关于我们"
}
```

The `title` column in `tallcms_pages`:
```sql
title JSON NOT NULL
```

---

## SEO Considerations

### URL Canonicalization

Each locale has its own canonical URL. Cross-locale links use hreflang.

### Sitemap

Sitemap includes all localized URLs with `xhtml:link` alternate references.

### Meta Tags

Meta title and description are translatable and output in current locale.

---

## Common Pitfalls

**"Translations not saving"**
Ensure locale exists in config. Check JSON column supports your content length.

**"Wrong locale showing"**
Clear route cache. Check middleware order. Verify session/cookie settings.

**"RTL not working"**
Add `dir="rtl"` to your HTML element based on `tallcms_is_rtl()`.

**"Hreflang missing locales"**
Ensure page has translations for all locales you want included.

---

## Next Steps

- [Site settings](site-settings)
- [SEO features](seo)
- [Menu management](menus)
