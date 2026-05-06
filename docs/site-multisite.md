---
title: "Multisite Setup Guide"
slug: "multisite"
audience: "site-owner"
category: "site-management"
order: 70
time: 30
prerequisites:
  - "installation"
  - "site-settings"
---

# Multisite Setup Guide

> **What you'll learn:** How to run multiple sites from one TallCMS install — from a small in-house network of sites you own, all the way to a public self-serve SaaS where visitors sign up and pay for sites.

**Time:** ~30 minutes (single-tenant) to ~2 hours (full SaaS, including Stripe setup)

---

## Overview

The multisite stack is built in layers. Each layer is optional — install only what you need.

| Layer | Plugin | Adds |
|-------|--------|------|
| **1. Multisite** | `tallcms/multisite` | Multiple sites, per-site domains/themes/settings, ownership, plans/quotas |
| **2. Registration** | `tallcms/registration` | Self-serve signup with the `site_owner` role and onboarding redirect |
| **3. Billing** | `tallcms/billing` | Stripe-backed paid plans (Cashier), webhook-driven quota changes |
| **4. Templates** | (built into Multisite) | Site templates a new user can clone on signup |

Pick your destination:

- **Single operator, a few sites** — you only need Layer 1.
- **Internal team, members create their own sites** — Layers 1 + 2.
- **Public SaaS with paid tiers** — Layers 1 + 2 + 3 + (usually) 4.

The rest of this guide walks each layer in order.

---

## Requirements

- TallCMS 4.4.8 or later (Multisite 2.3.x baseline)
- A working TallCMS install with admin access
- DNS control for any domain you want to host
- For Billing: a Stripe account, `laravel/cashier ^15.0` installed in the host app
- For Registration: `tallcms/filament-registration` installed via Composer

---

# Part 1 — Multisite

This is the only layer most installs need. After Part 1 you'll have multiple sites with separate domains, themes, and settings, all managed by a single super-admin.

## 1.1 Activate the Plugin

1. Go to **Admin > Plugins** and install the **TallCMS Multisite** plugin (zip upload or marketplace). The installer runs the plugin's migrations for you.
2. Go to **Admin > Plugin Licenses**, paste your license key for **TallCMS Multisite**, and click **Activate**.

A default site is created automatically from your current domain. The admin navigation reshapes itself: **Pages** and **Menus** disappear from the top level (you'll now reach them through each site's edit page), and a **Sites** group appears.

> If you installed the plugin from the CLI with `--no-migrate`, run `php artisan plugin:migrate tallcms/multisite` to apply them manually.

## 1.2 Create a Site

1. Navigate to **Admin > Sites > Sites**
2. Click **Create**
3. Fill in:
   - **Name** — Display name for this site
   - **Domain** — The domain this site responds to (e.g., `shop.example.com`). Lowercase, no protocol or port.
   - **Locale** — Optional language override (or leave empty for the global locale)
   - **Active** — Toggle to enable/disable the site
4. Click **Create**

