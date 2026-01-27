---
title: "API Permissions Reference"
slug: "api-permissions"
audience: "developer"
category: "reference"
order: 55
prerequisites:
  - "api"
---

# API Permissions Reference

> **What you'll learn:** How Shield permissions work with the REST API and how to configure roles for API access.

---

## Overview

The TallCMS API uses a **dual authorization** system:

| Layer | Purpose | Configured In |
|-------|---------|---------------|
| **Token Abilities** | Scopes what the token can do | Token creation request |
| **Shield Permissions** | Scopes what the user can do | Admin panel (Shield) |

Both layers must authorize a request for it to succeed.

---

## Permission Mapping

### Pages

| API Endpoint | Token Ability | Shield Permission |
|--------------|---------------|-------------------|
| `GET /pages` | `pages:read` | `ViewAny:CmsPage` |
| `GET /pages/{id}` | `pages:read` | `View:CmsPage` |
| `GET /pages/{id}/revisions` | `pages:read` | `ViewRevisions:CmsPage` |
| `POST /pages` | `pages:write` | `Create:CmsPage` |
| `PUT /pages/{id}` | `pages:write` | `Update:CmsPage` |
| `POST /pages/{id}/publish` | `pages:write` | `Update:CmsPage` |
| `POST /pages/{id}/unpublish` | `pages:write` | `Update:CmsPage` |
| `POST /pages/{id}/submit-for-review` | `pages:write` | `SubmitForReview:CmsPage` |
| `POST /pages/{id}/approve` | `pages:write` | `Approve:CmsPage` |
| `POST /pages/{id}/reject` | `pages:write` | `Approve:CmsPage` |
| `POST /pages/{id}/restore` | `pages:write` | `Restore:CmsPage` |
| `POST /pages/{id}/revisions/{rev}/restore` | `pages:write` | `RestoreRevision:CmsPage` |
| `DELETE /pages/{id}` | `pages:delete` | `Delete:CmsPage` |
| `DELETE /pages/{id}/force` | `pages:delete` | `ForceDelete:CmsPage` |

### Posts

| API Endpoint | Token Ability | Shield Permission |
|--------------|---------------|-------------------|
| `GET /posts` | `posts:read` | `ViewAny:CmsPost` |
| `GET /posts/{id}` | `posts:read` | `View:CmsPost` |
| `GET /posts/{id}/revisions` | `posts:read` | `ViewRevisions:CmsPost` |
| `POST /posts` | `posts:write` | `Create:CmsPost` |
| `PUT /posts/{id}` | `posts:write` | `Update:CmsPost` |
| `POST /posts/{id}/publish` | `posts:write` | `Update:CmsPost` |
| `POST /posts/{id}/unpublish` | `posts:write` | `Update:CmsPost` |
| `POST /posts/{id}/submit-for-review` | `posts:write` | `SubmitForReview:CmsPost` |
| `POST /posts/{id}/approve` | `posts:write` | `Approve:CmsPost` |
| `POST /posts/{id}/reject` | `posts:write` | `Approve:CmsPost` |
| `POST /posts/{id}/restore` | `posts:write` | `Restore:CmsPost` |
| `POST /posts/{id}/revisions/{rev}/restore` | `posts:write` | `RestoreRevision:CmsPost` |
| `DELETE /posts/{id}` | `posts:delete` | `Delete:CmsPost` |
| `DELETE /posts/{id}/force` | `posts:delete` | `ForceDelete:CmsPost` |

### Categories

| API Endpoint | Token Ability | Shield Permission |
|--------------|---------------|-------------------|
| `GET /categories` | `categories:read` | `ViewAny:CmsCategory` |
| `GET /categories/{id}` | `categories:read` | `View:CmsCategory` |
| `GET /categories/{id}/posts` | `categories:read` | `View:CmsCategory` |
| `POST /categories` | `categories:write` | `Create:CmsCategory` |
| `PUT /categories/{id}` | `categories:write` | `Update:CmsCategory` |
| `DELETE /categories/{id}` | `categories:delete` | `Delete:CmsCategory` |

### Media

| API Endpoint | Token Ability | Shield Permission |
|--------------|---------------|-------------------|
| `GET /media` | `media:read` | `ViewAny:TallcmsMedia` |
| `GET /media/{id}` | `media:read` | `View:TallcmsMedia` |
| `GET /media/collections` | `media:read` | `ViewAny:TallcmsMedia` |
| `GET /media/collections/{id}` | `media:read` | `ViewAny:TallcmsMedia` |
| `POST /media` | `media:write` | `Create:TallcmsMedia` |
| `POST /media/collections` | `media:write` | `Create:TallcmsMedia` |
| `PUT /media/{id}` | `media:write` | `Update:TallcmsMedia` |
| `PUT /media/collections/{id}` | `media:write` | `Create:TallcmsMedia` |
| `DELETE /media/{id}` | `media:delete` | `Delete:TallcmsMedia` |
| `DELETE /media/collections/{id}` | `media:delete` | `Create:TallcmsMedia` |

