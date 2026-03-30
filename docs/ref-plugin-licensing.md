---
title: "Plugin Licensing"
slug: "plugin-licensing"
audience: "developer"
category: "developers"
order: 25
prerequisites:
  - "plugins"
---

# Plugin Licensing

> **What you'll learn:** How licensing works for TallCMS plugins and themes, what the CMS handles automatically, and how to implement your own licensing as a 3rd party developer.

---

## How It Works

TallCMS has a built-in license activation system for **official plugins** (those published by TallCMS with `vendor: tallcms`). This system uses a license proxy hosted on tallcms.com that communicates with an upstream license provider.

Official **themes** use a different model — their downloads are gated by the license provider (Anystack), so no post-install activation is needed. Theme licensing is handled at purchase/download time, not within the CMS.

**If you're a 3rd party developer, the built-in activation system is not available to you.** You'll need to implement your own licensing within your plugin's service provider. This guide explains both paths.

---

## Official vs 3rd Party Licensing

| | Official (tallcms vendor) | 3rd Party (your vendor) |
|---|---|---|
| **License activation UI** | Built-in — CMS shows "Activate" button in Plugin Manager | Not available |
| **License validation** | Handled by TallCMS proxy | Your responsibility |
| **Update gating** | Proxy checks license before serving download URL | Your responsibility |
| **Marketplace listing** | Managed by TallCMS team | Submit via tallcms.com for review |

The CMS determines whether to show the built-in activation UI based on the **marketplace catalog**, not your `plugin.json`. Even if you set `license_required: true` in your `plugin.json`, the CMS will not show the activation button for non-official plugins.

---

## For 3rd Party Developers

### Selling Your Plugin

1. **List on the TallCMS Marketplace** — submit your plugin via tallcms.com. Set a price, add download/purchase URLs pointing to your own site or payment provider.
2. **Gate the download** — use your own payment processor (Gumroad, Lemonsqueezy, Paddle, etc.) to gate access to the ZIP file. Users purchase → download ZIP → upload via Plugin Manager.
3. **Optionally enforce a license** — if you want post-install license validation, implement it in your plugin's service provider.

### Implementing Your Own License Check

Add license validation in your service provider's `boot()` method. Here's a pattern:

```php
namespace YourVendor\YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Skip license check in local/testing environments
        if (app()->environment('local', 'testing')) {
            return;
        }

        if (! $this->isLicenseValid()) {
            // Option A: Disable features silently
            return;

            // Option B: Show admin notice
            // $this->showLicenseNotice();
        }

        // Register your plugin's features here
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'your-plugin');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function isLicenseValid(): bool
    {
        // Implement your own validation logic:
        // - Check a locally stored license key
        // - Validate against your own API
        // - Check an expiry date
        // - Verify a signed token

        $key = config('your-plugin.license_key');
        if (empty($key)) {
            return false;
        }

        // Example: validate against your API (cache the result)
        return cache()->remember("your_plugin_license_{$key}", 86400, function () use ($key) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->post('https://your-api.com/validate', [
                        'key' => $key,
                        'domain' => request()->getHost(),
                    ]);

                return $response->successful() && ($response->json('valid') === true);
            } catch (\Throwable $e) {
                // Fail open on network errors — don't break the site
                return true;
            }
        });
    }
}
```

### Adding a License Key Setting

Create a config file so users can set their license key:

```php
// your-plugin/src/config.php
return [
    'license_key' => env('YOUR_PLUGIN_LICENSE_KEY'),
];
```

Register it in your service provider:

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__.'/../config.php', 'your-plugin');
}
```

Users add `YOUR_PLUGIN_LICENSE_KEY=xxxx` to their `.env` file.

### Adding a Filament Settings Page

For a better UX, add a settings page in the admin panel where users can enter their license key:

```php
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;

class YourPluginSettings extends Page
{
    public string $license_key = '';

    public function mount(): void
    {
        $this->license_key = config('your-plugin.license_key', '');
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('license_key')
                ->label('License Key')
                ->placeholder('Enter your license key')
                ->helperText('Get your key from your purchase email'),
        ];
    }

    public function save(): void
    {
        // Save to .env, database, or your preferred storage
    }
}
```

### Best Practices

- **Fail open on network errors** — if your validation API is unreachable, let the plugin work. Don't break customer sites because your server is down.
- **Cache validation results** — don't hit your API on every page load. Cache for 24 hours.
- **Allow local/testing environments** — skip license checks in `local` and `testing` environments.
- **Degrade gracefully** — disable premium features instead of throwing errors when unlicensed.
- **Don't obfuscate** — the TallCMS community values open, readable code. Use licensing for business protection, not code hiding.

---

## Marketplace Catalog

The TallCMS marketplace at tallcms.com serves as the catalog for available plugins and themes. Every TallCMS installation fetches this catalog to show the "From the Marketplace" section in the Plugin Manager and Theme Manager.

### Getting Listed

Submit your plugin or theme for review at tallcms.com. Once approved, it appears in every TallCMS installation's admin panel. Your listing includes:

- Name, description, author, version
- Screenshot and gallery images
- Download URL (your site or payment provider)
- Purchase URL (your checkout page)
- Category tags (SEO, E-commerce, Analytics, etc.)
- Price and pricing model

### Catalog Fields That Affect Behavior

| Field | Effect on CMS |
|-------|---------------|
| `is_paid` | Informational — shown in marketplace UI |
| `requires_license` | **Only affects official plugins.** When `true`, the CMS shows the built-in license activation UI in the Plugin Manager for plugins with `vendor: tallcms`. Has no effect on themes or 3rd party plugins — set to `false` for those. |
| `download_url` | "Download" button target |
| `purchase_url` | "Purchase" button target |

---

## plugin.json Reference

These fields in your `plugin.json` are relevant to licensing:

```json
{
    "name": "Your Plugin",
    "slug": "your-plugin",
    "vendor": "your-vendor",
    "license_required": false
}
```

| Field | Description |
|-------|-------------|
| `vendor` | Your vendor name. Only `tallcms` vendor plugins use the built-in license proxy. |
| `license_required` | Set to `true` if your plugin requires licensing. For official plugins (`vendor: tallcms`), this serves as a **fallback only when the marketplace catalog is unreachable** — the catalog is the primary source of truth. For 3rd party plugins, the CMS does not act on this field; implement your own licensing instead. |

---

## Common Pitfalls

**"Plugin Not Supported" when activating**
The built-in activation system only works for official TallCMS plugins. If you're a 3rd party developer, implement your own licensing (see above).

**License check breaks the site when your API is down**
Always fail open on network errors. Cache validation results and don't block the boot process on a failed HTTP call.

**Users can't enter a license key**
Provide a config file with an env variable, or add a Filament settings page. Don't expect users to edit PHP files.

---

## Next Steps

- [Plugin development guide](plugins) — full guide to building plugins
- [Theme development guide](themes) — building themes for TallCMS
