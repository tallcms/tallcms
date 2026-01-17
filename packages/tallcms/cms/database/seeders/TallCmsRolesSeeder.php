<?php

declare(strict_types=1);

namespace TallCms\Cms\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds default TallCMS roles with appropriate permissions.
 *
 * This seeder creates four default roles:
 * - super_admin: Full system access (all permissions)
 * - administrator: Content + limited user management
 * - editor: Full content management
 * - author: Create and edit own content
 *
 * Run after Shield has generated permissions:
 * php artisan db:seed --class="TallCms\Cms\Database\Seeders\TallCmsRolesSeeder"
 */
class TallCmsRolesSeeder extends Seeder
{
    /**
     * The guard name to use for roles and permissions.
     * Override this in a subclass or set via constructor if needed.
     */
    protected string $guardName = 'web';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Allow guard to be configured via config
        $this->guardName = config('tallcms.auth.guard', $this->guardName);

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create default roles
        $this->createRoles();

        // Assign permissions to roles
        $this->assignPermissions();
    }

    /**
     * Create default TallCMS roles.
     */
    protected function createRoles(): void
    {
        $roles = [
            'super_admin' => 'Super Administrator - Complete system access',
            'administrator' => 'Administrator - Full content and limited user management',
            'editor' => 'Editor - Full content management',
            'author' => 'Author - Create and edit own content',
        ];

        foreach (array_keys($roles) as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $this->guardName],
                ['name' => $roleName, 'guard_name' => $this->guardName]
            );
        }
    }

    /**
     * Assign permissions to roles based on TallCMS defaults.
     */
    protected function assignPermissions(): void
    {
        $allPermissions = Permission::where('guard_name', $this->guardName)->get();

        if ($allPermissions->isEmpty()) {
            // No permissions yet - this is expected if Shield hasn't run
            return;
        }

        // Super Admin gets all permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions($allPermissions);
        }

        // Administrator: Content + limited user management + some settings
        $administratorPermissions = $allPermissions->filter(function ($permission) {
            return $this->isAdministratorPermission($permission->name);
        });
        $administratorRole = Role::where('name', 'administrator')->first();
        if ($administratorRole) {
            $administratorRole->syncPermissions($administratorPermissions);
        }

        // Editor: Full content management, no users/settings
        $editorPermissions = $allPermissions->filter(function ($permission) {
            return $this->isEditorPermission($permission->name);
        });
        $editorRole = Role::where('name', 'editor')->first();
        if ($editorRole) {
            $editorRole->syncPermissions($editorPermissions);
        }

        // Author: Own content + basic operations
        $authorPermissions = $allPermissions->filter(function ($permission) {
            return $this->isAuthorPermission($permission->name);
        });
        $authorRole = Role::where('name', 'author')->first();
        if ($authorRole) {
            $authorRole->syncPermissions($authorPermissions);
        }
    }

    /**
     * Check if permission is for Administrator role.
     *
     * Shield generates snake_case permissions like: view_any_cms_page, create_cms_post
     */
    protected function isAdministratorPermission(string $permission): bool
    {
        // Allow all CMS content management (snake_case format from Shield)
        if (str_contains($permission, 'cms_page') ||
            str_contains($permission, 'cms_post') ||
            str_contains($permission, 'cms_category') ||
            str_contains($permission, 'tallcms_menu') ||
            str_contains($permission, 'tallcms_media') ||
            str_contains($permission, 'tallcms_contact_submission')) {
            return true;
        }

        // Allow user management (but exclude Shield roles)
        if (str_contains($permission, '_user') &&
            ! str_contains($permission, '_role') &&
            ! str_contains($permission, 'shield')) {
            return true;
        }

        // Allow site settings page
        if (str_contains($permission, 'site_settings')) {
            return true;
        }

        return false;
    }

    /**
     * Check if permission is for Editor role.
     *
     * Shield generates snake_case permissions like: view_any_cms_page, create_cms_post
     */
    protected function isEditorPermission(string $permission): bool
    {
        // Full content management (snake_case format from Shield)
        if (str_contains($permission, 'cms_page') ||
            str_contains($permission, 'cms_post') ||
            str_contains($permission, 'cms_category') ||
            str_contains($permission, 'tallcms_menu') ||
            str_contains($permission, 'tallcms_media') ||
            str_contains($permission, 'tallcms_contact_submission')) {
            return true;
        }

        // No user management, no settings, no system features
        return false;
    }

    /**
     * Check if permission is for Author role.
     *
     * Shield generates snake_case permissions like: view_any_cms_page, create_cms_post
     */
    protected function isAuthorPermission(string $permission): bool
    {
        // Basic content permissions (view, create, update)
        if (str_contains($permission, 'cms_page') || str_contains($permission, 'cms_post')) {
            // Allow ViewAny, View, Create, Update
            if (str_contains($permission, 'view_any_') ||
                str_contains($permission, 'view_') ||
                str_contains($permission, 'create_') ||
                str_contains($permission, 'update_')) {
                return true;
            }
            // Exclude delete operations for security
        }

        // View categories only (but can't manage them)
        if (str_contains($permission, 'cms_category') &&
            (str_contains($permission, 'view_any_') || str_contains($permission, 'view_'))) {
            return true;
        }

        // Basic media operations
        if (str_contains($permission, 'tallcms_media') &&
            (str_contains($permission, 'view_any_') ||
             str_contains($permission, 'view_') ||
             str_contains($permission, 'create_') ||
             str_contains($permission, 'update_'))) {
            return true;
        }

        // View contact submissions
        if (str_contains($permission, 'tallcms_contact_submission') &&
            (str_contains($permission, 'view_any_') || str_contains($permission, 'view_'))) {
            return true;
        }

        // Menu viewing (view only)
        if (str_contains($permission, 'tallcms_menu') &&
            (str_contains($permission, 'view_any_') || str_contains($permission, 'view_'))) {
            return true;
        }

        return false;
    }
}
