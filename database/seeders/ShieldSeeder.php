<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions in a more readable format
        $superAdminPermissions = [
            // CmsCategory permissions
            'ViewAny:CmsCategory', 'View:CmsCategory', 'Create:CmsCategory', 'Update:CmsCategory',
            'Delete:CmsCategory', 'Restore:CmsCategory', 'ForceDelete:CmsCategory',
            'ForceDeleteAny:CmsCategory', 'RestoreAny:CmsCategory', 'Replicate:CmsCategory', 'Reorder:CmsCategory',
            // CmsPage permissions
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage',
            'Delete:CmsPage', 'Restore:CmsPage', 'ForceDelete:CmsPage',
            'ForceDeleteAny:CmsPage', 'RestoreAny:CmsPage', 'Replicate:CmsPage', 'Reorder:CmsPage',
            // CmsPage workflow permissions
            'Approve:CmsPage', 'SubmitForReview:CmsPage', 'ViewRevisions:CmsPage',
            'RestoreRevision:CmsPage', 'GeneratePreviewLink:CmsPage',
            // CmsPost permissions
            'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost',
            'Delete:CmsPost', 'Restore:CmsPost', 'ForceDelete:CmsPost',
            'ForceDeleteAny:CmsPost', 'RestoreAny:CmsPost', 'Replicate:CmsPost', 'Reorder:CmsPost',
            // CmsPost workflow permissions
            'Approve:CmsPost', 'SubmitForReview:CmsPost', 'ViewRevisions:CmsPost',
            'RestoreRevision:CmsPost', 'GeneratePreviewLink:CmsPost',
            // TallcmsMedia permissions
            'ViewAny:TallcmsMedia', 'View:TallcmsMedia', 'Create:TallcmsMedia', 'Update:TallcmsMedia',
            'Delete:TallcmsMedia', 'Restore:TallcmsMedia', 'ForceDelete:TallcmsMedia',
            'ForceDeleteAny:TallcmsMedia', 'RestoreAny:TallcmsMedia', 'Replicate:TallcmsMedia', 'Reorder:TallcmsMedia',
            // TallcmsMenu permissions
            'ViewAny:TallcmsMenu', 'View:TallcmsMenu', 'Create:TallcmsMenu', 'Update:TallcmsMenu',
            'Delete:TallcmsMenu', 'Restore:TallcmsMenu', 'ForceDelete:TallcmsMenu',
            'ForceDeleteAny:TallcmsMenu', 'RestoreAny:TallcmsMenu', 'Replicate:TallcmsMenu', 'Reorder:TallcmsMenu',
            // User permissions
            'ViewAny:User', 'View:User', 'Create:User', 'Update:User',
            'Delete:User', 'Restore:User', 'ForceDelete:User',
            'ForceDeleteAny:User', 'RestoreAny:User', 'Replicate:User', 'Reorder:User',
            // Role permissions
            'ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role',
            'Delete:Role', 'Restore:Role', 'ForceDelete:Role',
            'ForceDeleteAny:Role', 'RestoreAny:Role', 'Replicate:Role', 'Reorder:Role',
            // Page permissions
            'View:MenuItemsManager', 'View:SiteSettings', 'View:ThemeManager', 'View:MenuOverviewWidget',
            'View:PluginManager', 'View:PluginLicenses',
            // SiteTemplate permissions (multisite plugin)
            'ViewAny:SiteTemplate', 'View:SiteTemplate', 'Create:SiteTemplate', 'Update:SiteTemplate',
            'Delete:SiteTemplate',
            // Template gallery page
            'View:TemplateGallery',
        ];

        // Administrator: Full content management + approval
        $administratorPermissions = [
            // CmsCategory
            'ViewAny:CmsCategory', 'View:CmsCategory', 'Create:CmsCategory', 'Update:CmsCategory',
            'Delete:CmsCategory', 'Restore:CmsCategory',
            // CmsPage
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage',
            'Delete:CmsPage', 'Restore:CmsPage',
            'Approve:CmsPage', 'SubmitForReview:CmsPage', 'ViewRevisions:CmsPage',
            'RestoreRevision:CmsPage', 'GeneratePreviewLink:CmsPage',
            // CmsPost
            'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost',
            'Delete:CmsPost', 'Restore:CmsPost',
            'Approve:CmsPost', 'SubmitForReview:CmsPost', 'ViewRevisions:CmsPost',
            'RestoreRevision:CmsPost', 'GeneratePreviewLink:CmsPost',
            // Media & Menu
            'ViewAny:TallcmsMedia', 'View:TallcmsMedia', 'Create:TallcmsMedia', 'Update:TallcmsMedia', 'Delete:TallcmsMedia',
            'ViewAny:TallcmsMenu', 'View:TallcmsMenu', 'Create:TallcmsMenu', 'Update:TallcmsMenu', 'Delete:TallcmsMenu',
            // Pages
            'View:MenuItemsManager', 'View:SiteSettings',
            // Template gallery
            'View:TemplateGallery',
        ];

        // Editor: Content management without approval (can submit for review)
        $editorPermissions = [
            // CmsCategory (read-only)
            'ViewAny:CmsCategory', 'View:CmsCategory',
            // CmsPage
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage',
            'SubmitForReview:CmsPage', 'ViewRevisions:CmsPage', 'GeneratePreviewLink:CmsPage',
            // CmsPost
            'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost',
            'SubmitForReview:CmsPost', 'ViewRevisions:CmsPost', 'GeneratePreviewLink:CmsPost',
            // Media
            'ViewAny:TallcmsMedia', 'View:TallcmsMedia', 'Create:TallcmsMedia', 'Update:TallcmsMedia',
            // Template gallery
            'View:TemplateGallery',
        ];

        // Author: Basic content creation + submit for review
        $authorPermissions = [
            // CmsCategory (read-only)
            'ViewAny:CmsCategory', 'View:CmsCategory',
            // CmsPost only (authors write posts, not pages)
            'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost',
            'SubmitForReview:CmsPost', 'ViewRevisions:CmsPost', 'GeneratePreviewLink:CmsPost',
            // Media
            'ViewAny:TallcmsMedia', 'View:TallcmsMedia', 'Create:TallcmsMedia',
            // Template gallery
            'View:TemplateGallery',
        ];

        // Site Owner: Manages their own site end-to-end (SaaS-flow role).
        //
        // The permissions here grant access to the resource; site ownership is
        // enforced separately by the policies (ChecksSiteOwnership trait on
        // CmsPagePolicy, TallcmsMenuPolicy, CmsCommentPolicy, and
        // TallcmsContactSubmissionPolicy). Without the policy layer, a
        // site_owner would see every tenant's records — shield alone is not
        // sufficient for per-tenant isolation in multisite.
        $siteOwnerPermissions = [
            // CmsPage — full CRUD + publishing workflow
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage', 'Delete:CmsPage',
            'SubmitForReview:CmsPage', 'ViewRevisions:CmsPage', 'RestoreRevision:CmsPage', 'GeneratePreviewLink:CmsPage',
            // CmsPost — full CRUD + publishing workflow
            'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost', 'Delete:CmsPost',
            'SubmitForReview:CmsPost', 'ViewRevisions:CmsPost', 'RestoreRevision:CmsPost', 'GeneratePreviewLink:CmsPost',
            // CmsCategory — owners manage their own taxonomies
            'ViewAny:CmsCategory', 'View:CmsCategory', 'Create:CmsCategory', 'Update:CmsCategory', 'Delete:CmsCategory',
            // TallcmsMedia — full CRUD
            'ViewAny:TallcmsMedia', 'View:TallcmsMedia', 'Create:TallcmsMedia', 'Update:TallcmsMedia', 'Delete:TallcmsMedia',
            // TallcmsMenu — full CRUD
            'ViewAny:TallcmsMenu', 'View:TallcmsMenu', 'Create:TallcmsMenu', 'Update:TallcmsMenu', 'Delete:TallcmsMenu',
            // CmsComment — moderation on their own site
            'ViewAny:CmsComment', 'View:CmsComment', 'Update:CmsComment', 'Delete:CmsComment',
            'Approve:CmsComment', 'Reject:CmsComment', 'MarkAsSpam:CmsComment',
            // TallcmsContactSubmission — see and manage their own site's form submissions
            'ViewAny:TallcmsContactSubmission', 'View:TallcmsContactSubmission',
            'Update:TallcmsContactSubmission', 'Delete:TallcmsContactSubmission',
            // Admin-panel pages — wiring to let them use menus, site settings, and theme manager
            'View:MenuItemsManager', 'View:SiteSettings', 'View:ThemeManager',
            // Template gallery — choose a template when spinning up new sites
            'View:TemplateGallery',
        ];

        $rolesWithPermissions = json_encode([
            ['name' => 'super_admin', 'guard_name' => 'web', 'permissions' => $superAdminPermissions],
            ['name' => 'administrator', 'guard_name' => 'web', 'permissions' => $administratorPermissions],
            ['name' => 'editor', 'guard_name' => 'web', 'permissions' => $editorPermissions],
            ['name' => 'author', 'guard_name' => 'web', 'permissions' => $authorPermissions],
            ['name' => 'site_owner', 'guard_name' => 'web', 'permissions' => $siteOwnerPermissions],
        ]);
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
