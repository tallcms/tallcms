---
title: "Email Verification & Email Change Verification"
slug: "email-verification"
audience: "developer"
category: "reference"
order: 55
prerequisites:
  - "installation"
  - "roles-authorization"
---

# Email Verification & Email Change Verification

> **What you'll learn:** How to enable Filament-native email verification — both at registration and on email change — using `REGISTRATION_EMAIL_VERIFICATION` as a single opt-in toggle.

---

## When you need this

Enable email verification when:

- You allow public signup (e.g. via the [Registration plugin](dev-plugins) or your own controller) and want to confirm addresses are real before granting panel access.
- You want changes to a user's email address to be confirmed by both the new and old address (Filament's email-change verification sends a security email to the old address with a "block this change" link).

You **don't** need this if:

- Your install is a content CMS with admin-created users only and no public signup. Leave `REGISTRATION_EMAIL_VERIFICATION` unset and nothing changes.

---

## Architecture

The wiring is intentionally minimal and Filament-native:

| Layer | What it does |
|---|---|
| `User implements MustVerifyEmail` | Activates Laravel's stock `SendEmailVerificationNotification` listener on the `Registered` event |
| `User implements FilamentUser` + `canAccessPanel()` | App-level safety net — rejects unverified users when the flag is on |
| `->emailVerification(isRequired: …)` | Adds Filament's `verified` middleware to authenticated panel routes when the flag is on |
| `->emailChangeVerification(…)` | Activates the email-change confirmation flow on Filament's profile page |
| `config('registration.email_verification.enabled')` | Single source of truth — bound to `REGISTRATION_EMAIL_VERIFICATION` env |

Filament's middleware bounces unverified users to the verification-notice page; `canAccessPanel()` is an additional safety net for any path that bypasses middleware (console-impersonated session, future panel changes).

---

## Setup recipe

The standalone skeleton ships with this wiring already in place. If you're upgrading an existing install or wiring a plugin-mode host (an existing Filament app that pulls in `tallcms/cms` via Composer), the same recipe applies.

### Step 1: User model

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        if (! ($this->is_active ?? true)) {
            return false;
        }

        // First-user role/setup safety net — NOT an email-verification bypass.
        // Setup marks the first user verified at install time; this short-circuit
        // covers the role-less case on a fresh install. Filament's `verified`
        // middleware still gates unverified users at the route layer.
        if ($this->isFirstUser()) {
            return true;
        }

        if (config('registration.email_verification.enabled') && ! $this->hasVerifiedEmail()) {
            return false;
        }

        return $this->roles->isNotEmpty();
    }
}
```

### Step 2: Panel provider

```php
return $panel
    ->login()
    ->passwordReset()
    ->emailVerification(isRequired: fn () => (bool) config('registration.email_verification.enabled'))
    ->emailChangeVerification(fn () => (bool) config('registration.email_verification.enabled'))
    ->profile(isSimple: false)
    // … rest of panel config
;
```

> **Signature gotcha.** `->emailVerification()` accepts a named `isRequired:` argument. `->emailChangeVerification()` accepts a single **positional** `$condition` parameter — passing `isRequired:` to it raises an "unknown named parameter" error. Verified against `vendor/filament/filament/src/Panel/Concerns/HasAuth.php` lines 110, 118.
>
> **`->profile()` is required for email change.** Filament's email-change-verification flow is triggered by the built-in `EditProfile` page. Without `->profile(isSimple: false)`, `->emailChangeVerification()` only registers routes — there's no user-facing surface to invoke them.

### Step 3: Config fallback

If your install doesn't have the registration plugin installed, ship a tiny `config/registration.php` so the env var still gates everything:

```php
<?php

return [
    'email_verification' => [
        'enabled' => env('REGISTRATION_EMAIL_VERIFICATION', false),
    ],
];
```

The registration plugin uses `mergeConfigFrom`, so installing it later won't override your host config — the plugin's CAPTCHA / setting keys merge in alongside.

### Step 4: Mail driver

The flow is useless without a working mailer. `MAIL_MAILER=log` silently swallows verification emails into `storage/logs/laravel.log`. Switch to a real driver before flipping the env:

```env
# Local/staging
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
# …

