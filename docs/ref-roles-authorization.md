---
title: "Roles & Authorization"
slug: "roles-authorization"
audience: "developer"
category: "reference"
order: 52
prerequisites:
  - "installation"
---

# Roles & Authorization

> **What you'll learn:** How TallCMS uses Filament Shield for role-based access control, including built-in roles, permissions, and artisan commands.

---

## Overview

TallCMS uses [Filament Shield](https://filamentphp.com/plugins/bezhansalleh-shield) built on [Spatie Permission](https://spatie.be/docs/laravel-permission) for authorization:

| Component | Purpose |
|-----------|---------|
| **Roles** | Groups of permissions assigned to users |
| **Permissions** | Individual access rights (e.g., `Update:CmsPage`) |
| **Policies** | Laravel policy classes that check permissions |

---

## Built-in Roles

| Role | Purpose | Typical Use |
|------|---------|-------------|
| `super_admin` | Full system access | Site owner, lead developer |
| `administrator` | Content and settings management | Site manager |
| `editor` | Content editing and publishing | Content team lead |
| `author` | Content creation, submit for review | Content contributor |

---

## Permission Naming Convention

Permissions follow the pattern: `{Action}:{Resource}`

### Actions

| Action | Description |
|--------|-------------|
| `ViewAny` | List/index resources |
| `View` | View single resource |
| `Create` | Create new resource |
| `Update` | Edit existing resource |
| `Delete` | Soft-delete resource |
| `ForceDelete` | Permanently delete |
| `Restore` | Restore soft-deleted |
| `Replicate` | Duplicate resource |
| `Reorder` | Change sort order |

### Content-Specific Actions

| Action | Description |
|--------|-------------|
| `Approve` | Approve pending content |
| `SubmitForReview` | Submit draft for approval |
| `ViewRevisions` | View revision history |
| `RestoreRevision` | Restore previous revision |
| `GeneratePreviewLink` | Create preview URLs |

---

## Permission Categories

### Content Management

| Category | Resource | Description |
|----------|----------|-------------|
| `CmsPage` | Pages | Static pages |
| `CmsPost` | Posts | Blog posts |
| `CmsCategory` | Categories | Post categories |
| `TallcmsMedia` | Media | Media library |
| `MediaCollection` | Collections | Media collections |
| `TallcmsMenu` | Menus | Navigation menus |

### System Administration

| Category | Resource | Description |
|----------|----------|-------------|
| `User` | Users | User accounts |
| `Role` | Roles | Role management |
| `SiteSettings` | Settings | Site configuration |
| `TallcmsContactSubmission` | Contact | Form submissions |

### Standalone Features

| Category | Resource | Description |
|----------|----------|-------------|
| `ThemeManager` | Themes | Theme management |
| `PluginManager` | Plugins | Plugin management |
| `PluginLicenses` | Licenses | License keys |
| `SystemUpdates` | Updates | System updates |

---

## Artisan Commands

### Shield Commands

#### Generate Permissions

Generate permissions for Filament resources:

```bash
# Generate for all resources
php artisan shield:generate --all

# Generate for specific resource
php artisan shield:generate --resource=CmsPageResource

# Generate for specific panel
php artisan shield:generate --all --panel=admin
```

#### Create Super Admin

Assign super admin role to a user:

```bash
php artisan shield:super-admin

# Or specify user
php artisan shield:super-admin --user=1
```

#### Create Seeder

Generate a seeder from current roles/permissions:

```bash
php artisan shield:seeder
```

Creates `database/seeders/ShieldSeeder.php` for version control.

#### Setup Shield

Initial Shield installation:

```bash
php artisan shield:setup
```

### Permission Commands

#### Show Permissions

Display roles and permissions matrix:

```bash
php artisan permission:show
```

#### Create Role

```bash
php artisan permission:create-role editor
```

#### Create Permission

```bash
php artisan permission:create-permission "CustomAction:CustomResource"
```

#### Assign Role to User

```bash
php artisan permission:assign-role editor user@example.com
```

#### Clear Permission Cache

```bash
php artisan permission:cache-reset
```

### TallCMS Commands

#### Full Setup

Run complete TallCMS setup including roles:

```bash
php artisan tallcms:setup
```

#### Install Only

Run migrations and create default roles:

```bash
php artisan tallcms:install
```

---

## Managing Roles in Admin Panel

### View Roles

Navigate to **Admin > Shield > Roles** to see all roles and their permissions.

### Create Role

1. Click **New Role**
2. Enter role name (lowercase, underscores)
3. Select permissions
4. Click **Create**

### Edit Role Permissions

1. Navigate to **Admin > Shield > Roles**
2. Click the role to edit
3. Check/uncheck permissions
4. Click **Save**

### Assign Role to User

1. Navigate to **Admin > Users**
2. Edit the user
3. Select role(s) in the **Roles** field
4. Click **Save**

---

## Managing Permissions in Code

### Check Permission

```php
// Via user
$user->can('Update:CmsPage');
$user->cannot('ForceDelete:CmsPage');

// Via Gate
Gate::allows('Update:CmsPage');

// Via policy (in controller)
$this->authorize('update', $page);
```

### Grant Permission to Role

```php
use Spatie\Permission\Models\Role;

$role = Role::findByName('editor');
$role->givePermissionTo('Approve:CmsPage');
$role->givePermissionTo(['Approve:CmsPost', 'ViewRevisions:CmsPost']);
```

### Revoke Permission

```php
$role->revokePermissionTo('ForceDelete:CmsPage');
```

### Sync Permissions

Replace all permissions for a role:

```php
$role->syncPermissions([
    'ViewAny:CmsPage',
    'View:CmsPage',
    'Create:CmsPage',
    'Update:CmsPage',
]);
```

### Assign Role to User

```php
$user->assignRole('editor');
$user->assignRole(['editor', 'author']);
```

### Remove Role from User

```php
$user->removeRole('author');
```

### Check Role

```php
$user->hasRole('editor');
$user->hasAnyRole(['editor', 'administrator']);
$user->hasAllRoles(['editor', 'author']);
```

---

## Creating Custom Permissions

### 1. Create the Permission

```bash
php artisan permission:create-permission "Export:CmsPage"
```

Or in code:

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'Export:CmsPage']);
```

### 2. Add to Policy

```php
// app/Policies/CmsPagePolicy.php

