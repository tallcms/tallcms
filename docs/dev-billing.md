---
title: "Billing Plugin (Stripe via Cashier)"
slug: "billing"
audience: "developer"
category: "developers"
order: 35
prerequisites:
  - "multisite"
  - "plugins"
---

# TallCMS Billing — Production Setup

> **What you'll learn:** How to connect Stripe to a TallCMS multisite install via the `tallcms/billing` plugin, how Stripe products/prices map to `SitePlan` rows, and what to verify before pointing live keys at production.

This is the operator runbook for production deployment. The full plugin reference (status matrix, behaviour notes, edge cases) lives in the [plugin README](https://github.com/tallcms/tallcms-billing-plugin#readme) — this page focuses on the deployment sequence.

---

## Architecture in one paragraph

The Billing plugin watches Cashier's `WebhookReceived` event. When a Stripe subscription state changes, the listener (`SyncSitePlanFromStripe`) refetches the subscription from Stripe, looks up the price ID in `SitePlan.metadata`, and reassigns the user's `tallcms_user_site_plans.site_plan_id` to the matching plan. The plan's `max_sites` quota is then enforced by the multisite plugin's existing `SitePlanService` — Billing doesn't add new quota logic, it just changes which plan the user is on.

---

## Hard prerequisites

- `tallcms/multisite` plugin installed and migrated.
- `laravel/cashier ^15.0` installed in the host app.
- Cashier migrations applied (`stripe_id` columns on `users`, `subscriptions` and `subscription_items` tables).
- `Laravel\Cashier\Billable` trait on the host's `App\Models\User`.
- Env vars set: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`.
- `APP_ENV` is **not** `testing` in production. (See "Critical security note" below.)

If any of these are missing, the plugin's `BillingGate` returns false from all three call sites (service provider boot, Filament register, route middleware) and the plugin silently no-ops.

---

## 1. Stripe products + prices

In the Stripe Dashboard (live mode):

1. Create one **Product** per paid plan tier — e.g. "Starter", "Pro", "Business".
2. Under each product, create two **Prices** — one monthly recurring, one yearly recurring.
3. Note each price ID (`price_xxx_monthly`, `price_xxx_yearly`).

The Free / default tier doesn't need a Stripe product — it's the implicit fallback when a user has no subscription.

## 2. Cashier setup

```bash
composer require laravel/cashier "^15.0"
php artisan vendor:publish --tag=cashier-config
php artisan vendor:publish --tag=cashier-migrations
php artisan migrate
```

Add the `Billable` trait to `App\Models\User`:

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
    // ...
}
```

## 3. Environment variables

```dotenv
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en
APP_ENV=production
```

> **Critical security note**: `BillingGate::isLicensed()` short-circuits to `true` when `APP_ENV=testing` so the plugin's own test suite can run without mocking Anystack. **Never deploy with `APP_ENV=testing`** — it would let unlicensed installs reach checkout and portal endpoints.

## 4. SitePlan metadata

For each Stripe-paid `SitePlan` in `tallcms_site_plans`, populate the `metadata` JSON column with the Stripe price IDs:

```php
$plan = SitePlan::where('slug', 'pro')->first();
$plan->metadata = [
    'stripe_product_id'        => 'prod_XXXX',
    'stripe_price_id_monthly'  => 'price_XXXX_monthly',
    'stripe_price_id_yearly'   => 'price_XXXX_yearly',
    'price_cents_monthly'      => 2900,
    'price_cents_yearly'       => 29000,
    'features'                 => ['10 sites', 'Custom domains', 'Priority support'],
];
$plan->save();
```

The exact key names are fixed conventions defined as `Tallcms\Billing\Support\PlanResolver::META_*` constants. Plugins are forbidden from shipping a `config/` directory, so these are inlined in the source rather than operator-overridable. The keys must match exactly.

## 5. Stripe webhook

In Stripe Dashboard → **Developers → Webhooks → Add endpoint**:

- **URL**: `https://your-domain.com/stripe/webhook` (Cashier's default — don't change).
- **Events** (minimum):
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
  - `customer.updated`
- **Signing secret** → copy → set as `STRIPE_WEBHOOK_SECRET` in `.env`.

## 6. Stripe Customer Portal

In Stripe Dashboard → **Settings → Billing → Customer portal**:

- **Subscription updates**: ON.
- **Products available for upgrade/downgrade**: include **only** the prices you've recorded in `SitePlan.metadata`. Anything exposed in the Portal but unmapped in metadata will silently let a user change their Stripe subscription without their `UserSitePlan` updating — quota mismatch with no error. **This is operator discipline; there's no API check.**
- **Cancellation**: ON.
- **Payment method update**: ON.

## 7. Plugin install + license

1. Buy `tallcms/billing` from Anystack → receive license key.
2. TallCMS admin → **Plugin Manager** → upload the `tallcms-billing-X.Y.Z.zip` from the [plugin's GitHub releases](https://github.com/tallcms/tallcms-billing-plugin/releases).
3. **Plugins → Billing → Activate License** with the Anystack key.
4. Refresh the admin — the **Billing** page appears in the navigation.

## 8. Smoke test (in test mode first)

Switch env vars to Stripe test keys (`pk_test_*` / `sk_test_*`) + test webhook signing secret, then:

1. Log in as a non-super-admin user with a Free site.
2. **Billing** → click "Subscribe to Pro (monthly)" → Stripe Checkout opens.
3. Use card `4242 4242 4242 4242`, any future expiry, any CVC, any postcode.
4. Land on `/admin/billing?checkout=success`.
5. Verify `tallcms_user_site_plans.site_plan_id` for the user matches the Pro plan id.
6. Click "Manage Subscription" → Stripe Portal opens → cancel the subscription.
7. Confirm `customer.subscription.deleted` webhook fires (Stripe Dashboard → Webhooks → recent deliveries) and the user's site plan reverts to the default.
8. Simulate a failed renewal in Stripe Dashboard → confirm `invoice.payment_failed` webhook arrives, the listener handles it, and the user's plan stays put (past-due grace per the [status matrix](https://github.com/tallcms/tallcms-billing-plugin#behaviour-notes)).

Switch to live keys + live webhook only after the test-mode flow works end-to-end.

---

## Things to monitor in production

- **Webhook delivery health** — Stripe Dashboard → Webhooks → your endpoint → recent deliveries. Anything stuck retrying means `SyncSitePlanFromStripe` is throwing.
- **Cashier `subscriptions` table** drift vs Stripe — if Cashier's view of a subscription disagrees with Stripe's, a webhook was missed.
- **`tallcms_user_site_plans` mismatches with Stripe** — if a user shows an active paid subscription in Stripe but the site_plan_id is still the default, the listener didn't run or threw. Check `storage/logs/laravel.log` for `Tallcms\Billing` entries.
- **License expiry on `tallcms/billing`** — Anystack license expiry doesn't disable the plugin (`hasEverBeenLicensed()` short-circuits to true). To fully deactivate, deactivate the license in the Filament Plugins page. Same model as Pro.

## Status matrix (when site plans change)

| Stripe subscription status | UserSitePlan action |
|---|---|
| `active`, `trialing` | Assign paid plan |
| `past_due`, `incomplete` | Leave unchanged (Stripe is retrying the card) |
| `canceled`, `unpaid`, `incomplete_expired` | Downgrade to default |

Past-due users keep their plan and Portal access during retries — removing quota mid-Smart-Retry would be a worse UX than letting them keep the plan for a few days. Eventual `canceled` arrives only if all retries fail.

## Troubleshooting

### `Route [filament.admin.pages.billing] not defined`

Symptom: 500 error after clicking a plan button. Log shows `RouteNotFoundException` with the offending route name.

Cause: your Filament panel uses a non-default panel ID (e.g. `'app'` from `make:filament-panel app`, or any multi-panel install). The Billing plugin v1.0.1 and earlier hardcoded `'admin'` as the panel ID via the cms's `tallcms_panel_route()` helper.

Fix: **upgrade the plugin to v1.0.2 or later** — that release switched all three URL-generation call sites (checkout success, checkout cancel, portal return) to Filament's own `Billing::getUrl()`, which auto-resolves the panel from where the page was actually registered.

If you can't upgrade immediately, the workaround is to set in `.env`:

```
TALLCMS_PANEL_ID=app   # or whatever your panel id is
```

Then `php artisan config:clear`. This makes the v1.0.1 helper resolve correctly.

## Out of scope (v1.0.x)

- Promo codes / coupons
- Stripe Tax / VAT collection
- Stripe Connect for marketplace-style multi-tenancy
- Per-team / per-user billing (current model is one subscription per User)
- Custom invoice details, receipt email customisation
- Public marketing pricing page, billing audit log, `billing:verify` command, custom-domain feature gate

## Reference

- [Plugin README](https://github.com/tallcms/tallcms-billing-plugin#readme) — status matrix details, behaviour notes, test bucket organisation.
- [Multisite Architecture](multisite-architecture) — how SitePlan / UserSitePlan / SitePlanService work, which the Billing plugin sits on top of.
- [Plugin Development](plugins) — general TallCMS plugin lifecycle.
