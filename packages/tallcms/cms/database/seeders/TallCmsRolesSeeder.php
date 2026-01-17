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
 * Supported Shield permission formats:
 * - pascal case with any separator (default: ViewAny:CmsPage)
 * - snake case with any separator (e.g., view_any_cms_page)
 *
 * Unsupported formats (will throw RuntimeException):
 * - camel, kebab, upper_snake, lower_snake
 * For these, create a custom seeder extending this class.
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
     * Shield permission separator (default ':' for Action:Model format).
     */
    protected string $separator = ':';

    /**
     * Shield permission case (default 'pascal' for CmsPage format).
     */
    protected string $case = 'pascal';

    /**
     * Supported Shield permission case formats.
     * Other formats (camel, kebab, upper_snake, lower_snake) are not supported.
     */
    protected const SUPPORTED_CASES = ['pascal', 'snake'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Allow guard to be configured via config
        $this->guardName = config('tallcms.auth.guard', $this->guardName);

        // Read Shield's permission format settings (filament-shield.permissions.*)
        $this->separator = config('filament-shield.permissions.separator', ':');
        $this->case = config('filament-shield.permissions.case', 'pascal');

        // Fail fast if using unsupported permission case format
        if (! in_array($this->case, self::SUPPORTED_CASES, true)) {
            $supported = implode(', ', self::SUPPORTED_CASES);
            throw new \RuntimeException(
                "TallCmsRolesSeeder only supports Shield permission cases: {$supported}. " .
                "Current config: filament-shield.permissions.case = '{$this->case}'. " .
                "Please change your Shield config or create a custom seeder."
            );
        }

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
     * Shield permission format depends on config:
     * - Default (pascal case, colon separator): Create:CmsPage, ViewAny:TallcmsMenu
     * - Snake case with underscore: create_cms_page, view_any_tallcms_menu
     */
    protected function isAdministratorPermission(string $permission): bool
    {
        // Allow all CMS content management
        if ($this->matchesModel($permission, 'CmsPage') ||
            $this->matchesModel($permission, 'CmsPost') ||
            $this->matchesModel($permission, 'CmsCategory') ||
            $this->matchesModel($permission, 'TallcmsMenu') ||
            $this->matchesModel($permission, 'TallcmsMedia') ||
            $this->matchesModel($permission, 'TallcmsContactSubmission')) {
            return true;
        }

        // Allow user management (but exclude Shield roles)
        if ($this->matchesModel($permission, 'User') &&
            ! $this->matchesModel($permission, 'Role')) {
            return true;
        }

        // Allow site settings page
        if ($this->matchesModel($permission, 'SiteSettings')) {
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
        if ($this->matchesModel($permission, 'CmsPage') ||
            $this->matchesModel($permission, 'CmsPost') ||
            $this->matchesModel($permission, 'CmsCategory') ||
            $this->matchesModel($permission, 'TallcmsMenu') ||
            $this->matchesModel($permission, 'TallcmsMedia') ||
            $this->matchesModel($permission, 'TallcmsContactSubmission')) {
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
        if ($this->matchesModel($permission, 'CmsPage') || $this->matchesModel($permission, 'CmsPost')) {
            // Allow ViewAny, View, Create, Update
            if ($this->matchesAction($permission, 'ViewAny') ||
                $this->matchesAction($permission, 'View') ||
                $this->matchesAction($permission, 'Create') ||
                $this->matchesAction($permission, 'Update')) {
                return true;
            }
            // Exclude delete operations for security
        }

        // View categories only (but can't manage them)
        if ($this->matchesModel($permission, 'CmsCategory') &&
            ($this->matchesAction($permission, 'ViewAny') || $this->matchesAction($permission, 'View'))) {
            return true;
        }

        // Basic media operations
        if ($this->matchesModel($permission, 'TallcmsMedia') &&
            ($this->matchesAction($permission, 'ViewAny') ||
             $this->matchesAction($permission, 'View') ||
             $this->matchesAction($permission, 'Create') ||
             $this->matchesAction($permission, 'Update'))) {
            return true;
        }

        // View contact submissions
        if ($this->matchesModel($permission, 'TallcmsContactSubmission') &&
            ($this->matchesAction($permission, 'ViewAny') || $this->matchesAction($permission, 'View'))) {
            return true;
        }

        // Menu viewing (view only)
        if ($this->matchesModel($permission, 'TallcmsMenu') &&
            ($this->matchesAction($permission, 'ViewAny') || $this->matchesAction($permission, 'View'))) {
            return true;
        }

        return false;
    }

    /**
     * Check if permission contains the model name (format-aware).
     *
     * Handles both formats using configured separator:
     * - Pascal case with ':': Create:CmsPage, ViewAny:TallcmsMenu
     * - Snake case with '_': create_cms_page, view_any_tallcms_menu
     * - Custom combinations: create:cms_page (snake case, colon separator)
     */
    protected function matchesModel(string $permission, string $modelName): bool
    {
        if ($this->case === 'snake') {
            // Convert CmsPage to cms_page for matching
            $snakeModel = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $modelName));

            // Model appears after the separator
            return str_contains($permission, $this->separator.$snakeModel);
        }

        // Pascal case - model name appears after separator
        return str_contains($permission, $this->separator.$modelName);
    }

    /**
     * Check if permission contains the action (format-aware).
     *
     * Handles both formats using configured separator:
     * - Pascal case with ':': ViewAny:CmsPage, Create:CmsPost
     * - Snake case with '_': view_any_cms_page, create_cms_post
     * - Custom combinations: view_any:cms_page (snake case, colon separator)
     */
    protected function matchesAction(string $permission, string $actionName): bool
    {
        if ($this->case === 'snake') {
            // Convert ViewAny to view_any for matching
            $snakeAction = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $actionName));

            // Use configured separator (not hard-coded '_')
            return str_starts_with($permission, $snakeAction.$this->separator);
        }

        // Pascal case - action appears before separator
        return str_starts_with($permission, $actionName.$this->separator);
    }
}