After creating the site, point the domain at your server in DNS. Custom domains then need to be [verified](#16-verify-a-custom-domain) before TLS issues.

## 1.3 Manage Site Content

In multisite mode, **Pages** and **Menus** live under each site:

1. Navigate to **Admin > Sites > Sites**
2. Click **Edit** on the site you want to manage
3. Use the **Pages** and **Menus** relation tabs

The **Filter by Site** dropdown in the admin sidebar narrows global content lists to one site for quick browsing.

> **Posts and media are user-owned, not site-owned.** They're a shared library you surface on individual sites through content blocks (Latest Posts, Media Gallery, etc.). One author can write a post once and feature it on multiple sites.

## 1.4 Edit Site Settings

Each site has its own settings, edited directly on the site:

1. Navigate to **Admin > Sites > Sites**
2. Click **Edit** on the site
3. Use the settings tabs (General, Branding, Contact, Social, Publishing, Maintenance)

### How inheritance works

Settings inherit from **Global Defaults** (Admin > Configuration > Global Defaults) unless overridden. When you save a site:

- Values that **match** the global default are stored as no override (the site keeps inheriting).
- Values that **differ** from the global default are stored as an override on that site.
- Changing a value back to match the global default automatically removes the override.

This means the Global Defaults page is your "edit once, applies everywhere" knob — useful for company-wide branding, footer text, contact info, etc. Sites only override what's actually different.

## 1.5 Assign a Theme to a Site

1. Select a site in the **Filter by Site** dropdown
2. Navigate to **Admin > Appearance > Themes**
3. The page subheading shows which site you're managing
4. Click **Activate** on any available theme
5. Optionally select a default **preset** for the site

Each site can use a different theme. The global theme is the fallback for sites without an explicit theme.

## 1.6 Verify a Custom Domain

Custom domains must be verified before TLS certificates are issued. Managed subdomains (e.g., `*.yoursaas.com`) skip this step.

First, configure how verification works:

1. Navigate to **Admin > Configuration > Multisite Settings**
2. Set at least one of:
   - **Server IP Addresses** — One per line. Supports IPv4 and IPv6.
   - **CNAME Target** — The domain users should point a CNAME record to (e.g., `sites.yoursaas.com`).

Then for each site:

1. Open the site's edit page (**Admin > Sites > Sites > Edit**)
2. The **Status** tab shows the DNS instructions
3. At your DNS provider, add the matching A/AAAA record or CNAME
4. Click **Verify Domain** in the page header
5. On success, status moves to **Verified** and TLS provisioning is queued

### Verification statuses

| Status | Meaning |
|--------|---------|
| **Pending** | Domain added but not yet verified |
| **Verified** | DNS confirmed, TLS eligible |
| **Stale** | Re-verification failed once (grace period) |
| **Failed** | Two consecutive re-verification failures, TLS revoked |

### Re-verification

Verified domains are periodically re-checked under **Multisite Settings**:

- **Re-verify Every (Days)** — Minimum 7 days. Set to 0 to disable.
- **Batch Size** — Maximum domains checked per hourly run (default 50, max 500).

Re-verification runs hourly in small batches. Failed re-checks enter **Stale**, then **Failed** on a second consecutive failure (TLS is revoked at that point).

## 1.7 Site Plans & Quotas

Even without billing, plans control how many sites each user can create.

1. Navigate to **Admin > Sites > Site Plans**
2. Create plans with a name, slug, and **Max Sites** (leave empty for unlimited)
3. Toggle **Default Plan** to set which plan new users receive automatically

Super-admins are never quota-limited. If a user is downgraded below their current site count:

- **Existing sites are preserved** — no sites are deleted
- **New site creation is blocked** until they reduce their site count
- The user sees a quota warning on their sites list

You'll wire these plans up to Stripe in Part 3 if you go SaaS.

## 1.8 Site Ownership

Every site has an owner — the user who created it.

- **Super-admins** see all sites and can reassign ownership.
- **Regular users** see only their own sites.
- Creating a site makes you its owner automatically.
- The "All Sites" view is super-admin only.

Ownership is what makes self-serve SaaS safe: each registered user manages only their own sites and can never see another tenant's content.

## 1.9 What's Per-Site vs Global

### Content

| Feature | Scope | Notes |
|---------|-------|-------|
| **Pages** | Per-site | Each site has its own pages with independent slugs and homepage |
| **Menus** | Per-site | Each site has its own navigation (same location names allowed per site) |
| **Posts** | User-owned | Shared library; surfaced on sites through content blocks |
| **Categories** | User-owned | Shared taxonomy used by posts |
| **Media library** | User-owned | Shared uploads; surfaced on sites through media blocks |

### Settings

| Setting Group | Scope |
|---------------|-------|
| **Site name, tagline, description** | Per-site |
| **Contact info** (email, phone, address) | Per-site |
| **Social media links** | Per-site |
| **Branding** (logo, favicon) | Per-site |
| **Maintenance mode** | Per-site |
| **Publishing workflow** | Per-site |
| **"Powered by" badge** | Per-site |
| **Embed code** (head, body) | Per-site |
| **SEO** (RSS, sitemap, robots, llms.txt) | Global |
| **Language settings** (i18n) | Global |

### System

| Feature | Scope |
|---------|-------|
| **Plugins** | Global |
| **Plugin licenses** | Global |
| **User accounts** | Global |
| **Roles & permissions** | Global |

---

# Part 2 — Self-Serve Registration

Add this layer when you want visitors to be able to sign up and create their own site. After Part 2 you'll have a `/register` page that creates users with the `site_owner` role and routes them into the admin to start building.

## 2.1 Install the Registration Plugin

The Registration plugin is a thin bridge over the upstream `tallcms/filament-registration` package, which provides the actual sign-up form, captcha pipeline, and admin settings page.

```bash
composer require tallcms/filament-registration
php artisan migrate
```

Then install the bridge plugin:

1. Go to **Admin > Plugins** and upload the `tallcms-registration-X.Y.Z.zip` from the [plugin's GitHub releases](https://github.com/tallcms/registration-plugin/releases) (or copy into `plugins/tallcms/registration/`).
2. The bridge has no license key — it's MIT.

## 2.2 Wire the Panel Provider

In your panel provider (typically `app/Providers/Filament/AdminPanelProvider.php`), enable registration:

```php
use Tallcms\FilamentRegistration\Filament\Pages\Register;
use Tallcms\Registration\Filament\RegistrationPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->login()
        ->registration(Register::class)          // upstream plugin's page
        ->plugin(RegistrationPlugin::make());    // TallCMS bridge
}
```

Both calls are required: `->registration()` tells Filament which page handles `/admin/register`; `->plugin()` wires the bridge (default role `site_owner`, onboarding redirect, plan assignment).

## 2.3 Confirm the `site_owner` Role Exists

The bridge creates new users with the `site_owner` role. That role must exist in the Spatie roles table before the first registration, or signup will 500.

- **Fresh installs** — seeded automatically by `tallcms:setup`.
- **Existing installs** — `php artisan tallcms:update` syncs it as part of the upgrade.
- **Git-based deploys** that skip `tallcms:update` — run this once after `migrate`:

  ```bash
  php artisan tallcms:shield-sync-site-owner
  ```

The command is idempotent and only touches the `site_owner` role and its permissions.

## 2.4 Configure Captcha (Recommended)

Public registration without captcha is bot-bait. Enable one in **Admin > Settings > Registration**:

- **Cloudflare Turnstile** — free, recommended for most installs
- **reCAPTCHA v3** — alternative if you already use Google services

Captcha keys live in `.env` (see the upstream plugin's README for the full list).

## 2.5 Optional: Email Verification

To require email verification before new users can use the panel, enable it on the panel provider:

```php
$panel
    ->registration(Register::class)
    ->emailVerification(isRequired: fn () => (bool) config('registration.email_verification.enabled'))
    ->plugin(RegistrationPlugin::make());
```

Then set `REGISTRATION_EMAIL_VERIFICATION=true` in `.env` and ensure your `App\Models\User` implements `MustVerifyEmail`. Filament handles the verification prompt and resend automatically.

## 2.6 What Visitors See

After Part 2:

- `/register` (and `/admin/register`) shows the sign-up form.
- New users land in the admin with no sites yet.
- If site templates are configured (Part 4), they're nudged to the Template Gallery on first login.
- They can create up to **Max Sites** on their plan (default plan from Part 1).

---

# Part 3 — Paid Plans with Stripe

Add this layer when you want to charge for higher quotas. The Billing plugin watches Stripe subscription state and assigns the matching `SitePlan` automatically.

## 3.1 Hard Prerequisites

Billing silently no-ops if any of these are missing:

- Multisite plugin installed and migrated (Part 1)
- `laravel/cashier ^15.0` in the host app
- Cashier migrations applied
- `Laravel\Cashier\Billable` trait on `App\Models\User`
- Env vars: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `APP_ENV` is **not** `testing` in production (see warning below)

## 3.2 Install Cashier

```bash
composer require laravel/cashier "^15.0"
php artisan vendor:publish --tag=cashier-config
php artisan vendor:publish --tag=cashier-migrations
php artisan migrate
```

Add the trait to your User model:

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
    // ...
}
```

## 3.3 Set Stripe Env Vars

```dotenv
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=usd
APP_ENV=production
```

> **Critical security note:** `APP_ENV=testing` short-circuits the plugin's license check so its own test suite can run without mocking Anystack. **Never deploy with `APP_ENV=testing`** — it would let unlicensed installs reach checkout.

## 3.4 Create Stripe Products and Prices

In the Stripe Dashboard (live mode):

1. Create one **Product** per paid tier (e.g., "Starter", "Pro", "Business").
2. Under each product, create two **Prices** — monthly recurring and yearly recurring.
3. Note the price IDs (`price_xxx_monthly`, `price_xxx_yearly`).

The free / default tier doesn't need a Stripe product — it's the implicit fallback when a user has no subscription.

## 3.5 Map Stripe Prices to Site Plans

For each paid plan in **Admin > Sites > Site Plans**, edit the plan and populate the metadata fields with your Stripe price IDs:

```
stripe_product_id        = prod_XYZ
stripe_price_id_monthly  = price_1ABC...
stripe_price_id_yearly   = price_1DEF...
price_cents_monthly      = 2900
price_cents_yearly       = 29000
features                 = ["Custom domain", "Priority support"]
```

The keys are fixed conventions — they must match exactly. The free / default plan stays without metadata.

## 3.6 Configure the Stripe Webhook

In Stripe Dashboard → **Developers > Webhooks > Add endpoint**:

- **URL:** `https://your-domain.com/stripe/webhook` (Cashier's default — don't change)
- **Events** (minimum):
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
  - `customer.updated`
- **Signing secret** → copy → set as `STRIPE_WEBHOOK_SECRET` in `.env`.

## 3.7 Configure the Stripe Customer Portal

In Stripe Dashboard → **Settings > Billing > Customer portal**:

- **Subscription updates:** ON
- **Products available for upgrade/downgrade:** include **only** the prices you've recorded in `SitePlan.metadata`. Anything exposed in the portal but unmapped will silently let users switch their Stripe subscription without their plan updating in TallCMS.
- **Cancellation:** ON
- **Payment method update:** ON

> Operator discipline: there's no API check here. Keep the portal's allowed prices in sync with `SitePlan.metadata`.

## 3.8 Install and License the Billing Plugin

1. Buy `tallcms/billing` from Anystack → receive license key.
2. **Admin > Plugins** → upload `tallcms-billing-X.Y.Z.zip` from the [plugin's GitHub releases](https://github.com/tallcms/billing-plugin/releases).
3. **Admin > Plugins > Billing > Activate License** with the Anystack key.
4. Refresh — the **Billing** page appears in the navigation.

## 3.9 Smoke Test in Stripe Test Mode

Switch env vars to Stripe test keys (`pk_test_*` / `sk_test_*`) and a test webhook signing secret, then:

1. Log in as a non-super-admin user with a free site.
2. **Billing > Subscribe to Pro (monthly)** → Stripe Checkout opens.
3. Card `4242 4242 4242 4242`, any future expiry, any CVC, any postcode.
4. Land back on `/admin/billing?checkout=success`.
5. Confirm the user's site plan now matches Pro (visible on **Sites > Site Plans** or in `tallcms_user_site_plans`).
6. Click **Manage Subscription** → Stripe Portal → cancel.
7. Confirm `customer.subscription.deleted` fires (Stripe Dashboard → Webhooks → recent deliveries) and the user's plan reverts to the default.
8. Simulate a failed renewal → confirm the user keeps their plan (past-due grace).

Switch to live keys only after the test-mode flow works end-to-end.

## 3.10 Plan Status Rules

| Stripe subscription status | What happens |
|---|---|
| `active`, `trialing` | User is assigned the paid plan |
| `past_due`, `incomplete` | Plan is **unchanged** — Stripe is retrying the card |
| `canceled`, `unpaid`, `incomplete_expired` | User is downgraded to the default plan |

Past-due users keep portal access during retries. Downgrade only happens once Stripe gives up.

Downgrades **never delete sites**. If a user ends up over quota, existing sites keep working — they just can't create more until they upgrade or remove sites. The Billing page surfaces an over-quota banner.

## 3.11 Recovering from Missed Webhooks

If webhooks fail (CF challenge, wrong URL, signing-secret mismatch), resend the failed events from the Stripe Dashboard once delivery is fixed. Each replay reconciles the user.

For one-off fixes, run:

```bash
php artisan billing:resync {user_id_or_email}
```

This refetches the user's subscriptions from Stripe and applies the same status matrix as the webhook listener.

---

# Part 4 — Site Templates

Templates give brand-new tenants something to start from. After Part 4, a freshly registered user is steered to a Template Gallery instead of an empty admin.

## 4.1 Author a Template

1. Build a regular site with the pages, menus, and styling you want as a starting point.
2. Open the site's edit page and toggle **Is Template Source** on.
3. Categorise it via **Admin > Sites > Template Categories** (Blog, Portfolio, Business, etc.).

The site will then appear in the Template Gallery for new users to clone.

## 4.2 What Cloning Does

When a user picks a template:

- A new site is created under their ownership.
- All pages and menus from the template are copied over with author remapped to the new owner.
- Settings are copied as overrides on the new site (so they can edit freely without touching the template source).
- The site count counts toward their plan's `max_sites` quota.

## 4.3 Onboarding Behaviour

With Registration v1.2.0+ and Multisite both installed:

- New users with no sites and no over-quota state land on the Template Gallery instead of the panel home.
- Once they own at least one site, the redirect stops firing.

You can override the redirect target with `REGISTRATION_ONBOARDING_REDIRECT_URL=/somewhere`, or disable the off-ramp with `REGISTRATION_ONBOARDING_ENABLED=false`.

---

## Common Pitfalls

**"404 when visiting my new site's domain"**
The domain must be configured in DNS to point to your server. The domain must also be added to a site and the site must be active.

**"Verify Domain says 'not configured'"**
Go to **Admin > Configuration > Multisite Settings** and enter your server IP or CNAME target.

**"Domain shows as Stale or Failed"**
DNS no longer points where it should. Update DNS and click **Verify Domain** to re-check.

**"Theme doesn't change on the frontend"**
Select the correct site in the **Filter by Site** dropdown before activating the theme. Check the page subheading on the Theme Manager.

**"Changes to Global Defaults not showing on a site"**
The site has an override for that setting. Edit the site and change the value back to match the global default — the override will be removed and the site will inherit again.

**"Authors can't publish pages"**
Check the **Publishing** tab on the site's settings. If the Review Workflow toggle is on, authors can only save drafts. Turn it off to let all users publish directly.

**"Can't find Pages or Menus in the navigation"**
In multisite mode, Pages and Menus are accessed through the Site resource. Navigate to **Admin > Sites > Sites**, edit a site, then use the Pages and Menus tabs.

**"Registration returns 500"**
The `site_owner` role is missing. Run `php artisan tallcms:shield-sync-site-owner` and retry.

**"User paid in Stripe but quota didn't update"**
A webhook didn't reach you. Check **Stripe Dashboard > Webhooks > recent deliveries** for failures, then either resend the failed event or run `php artisan billing:resync {user}`.

**"Users can change their Stripe subscription but TallCMS plan doesn't follow"**
The Customer Portal is exposing prices that aren't in `SitePlan.metadata`. Trim the portal's allowed products to match.

**"Plugin pages don't appear after install"**
For Billing: confirm `APP_ENV` is not `testing`, the license is activated, Cashier is installed, and `Billable` is on the User model. The plugin silently no-ops if any prereq fails.

---

## Next Steps

- [Multisite Architecture](multisite-architecture) — Developer reference for site resolution, scoping, and settings internals
- [Billing Plugin (Stripe)](billing) — Deeper Stripe runbook with monitoring guidance
- [Theme Development](themes) — Build themes that work cleanly across multiple sites
- [Plugin Development](plugins) — Build plugins compatible with multisite
- [Site Settings](site-settings) — Detailed settings reference
