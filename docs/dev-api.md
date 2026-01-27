---
title: "REST API Development"
slug: "api"
audience: "developer"
category: "developers"
order: 40
prerequisites:
  - "installation"
---

# REST API Development

> **What you'll learn:** How the TallCMS REST API is architected, how to authenticate, and how to extend it.

For detailed endpoint documentation, see the [OpenAPI docs](/api/docs).

---

## Overview

TallCMS provides a full REST API for headless CMS usage. The API follows JSON:API conventions and supports:

| Feature | Description |
|---------|-------------|
| **Authentication** | Laravel Sanctum token-based auth |
| **Authorization** | Filament Shield permissions + token abilities |
| **Resources** | Pages, Posts, Categories, Media, Webhooks |
| **i18n** | Per-locale or multi-locale read/write |
| **Soft Deletes** | Pages and Posts support trash/restore |
| **Webhooks** | Event-driven notifications with retry logic |

---

## Architecture

### Base URL

```
/api/v1/tallcms
```

### Route Structure

```
packages/tallcms/cms/
├── routes/
│   └── api.php                    # All API routes
├── src/Http/
│   ├── Controllers/Api/V1/
│   │   ├── Controller.php         # Base with response helpers
│   │   ├── AuthController.php
│   │   ├── PageController.php
│   │   ├── PostController.php
│   │   ├── CategoryController.php
│   │   ├── MediaController.php
│   │   ├── MediaCollectionController.php
│   │   └── WebhookController.php
│   ├── Middleware/
│   │   ├── CheckTokenExpiry.php
│   │   └── CheckTokenAbilities.php
│   ├── Requests/Api/V1/           # Form request validation
│   └── Resources/Api/V1/          # JSON transformers
```

### Controller Concerns

Controllers use shared traits for common functionality:

| Trait | Purpose |
|-------|---------|
| `HandlesFiltering` | Query parameter filtering with allowlist |
| `HandlesSorting` | Sort field validation |
| `HandlesIncludes` | Eager loading + `withCount` |
| `HandlesPagination` | Page/per_page with max limit |
| `HandlesLocale` | i18n response formatting |

---

## Authentication

### Token Creation

```bash
curl -X POST /api/v1/tallcms/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "secret",
    "device_name": "My App",
    "abilities": ["pages:read", "posts:read"]
  }'
```

Response:

```json
{
  "data": {
    "token": "1|abc123...",
    "expires_at": "2027-01-27T10:30:00Z",
    "abilities": ["pages:read", "posts:read"]
  }
}
```

### Using Tokens

```bash
curl /api/v1/tallcms/pages \
  -H "Authorization: Bearer 1|abc123..."
```

### Token Abilities

| Ability | Grants Access To |
|---------|------------------|
| `pages:read` | List/view pages and revisions |
| `pages:write` | Create/update/publish pages |
| `pages:delete` | Soft-delete and force-delete pages |
| `posts:read` | List/view posts and revisions |
| `posts:write` | Create/update/publish posts |
| `posts:delete` | Soft-delete and force-delete posts |
| `categories:read` | List/view categories |
| `categories:write` | Create/update categories |
| `categories:delete` | Delete categories |
| `media:read` | List/view media and collections |
| `media:write` | Upload/update media and collections |
| `media:delete` | Delete media and collections |
| `webhooks:manage` | Full webhook management |

---

## Authorization Flow

API requests pass through two authorization layers:

```
Request
   │
   ▼
┌─────────────────────────┐
│ 1. Token Ability Check  │  CheckTokenAbilities middleware
│    "Does token have     │  e.g., pages:write
│     required ability?"  │
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐
│ 2. Policy Check         │  $this->authorize() in controller
│    "Does user have      │  e.g., Update:CmsPage Shield permission
│     Shield permission?" │
└───────────┬─────────────┘
            │
            ▼
        Response
```

Both checks must pass. A token with `pages:write` ability still requires the user to have `Update:CmsPage` Shield permission.

---

## Query Parameters

### Filtering

```
GET /pages?filter[status]=published&filter[author_id]=1
```

Each resource defines allowed filters:

| Resource | Allowed Filters |
|----------|-----------------|
| Pages | `status`, `author_id`, `parent_id`, `is_homepage`, `created_at`, `updated_at`, `trashed` |
| Posts | `status`, `author_id`, `category_id`, `is_featured`, `created_at`, `updated_at`, `trashed` |
| Categories | `parent_id` |
| Media | `mime_type`, `collection_id`, `has_variants`, `created_at` |

### Sorting

```
GET /pages?sort=created_at&order=desc
```

### Includes

```
GET /pages?include=author,children&with_counts=children
```

### Pagination

```
GET /pages?page=2&per_page=25
```

Maximum `per_page` is 100.

---

## Translations (i18n)

### Reading

Single locale:

```
GET /pages/1?locale=en
```