public function export(User $user, CmsPage $page): bool
{
    return $user->can('Export:CmsPage');
}
```

### 3. Use in Controller

```php
$this->authorize('export', $page);
```

### 4. Use in Filament Resource

```php
// In your Resource class

public static function canExport(): bool
{
    return auth()->user()?->can('Export:CmsPage') ?? false;
}
```

---

## Seeding Roles and Permissions

### Create Seeder

```php
// database/seeders/RolesAndPermissionsSeeder.php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage',
            'Update:CmsPage', 'Delete:CmsPage',
            'Approve:CmsPage', 'SubmitForReview:CmsPage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $author = Role::firstOrCreate(['name' => 'author']);
        $author->givePermissionTo([
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage',
            'Update:CmsPage', 'SubmitForReview:CmsPage',
        ]);

        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->givePermissionTo([
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage',
            'Update:CmsPage', 'Delete:CmsPage', 'Approve:CmsPage',
        ]);
    }
}
```

### Run Seeder

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## Caching

Permissions are cached automatically. Clear cache after changes:

```bash
php artisan permission:cache-reset
```

Or in code:

```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## Common Pitfalls

**Permission changes not taking effect**
Run `php artisan permission:cache-reset` to clear the permission cache.

**"There is no permission named X"**
The permission doesn't exist. Create it with `php artisan permission:create-permission "X"` or run `php artisan shield:generate --all`.

**User has role but can't access resource**
The role may not have the required permission. Check with `php artisan permission:show` or in **Admin > Shield > Roles**.

**New Filament resource has no permissions**
Run `php artisan shield:generate --resource=YourResource` to generate permissions.

---

## Next Steps

- [API Permissions Reference](api-permissions) - API-specific permission mapping
- [REST API Development](api) - API architecture
- [Shield Documentation](https://filamentphp.com/plugins/bezhansalleh-shield) - Full Shield reference
- [Spatie Permission Docs](https://spatie.be/docs/laravel-permission) - Underlying package