# Production
MAIL_MAILER=postmark
POSTMARK_TOKEN=…
```

### Step 5: Pre-flight (mandatory before opt-in)

**5a. Backfill unverified users.** Filament's middleware will lock out anyone with `email_verified_at = NULL` — including admin-created users (Filament's `UserResource` does not set the timestamp by default).

```bash
php artisan tinker
>>> App\Models\User::whereNull('email_verified_at')->get()->each->markEmailAsVerified();
```

If you have the registration plugin installed:

```bash
php artisan tallcms:registration-backfill-verified
```

**5b. Audit role-less users.** `canAccessPanel()` requires a role. Anyone currently reaching the panel without a role assigned will be locked out.

```bash
php artisan tinker
>>> App\Models\User::doesntHave('roles')->pluck('email')
```

Backfill roles or accept the lockouts.

### Step 6: Flip the switch

```env
REGISTRATION_EMAIL_VERIFICATION=true
```

```bash
php artisan optimize:clear
```

---

## Custom signup code

If your install fires `event(new Registered($user))` from your own code — custom registration controllers, OAuth handlers, import scripts — adding `MustVerifyEmail` to the User contract activates Laravel's stock `SendEmailVerificationNotification` listener.

If verification is intentionally **off** on that install, the host must either:

- (a) **not fire `Registered`** for those users, or
- (b) call `$user->markEmailAsVerified()` **before** firing the event.

The registration plugin already handles this correctly:

```php
// Tallcms\Registration\Http\Controllers\RegisterController
$user = User::create([...]);

if (! config('registration.email_verification.enabled')) {
    $user->markEmailAsVerified();
}

event(new Registered($user));
```

Other code paths don't get this for free.

---

## How it works at runtime

### New-user verification (with flag on)

1. User registers (e.g. via the registration plugin's `/register` form).
2. `User::create()` → no `markEmailAsVerified()` call (because flag is on).
3. `event(new Registered($user))` → Laravel's listener sends `VerifyEmail` notification.
4. The verification email points at `/{panel}/email-verification/verify/...` (the registration plugin re-routes the URL through Filament's panel route).
5. User clicks the link → email is verified, redirected into the panel.
6. If user tries to log in before clicking, Filament's `verified` middleware bounces them to the verification-notice page.

### Email change (with flag on)

1. User edits their email on the Filament profile page (`->profile()` must be enabled).
2. Filament sends a confirmation email to the **new** address with a 60-minute confirmation link.
3. Filament sends a security email to the **old** address with a "block this change" link.
4. The DB email is **not** updated until the new address confirms.
5. The new address can confirm OR the old address can block. Either action invalidates the other.

---

## Backwards compatibility

When `REGISTRATION_EMAIL_VERIFICATION` is unset/false, every change above is a no-op:

| Change | Effect when flag is off |
|---|---|
| `MustVerifyEmail` contract | Inert. Only triggers via `verified` middleware or `event(Registered)`. Neither fires in admin-created-user flows. |
| `->emailVerification(isRequired: fn …)` | Closure returns false → no `verified` middleware on panel routes. |
| `->emailChangeVerification(fn …)` | Closure returns false → email-change confirmation flow disabled. |
| `canAccessPanel()` verification check | Wrapped in `if (config(…))` — short-circuits when flag is off. |

**Existing users with `email_verified_at = NULL` continue logging in unchanged when the flag is off.**

---

## Plugin-mode hosts

If you're building on top of `tallcms/cms` via Composer rather than the standalone skeleton, you own your own User model and panel provider — drop the same wiring in. The `config/registration.php` fallback file works the same way; the registration plugin is install-via-marketplace if you want public signup.

---

## Common pitfalls

**"I enabled verification but no email arrives."**
Check `MAIL_MAILER` (`php artisan about`). If it's `log`, the email is in `storage/logs/laravel.log`.

**"Users are locked out after I flipped the env."**
You skipped step 5a (backfill). Run the backfill, then re-test login.

**"I added `->emailChangeVerification(isRequired: …)` and got a fatal error."**
That method takes a positional `$condition`, not a named `isRequired:`. Drop the keyword: `->emailChangeVerification(fn () => …)`.

**"I added `->emailChangeVerification()` but profile email changes still happen instantly."**
You're missing `->profile(isSimple: false)`. The Filament `EditProfile` page is what invokes the email-change flow.

**"Custom registration code is sending unwanted verification emails after I added `MustVerifyEmail`."**
See [Custom signup code](#custom-signup-code). Either skip the `Registered` event for those users or call `markEmailAsVerified()` first.

---

## Next steps

- [Roles & Authorization](roles-authorization)
- [Plugin development](plugins)