```json
{ "title": "About Us", "slug": "about-us" }
```

All translations:

```
GET /pages/1?with_translations=true
```

```json
{
  "title": { "en": "About Us", "de": "Über uns" },
  "slug": { "en": "about-us", "de": "ueber-uns" }
}
```

### Writing

**Single-locale mode** (use `?locale=` or `X-Locale` header):

```bash
POST /pages?locale=en
{ "title": "About Us", "content": [...] }
```

**Multi-locale mode** (use `translations` object):

```bash
PUT /pages/1
{
  "translations": {
    "title": { "en": "About Us", "de": "Über uns" }
  }
}
```

Mixing both modes in one request returns a 400 error.

---

## Webhooks

### Event Types

| Event | Triggered When |
|-------|----------------|
| `page.created` | Page created |
| `page.updated` | Page updated |
| `page.published` | Page published |
| `page.deleted` | Page soft-deleted |
| `post.created` | Post created |
| `post.updated` | Post updated |
| `post.published` | Post published |
| `post.deleted` | Post soft-deleted |

### Payload Format

```json
{
  "id": "wh_del_abc123",
  "event": "page.published",
  "attempt": 1,
  "max_attempts": 3,
  "timestamp": "2026-01-27T10:30:00Z",
  "data": {
    "id": 123,
    "type": "page",
    "attributes": { "title": "About Us", "status": "published" }
  },
  "meta": {
    "triggered_by": { "id": 1, "name": "Admin User" }
  }
}
```

### Security Headers

| Header | Description |
|--------|-------------|
| `X-TallCMS-Event` | Event type |
| `X-TallCMS-Signature` | HMAC-SHA256 signature |
| `X-TallCMS-Delivery` | Unique delivery ID |
| `X-TallCMS-Attempt` | Retry attempt number |

### Signature Verification

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TALLCMS_SIGNATURE'];
$secret = 'your-webhook-secret';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    abort(401, 'Invalid signature');
}
```

### SSRF Protection

Webhook URLs are validated for security:

- HTTPS only (port 443)
- No IP literals in hostname
- No private/reserved IP ranges
- No localhost or `.local` domains
- DNS re-validated at delivery time

---

## Configuration

```php
// config/tallcms.php

'api' => [
    'enabled' => env('TALLCMS_API_ENABLED', false),
    'prefix' => env('TALLCMS_API_PREFIX', 'api/v1/tallcms'),
    'rate_limit' => env('TALLCMS_API_RATE_LIMIT', 60),
    'auth_rate_limit' => env('TALLCMS_API_AUTH_RATE_LIMIT', 5),
    'auth_lockout_minutes' => env('TALLCMS_API_AUTH_LOCKOUT', 15),
    'token_expiry_days' => env('TALLCMS_API_TOKEN_EXPIRY', 365),
    'max_per_page' => 100,
],

'webhooks' => [
    'enabled' => env('TALLCMS_WEBHOOKS_ENABLED', false),
    'timeout' => env('TALLCMS_WEBHOOK_TIMEOUT', 30),
    'max_retries' => env('TALLCMS_WEBHOOK_MAX_RETRIES', 3),
    'retry_backoff' => [60, 300, 900],
],
```

---

## Extending the API

### Adding a New Resource

1. Create the controller:

```php
namespace TallCms\Cms\Http\Controllers\Api\V1;

class CustomController extends Controller
{
    use HandlesFiltering, HandlesPagination;

    protected function allowedFilters(): array
    {
        return ['status', 'created_at'];
    }
}
```

2. Create the resource transformer:

```php
namespace TallCms\Cms\Http\Resources\Api\V1;

class CustomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
```

3. Register routes in `routes/api.php`:

```php
Route::middleware('tallcms.abilities:custom:read')->group(function () {
    Route::get('/custom', [CustomController::class, 'index']);
});
```

4. Add the ability to `TokenAbilityValidator::VALID_ABILITIES`.

---

## Rate Limiting

| Endpoint | Limit | Key |
|----------|-------|-----|
| `POST /auth/token` | 5 attempts | IP + email hash |
| All other endpoints | 60/minute | User ID |

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Retry-After: 45  (on 429 responses)
```

---

## Common Pitfalls

**"Token missing required ability"**
The token doesn't include the needed ability. Request a new token with the required abilities.

**"This action is unauthorized" (403)**
The user has the token ability but lacks the Shield permission. Grant the permission in **Admin > Shield**.

**"Invalid include(s)"**
The requested include relation isn't in the controller's `allowedIncludes()` array.

**Webhook not receiving events**
Check that `TALLCMS_WEBHOOKS_ENABLED=true` and the webhook is active.

---

## Next Steps

- [OpenAPI Documentation](/api/docs) - Full endpoint reference
- [API Permissions Reference](api-permissions) - Token abilities and Shield permissions mapping
- [Webhook management](webhooks) - Admin panel setup