### Webhooks

| API Endpoint | Token Ability | Shield Permission |
|--------------|---------------|-------------------|
| `GET /webhooks` | `webhooks:manage` | `ViewAny:Webhook` |
| `GET /webhooks/{id}` | `webhooks:manage` | `View:Webhook` |
| `POST /webhooks` | `webhooks:manage` | `Create:Webhook` |
| `PUT /webhooks/{id}` | `webhooks:manage` | `Update:Webhook` |
| `DELETE /webhooks/{id}` | `webhooks:manage` | `Delete:Webhook` |
| `POST /webhooks/{id}/test` | `webhooks:manage` | `Update:Webhook` |

---

## Required Permissions by Role

### API Reader Role

Minimum permissions for read-only API access:

```
ViewAny:CmsPage
View:CmsPage
ViewAny:CmsPost
View:CmsPost
ViewAny:CmsCategory
View:CmsCategory
ViewAny:TallcmsMedia
View:TallcmsMedia
```

### API Editor Role

Permissions for content management via API:

```
# Read permissions (from Reader)
ViewAny:CmsPage, View:CmsPage
ViewAny:CmsPost, View:CmsPost
ViewAny:CmsCategory, View:CmsCategory
ViewAny:TallcmsMedia, View:TallcmsMedia

# Write permissions
Create:CmsPage, Update:CmsPage
Create:CmsPost, Update:CmsPost
Create:CmsCategory, Update:CmsCategory
Create:TallcmsMedia, Update:TallcmsMedia

# Workflow permissions
ViewRevisions:CmsPage, ViewRevisions:CmsPost
SubmitForReview:CmsPage, SubmitForReview:CmsPost
```

### API Publisher Role

Full content management including approval:

```
# All Editor permissions, plus:
Approve:CmsPage, Approve:CmsPost
Restore:CmsPage, Restore:CmsPost
RestoreRevision:CmsPage, RestoreRevision:CmsPost
Delete:CmsPage, Delete:CmsPost
Delete:CmsCategory
Delete:TallcmsMedia
```

### API Admin Role

Full API access including force-delete and webhooks:

```
# All Publisher permissions, plus:
ForceDelete:CmsPage, ForceDelete:CmsPost
ViewAny:Webhook, View:Webhook
Create:Webhook, Update:Webhook, Delete:Webhook
```

---

## Granting Permissions

### Via Admin Panel

1. Navigate to **Admin > Shield > Roles**
2. Select or create a role
3. Check the required permissions
4. Click **Save**

### Via Artisan

```bash
# Grant single permission
php artisan permission:grant-to-role editor "Approve:CmsPage"

# Grant multiple permissions
php artisan tinker
>>> $role = \Spatie\Permission\Models\Role::findByName('editor');
>>> $role->givePermissionTo(['Approve:CmsPage', 'Approve:CmsPost']);
```

### Via Seeder

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$apiEditor = Role::findOrCreate('api_editor');

$apiEditor->givePermissionTo([
    'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage',
    'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost',
    'ViewRevisions:CmsPage', 'ViewRevisions:CmsPost',
    'SubmitForReview:CmsPage', 'SubmitForReview:CmsPost',
]);
```

---

## Checking Permissions

### In Code

```php
// Check if user has permission
$user->can('Approve:CmsPage');

// Check via policy
$this->authorize('approve', $page);

// Get all user permissions
$user->getAllPermissions()->pluck('name');
```

### Via Tinker

```bash
php artisan tinker
>>> $user = User::find(1);
>>> $user->can('ViewRevisions:CmsPage')
=> true
>>> $user->roles->pluck('name')
=> ["super_admin"]
>>> $user->getAllPermissions()->pluck('name')->filter(fn($p) => str_contains($p, 'CmsPage'))
```

---

## Common Pitfalls

**"This action is unauthorized" on workflow endpoints**
The user is missing workflow permissions. Grant `SubmitForReview:CmsPage`, `Approve:CmsPage`, `ViewRevisions:CmsPage` as needed.

**Token works for some endpoints but not others**
Check both token abilities AND Shield permissions. A `pages:write` token still needs `Update:CmsPage` permission.

**New role can't access API**
Ensure the role has at least `ViewAny` and `View` permissions for the resources it needs to access.

**Super admin missing permissions**
Shield's `super_admin` role doesn't automatically have all permissions. You may need to grant new permissions explicitly after they're created.

---

## Next Steps

- [REST API Development](api) - API architecture overview
- [Roles & Authorization](roles-authorization) - Shield roles, permissions, and artisan commands
- [OpenAPI Documentation](/api/docs) - Endpoint reference
