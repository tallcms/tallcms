---
title: "Email Marketing Integration"
slug: "pro-email-marketing"
audience: "site-owner"
category: "reference"
order: 80
prerequisites:
  - "installation"
  - "site-settings"
---

# Email Marketing Integration

> **What you'll learn:** How to connect your TallCMS contact forms to Mailchimp, ConvertKit, or Brevo so leads are automatically added to your email list.

**Requires:** TallCMS Pro plugin (v1.7.0+)

---

## Overview

The TallCMS Pro plugin connects your contact forms to email marketing platforms. When a visitor submits a **Contact Form block** or **Hero block embedded form**, their email is automatically synced to your marketing list in the background.

- No Zapier or third-party middleware needed
- No code or webhook configuration
- Syncs run as background jobs — form submissions are never delayed
- Failed syncs retry automatically (3 attempts with increasing backoff)
- Works with multisite: each site can have its own provider and list

---

## Supported Providers

| Provider | Credentials needed |
|----------|-------------------|
| **Mailchimp** | API Key + Audience ID |
| **ConvertKit** | API Key + Form ID |
| **Brevo (Sendinblue)** | API Key + List ID |

---

## 1. Configure Your Provider

1. Navigate to **Admin > Settings > Pro Settings**
2. Select the **Email Marketing** tab
3. Choose your provider and enter your credentials:

### Mailchimp

- **API Key**: Found in Mailchimp > Account > Extras > API keys. Looks like `abc123def456-us1`.
- **Audience ID**: Found in Mailchimp > Audience > Settings > Audience name and defaults. Looks like `a1b2c3d4e5`.

### ConvertKit

- **API Key**: Found in ConvertKit > Settings > Advanced > API.
- **Form ID**: The numeric ID from the form's URL in ConvertKit (e.g., the `12345` in `https://app.convertkit.com/forms/12345`).

### Brevo (Sendinblue)

- **API Key**: Found in Brevo > Settings > SMTP & API > API Keys.
- **List ID**: The numeric ID of the contact list you want subscribers added to.

4. Click **Save**

That's it. Contact form submissions now sync to your list automatically.

---

## 2. How It Works

When a visitor submits a form:

1. TallCMS saves the contact submission to the database (name, email, form data)
2. The Pro plugin dispatches a **queued job** to sync the contact
3. The job calls the provider's API to add the subscriber
4. If the API call fails, the job retries up to 3 times (after 30s, 120s, then 300s)
5. Permanently failed jobs are logged to `failed_jobs` for review

The form submission itself is **never blocked** by the email sync. Even if the provider API is down, the visitor sees a successful submission. The sync happens in the background.

---

## 3. Queue Setup

Email marketing sync runs as a **Laravel queued job**. How it processes depends on your queue driver.

### Development (default)

TallCMS ships with the `sync` queue driver, which processes jobs immediately during the request. This works but means the form submission waits for the API call to complete.

```env
# .env
QUEUE_CONNECTION=sync
```

No additional setup needed — but form submissions will be slightly slower.

### Production (recommended)

For production, use the `database` or `redis` queue driver so jobs run in the background.

**Step 1: Set the queue driver**
```env
# .env
QUEUE_CONNECTION=database
```

**Step 2: Create the jobs table** (if using `database` driver for the first time)
```bash
php artisan queue:table
php artisan migrate
```

**Step 3: Start a queue worker**
```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

**Step 4: Keep the worker running** with Supervisor or your hosting panel:
```ini
[program:tallcms-queue]
command=php /path/to/tallcms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/tallcms-queue.log
```

If you use **Laravel Forge**, **Ploi**, or **Herd Pro**, they have built-in queue worker management — you don't need to configure Supervisor manually.

---

## 4. Multisite

On multisite installations, email marketing is **per-site configurable**. Each site with a Pro license can have its own provider and list:

| Site | Provider | List |
|------|----------|------|
| main.example.com | Mailchimp | "Main Newsletter" |
| shop.example.com | ConvertKit | "Shop Leads" |
| blog.example.com | (no Pro license) | Uses global default |

To configure per-site:

1. Select a site in the **admin site switcher**
2. Go to **Pro Settings > Email Marketing**
3. Configure the provider and credentials for that site
4. Click **Save** — the subheading confirms you're editing site-specific settings

To remove per-site settings and fall back to the global default, click **Reset to Global Defaults**.

Sites without a Pro license use the global configuration automatically.

---

## 5. What Gets Synced

When a contact is synced, the following data is sent to your provider:

| Field | Mailchimp | ConvertKit | Brevo |
|-------|-----------|------------|-------|
| **Email** | `email_address` | `email` | `email` |
| **First name** | `merge_fields.FNAME` | `first_name` | `attributes.FIRSTNAME` |
| **Last name** | `merge_fields.LNAME` | — | `attributes.LASTNAME` |

Names are split from the submission's `name` field (first word → first name, remainder → last name).

If a contact already exists in your list, the behavior depends on the provider:
- **Mailchimp**: Returns "Member Exists" — treated as success (not an error)
- **ConvertKit**: Updates the existing subscriber
- **Brevo**: Updates the existing contact (`updateEnabled: true`)

---

## Troubleshooting

### Contacts not appearing in my list

1. **Check your queue worker** — is it running?
   ```bash
   php artisan queue:work
   ```
2. **Check for failed jobs:**
   ```bash
   php artisan queue:failed
   ```
3. **Retry failed jobs:**
   ```bash
   php artisan queue:retry all
   ```
4. **Verify credentials** — make sure the API key and list/form ID are correct in Pro Settings
5. **Check the log** — look in `storage/logs/laravel.log` for "Email marketing sync failed" warnings

### Form submissions are slow

You're using the `sync` queue driver. Switch to `database` or `redis`:
```env
QUEUE_CONNECTION=database
```
Then start a queue worker (see [Queue Setup](#3-queue-setup) above).

### Works on one site but not another (multisite)

- Each site needs its own **Pro license** for per-site email marketing settings
- Unlicensed sites use the **global** email marketing configuration
- Check which provider is configured by selecting the site in the admin switcher and viewing Pro Settings

### "Email marketing sync failed" in logs

This means the provider API returned an error. Common causes:
- **Invalid API key** — regenerate and update in Pro Settings
- **Invalid list/form ID** — verify the ID exists in your provider's dashboard
- **Rate limiting** — the job will retry automatically (3 attempts)
- **Account suspended** — check your provider account status

---

## Next Steps

- [Contact Form block](block-contact-form) — Set up contact forms on your pages
- [Hero block](block-hero) — Add an embedded contact form to your hero section
- [Pro plugin changelog](pro-plugin-changelog) — See what's new in each release
