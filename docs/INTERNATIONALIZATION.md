# Internationalization (i18n) & Multilingual Content

> **User & Developer Documentation** - Setting up and managing multilingual content in TallCMS

## Table of Contents

1. [Overview](#overview)
2. [Enabling Multilingual Support](#enabling-multilingual-support)
3. [Configuration Options](#configuration-options)
4. [Managing Locales](#managing-locales)
5. [Translating Content](#translating-content)
6. [URL Strategies](#url-strategies)
7. [Admin Panel Usage](#admin-panel-usage)
8. [Frontend Components](#frontend-components)
9. [Helper Functions](#helper-functions)
10. [Technical Architecture](#technical-architecture)

---

## Overview

TallCMS provides comprehensive multilingual support, allowing you to:

- Translate pages, posts, categories, and menu items
- Use locale-prefixed URLs (e.g., `/zh-CN/about`)
- Hide the default locale from URLs for cleaner paths
- Switch languages in the admin panel with session persistence
- Display language switchers and hreflang tags on the frontend

The system is built on [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable) with a custom `LocaleRegistry` service for centralized locale management.

---

## Enabling Multilingual Support

### Quick Start

1. Add to your `.env` file:
   ```env
   TALLCMS_I18N_ENABLED=true
   ```

2. Configure your locales in `config/tallcms.php` (see [Configuration Options](#configuration-options))

3. Run the migration (if not already run):
   ```bash
   php artisan migrate
   ```

4. Clear caches:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

### Verification

Check that i18n is enabled:
```bash
php artisan tinker --execute="echo tallcms_i18n_enabled() ? 'Enabled' : 'Disabled';"
```

---

## Configuration Options

All i18n settings are in `config/tallcms.php` under the `i18n` key:

```php
'i18n' => [
    // Master switch for multilingual features
    'enabled' => env('TALLCMS_I18N_ENABLED', false),

    // Available locales with their configuration
    'locales' => [
        'en' => [
            'label' => 'English',      // Display name in admin
            'native' => 'English',     // Native language name (shown in switcher)
            'rtl' => false,            // Right-to-left text direction
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

    // Default/fallback locale (must exist in locales array)
    'default_locale' => env('TALLCMS_DEFAULT_LOCALE', 'en'),

    // URL strategy: 'prefix' (/en/about) or 'none' (no locale in URL)
    'url_strategy' => 'prefix',

    // Hide default locale from URL (/ instead of /en/)
    'hide_default_locale' => env('TALLCMS_HIDE_DEFAULT_LOCALE', true),

    // Fallback when translation missing: 'default', 'empty', 'key'
    'fallback_behavior' => 'default',

    // Remember locale preference in session
    'remember_locale' => true,
],
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TALLCMS_I18N_ENABLED` | `false` | Enable/disable multilingual features |
| `TALLCMS_DEFAULT_LOCALE` | `en` | Default locale code |
| `TALLCMS_HIDE_DEFAULT_LOCALE` | `true` | Hide default locale from URLs |

---

## Managing Locales

### Locale Code Format

TallCMS uses two locale code formats:

| Format | Example | Usage |
|--------|---------|-------|
| **Internal** | `zh_cn` | Database storage, config keys, PHP code |
| **BCP-47** | `zh-CN` | URLs, HTML lang attributes, hreflang tags |

Conversion is automatic via `LocaleRegistry::toBcp47()` and `LocaleRegistry::toInternal()`.

### Adding a New Locale

1. Add the locale to `config/tallcms.php`:
   ```php
   'locales' => [
       // ... existing locales
       'es' => [
           'label' => 'Spanish',
           'native' => 'Español',
           'rtl' => false,
       ],
   ],
   ```

2. Clear caches:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

3. Translate your content in the admin panel

### LocaleRegistry Service

The `LocaleRegistry` service provides centralized locale management:

```php
use TallCms\Cms\Services\LocaleRegistry;

$registry = app(LocaleRegistry::class);

// Get all locale codes (internal format)
$codes = $registry->getLocaleCodes(); // ['en', 'zh_cn']

// Get all locales with configuration
$locales = $registry->getLocales(); // Collection

// Get default locale
$default = $registry->getDefaultLocale(); // 'en'

// Convert formats
$bcp47 = LocaleRegistry::toBcp47('zh_cn');    // 'zh-CN'
$internal = LocaleRegistry::toInternal('zh-CN'); // 'zh_cn'
```

---

## Translating Content

### Translatable Fields

The following content types support translations:

| Content Type | Translatable Fields |
|--------------|---------------------|
| **Pages** | `title`, `slug`, `content`, `excerpt`, `meta_title`, `meta_description` |
| **Posts** | `title`, `slug`, `content`, `excerpt`, `meta_title`, `meta_description` |
| **Categories** | `name`, `slug`, `description` |
| **Menu Items** | `label` |

### How Translations Are Stored

Translations are stored as JSON in the database:

```json
{
    "en": "About Us",
    "zh_cn": "关于我们"
}
```

### Accessing Translations in Code

```php
// Get translation for specific locale
$title = $page->getTranslation('title', 'zh_cn');

// Get translation with fallback to default locale
$title = $page->getTranslation('title', 'zh_cn', true);

// Set translation
$page->setTranslation('title', 'zh_cn', '关于我们');
$page->save();

// Get all translations
$allTitles = $page->getTranslations('title');
// ['en' => 'About Us', 'zh_cn' => '关于我们']
```

---

## URL Strategies

### Prefix Strategy (Recommended)

With `url_strategy => 'prefix'`, locale codes appear in URLs:

| Locale | URL |
|--------|-----|
| English (default) | `/about` (when `hide_default_locale=true`) |
| English (default) | `/en/about` (when `hide_default_locale=false`) |
| Chinese | `/zh-CN/about` |

### Hide Default Locale

When `hide_default_locale => true`:

- Default locale: `/about`, `/blog/my-post`
- Other locales: `/zh-CN/about`, `/zh-CN/blog/my-post`

When `hide_default_locale => false`:

- All locales: `/en/about`, `/zh-CN/about`

### Route Registration

Routes are automatically registered for each locale:

```
GET /                    → Homepage (default locale)
GET /{slug}              → Page (default locale)
GET /zh-CN               → Homepage (Chinese)
GET /zh-CN/{slug}        → Page (Chinese)
```

---

## Admin Panel Usage

### Language Switcher

When i18n is enabled, a language switcher appears in the header of edit pages:

1. Navigate to any Page, Post, Category, or Menu Items page
2. Click the language dropdown in the header
3. Select the desired language
4. Edit the content for that language
5. Save

The selected language persists across all resources via session storage.

### Menu Items Manager

The Menu Items Manager includes full translation support:

1. Go to **Content > Menus**
2. Click **Manage Items** on any menu
3. Use the language switcher in the header
4. Create/edit menu items in the selected language

**Note:** When creating a menu item in a non-default locale, the label is automatically copied to the default locale to prevent blank menus.

### Translation Workflow

1. **Create content in default locale first** - This establishes the base content
2. **Switch to other locales** - Use the language switcher
3. **Translate each field** - The form shows content for the selected locale
4. **Save for each locale** - Changes are saved per-locale

---

## Frontend Components

### Language Switcher Component

Add a language switcher to your theme:

```blade
<x-language-switcher />
```

Or with custom styling:

```blade
<x-language-switcher class="my-custom-class" />
```

The component automatically:
- Shows all available locales with native names
- Highlights the current locale
- Generates correct URLs for the current page in each locale

### Hreflang Tags

Add hreflang tags for SEO:

```blade
{{-- In your layout's <head> --}}
<x-hreflang :model="$page" />
```

This generates:

```html
<link rel="alternate" hreflang="en" href="https://example.com/about" />
<link rel="alternate" hreflang="zh-CN" href="https://example.com/zh-CN/about" />
<link rel="alternate" hreflang="x-default" href="https://example.com/about" />
```

### Current Locale in Templates

```blade
{{-- Get current locale --}}
{{ tallcms_current_locale() }}

{{-- Check if specific locale --}}
@if(tallcms_current_locale() === 'zh_cn')
    {{-- Chinese-specific content --}}
@endif

{{-- Get locale display name --}}
{{ tallcms_locale_label(tallcms_current_locale()) }}
```

---

## Helper Functions

### URL Helpers

```php
// Generate localized URL for a slug
tallcms_localized_url('about');           // /about (default) or /zh-CN/about
tallcms_localized_url('about', 'zh_cn');  // /zh-CN/about

// Resolve custom URL (handles already-prefixed URLs)
tallcms_resolve_custom_url('/about');     // /about
tallcms_resolve_custom_url('/en/about');  // /about (normalizes default locale)
tallcms_resolve_custom_url('/zh-CN/about'); // /zh-CN/about

// Get alternate URLs for all translations
$urls = tallcms_alternate_urls($page);
// ['en' => '/about', 'zh_cn' => '/zh-CN/about']
```

### Locale Helpers

```php
// Check if i18n is enabled
if (tallcms_i18n_enabled()) {
    // Multilingual features available
}

// Get current locale
$locale = tallcms_current_locale(); // 'en' or 'zh_cn'

// Get current page slug (locale-aware)
$slug = tallcms_current_slug(); // 'about'

// Get locale display label
$label = tallcms_locale_label('zh_cn'); // '简体中文'

// Get i18n config value (with DB override support)
$default = tallcms_i18n_config('default_locale', 'en');
```

### Model Helpers

```php
// Get page by localized slug
$page = CmsPage::withLocalizedSlug('about')->first();

// Scope to published content
$page = CmsPage::withLocalizedSlug('about')->published()->first();
```

---

## Technical Architecture

### Database Schema

Translatable fields are stored as JSON columns:

```sql
-- Example: tallcms_pages table
title JSON,        -- {"en": "About", "zh_cn": "关于"}
slug JSON,         -- {"en": "about", "zh_cn": "about"}
content JSON,      -- {"en": "...", "zh_cn": "..."}
```

### Middleware Stack

The `SetLocaleMiddleware` handles locale detection:

1. Check URL prefix (e.g., `/zh-CN/...`)
2. Check session (if `remember_locale` enabled)
3. Check browser `Accept-Language` header
4. Fall back to default locale

### Service Providers

- **TallCmsServiceProvider** - Registers LocaleRegistry, middleware aliases
- **TallCmsPlugin** - Registers SpatieTranslatablePlugin with locale labels

### Key Classes

| Class | Purpose |
|-------|---------|
| `LocaleRegistry` | Centralized locale management |
| `SetLocaleMiddleware` | Detects and sets current locale |
| `HasTranslatableContent` | Trait for translatable models |
| `TranslatableArray` | Cast for translatable JSON fields |
| `LocaleSwitcher` | Filament action for language switching |

---

## Troubleshooting

### Routes Not Registered

If locale-prefixed routes aren't working:

1. Verify i18n is enabled:
   ```bash
   php artisan tinker --execute="echo tallcms_i18n_enabled() ? 'yes' : 'no';"
   ```

2. Clear route cache:
   ```bash
   php artisan route:clear
   ```

3. Check registered routes:
   ```bash
   php artisan route:list --name=tallcms
   ```

### Translations Not Showing

1. Verify the content has translations:
   ```bash
   php artisan tinker --execute="\$p = \TallCms\Cms\Models\CmsPage::first(); dd(\$p->getTranslations('title'));"
   ```

2. Check the current locale:
   ```bash
   php artisan tinker --execute="echo app()->getLocale();"
   ```

### Language Switcher Not Appearing

1. Ensure i18n is enabled in config
2. Ensure more than one locale is configured
3. Check that the SpatieTranslatablePlugin is registered

### 404 on Locale-Prefixed URLs

1. Verify routes are registered (see above)
2. Check `hide_default_locale` setting
3. Ensure the slug exists for that locale

---

## Best Practices

1. **Always create content in the default locale first** - This ensures fallback content exists

2. **Use consistent slugs across locales** - Makes URL mapping predictable

3. **Test with `hide_default_locale` both true and false** - Ensures URLs work in all configurations

4. **Add hreflang tags** - Essential for SEO with multilingual sites

5. **Use native locale names in switchers** - Better UX for international visitors (e.g., "简体中文" not "Chinese")

6. **Consider RTL support** - Set `rtl => true` for Arabic, Hebrew, etc. and handle in CSS
