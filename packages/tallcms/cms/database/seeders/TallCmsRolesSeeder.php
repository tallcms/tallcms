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
     * Run the database seeds.
     */
    public function run(): void
    {
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
                ['name' => $roleName, 'guard_name' => 'web'],
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }
    }

    /**
     * Assign permissions to roles based on TallCMS defaults.
     */
    protected function assignPermissions(): void
    {
        $allPermissions = Permission::where('guard_name', 'web')->get();

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
     */
    protected function isAdministratorPermission(string $permission): bool
    {
        // Allow all CMS content management
        if (str_contains($permission, 'CmsPage') ||
            str_contains($permission, 'CmsPost') ||
            str_contains($permission, 'CmsCategory') ||
            str_contains($permission, 'TallcmsMenu') ||
            str_contains($permission, 'TallcmsMedia') ||
            str_contains($permission, 'TallcmsContactSubmission')) {
            return true;
        }

        // Allow user management (but exclude Shield roles)
        if (str_contains($permission, 'User') &&
            ! str_contains($permission, 'Role') &&
            ! str_contains($permission, 'Shield')) {
            return true;
        }

        // Allow site settings page
        if (str_contains($permission, 'SiteSettings')) {
            return true;
        }

        return false;
    }

    /**
     * Check if permission is for Editor role.
     */
    protected function isEditorPermission(string $permission): bool
    {
        // Full content management
        if (str_contains($permission, 'CmsPage') ||
            str_contains($permission, 'CmsPost') ||
            str_contains($permission, 'CmsCategory') ||
            str_contains($permission, 'TallcmsMenu') ||
            str_contains($permission, 'TallcmsMedia') ||
            str_contains($permission, 'TallcmsContactSubmission')) {
            return true;
        }

        // No user management, no settings, no system features
        return false;
    }

    /**
     * Check if permission is for Author role.
     */
    protected function isAuthorPermission(string $permission): bool
    {
        // Basic content permissions (view, create, update)
        if (str_contains($permission, 'CmsPage') || str_contains($permission, 'CmsPost')) {
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
        if (str_contains($permission, 'CmsCategory') &&
            (str_contains($permission, 'view_any_') || str_contains($permission, 'view_'))) {
            return true;
        }

        // Basic media operations
        if (str_contains($permission, 'TallcmsMedia') &&
            (str_contains($permission, 'view_any_') ||
             str_contains($permission, 'view_') ||
             str_contains($permission, 'create_') ||
             str_contains($permission, 'update_'))) {
            return true;
        }

        // View contact submissions
        if (str_contains($permission, 'TallcmsContactSubmission') &&
            (str_contains($permission, 'view_any_') || str_contains($permission, 'view_'))) {
            return true;
        }

        // Menu viewing (view only)
        if (str_contains($permission, 'TallcmsMenu') &&
            (str_contains($permission, 'view_any_') || str_contains($permission, 'view_'))) {
            return true;
        }

        return false;
    }
}
