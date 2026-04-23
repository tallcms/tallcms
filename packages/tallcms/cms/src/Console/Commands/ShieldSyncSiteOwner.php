<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotently install or update the site_owner role.
 *
 * Why a dedicated command rather than re-running ShieldSeeder: the seeder
 * runs on fresh installs via db:seed. Existing installs that already seeded
 * Shield without the site_owner role need a safe way to pick it up without
 * touching the other roles or existing user-role assignments. This command
 * creates or updates only the site_owner role and its permissions — nothing
 * else is modified.
 *
 * Safe to run repeatedly. Missing permissions are created, existing ones
 * kept. Role-to-permission mapping is synced to the canonical list.
 */
class ShieldSyncSiteOwner extends Command
{
    protected $signature = 'tallcms:shield-sync-site-owner {--dry-run : Show what would change without applying}';

    protected $description = 'Create or update the site_owner role with the SaaS permission set';

    public function handle(): int
    {
        $permissions = $this->siteOwnerPermissions();

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run — no changes will be applied.');
            $this->newLine();
        }

        // Ensure every permission exists. Create missing ones.
        $createdPermissions = [];
        foreach ($permissions as $name) {
            $existing = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if (! $existing) {
                $createdPermissions[] = $name;
                if (! $dryRun) {
                    Permission::create(['name' => $name, 'guard_name' => 'web']);
                }
            }
        }

        // Ensure the role exists.
        $role = Role::where('name', 'site_owner')->where('guard_name', 'web')->first();
        $created = false;
        if (! $role) {
            $created = true;
            if (! $dryRun) {
                $role = Role::create(['name' => 'site_owner', 'guard_name' => 'web']);
            }
        }

        $currentPermissionNames = $role && ! $dryRun
            ? $role->permissions()->pluck('name')->all()
            : [];
        $toAdd = array_values(array_diff($permissions, $currentPermissionNames));
        $toRemove = array_values(array_diff($currentPermissionNames, $permissions));

        if (! $dryRun && $role) {
            $role->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Report
        $this->info($created ? 'Created role: site_owner' : 'Updated role: site_owner');
        $this->components->twoColumnDetail('Permissions created', (string) count($createdPermissions));
        $this->components->twoColumnDetail('Permissions added to role', (string) count($toAdd));
        $this->components->twoColumnDetail('Permissions removed from role', (string) count($toRemove));
        $this->components->twoColumnDetail('Total role permissions', (string) count($permissions));

        if (! empty($createdPermissions) && $this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->line('<comment>Newly created permissions:</comment>');
            foreach ($createdPermissions as $name) {
                $this->line("  + {$name}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Canonical permission set for the site_owner role.
     *
     * Kept in sync with database/seeders/ShieldSeeder.php — the two paths
     * exist so fresh installs (seeder) and existing installs (this command)
     * converge on the same permission list.
     */
    protected function siteOwnerPermissions(): array
    {
        return [
            // CmsPage
            'ViewAny:CmsPage', 'View:CmsPage', 'Create:CmsPage', 'Update:CmsPage', 'Delete:CmsPage',
            'SubmitForReview:CmsPage', 'ViewRevisions:CmsPage', 'RestoreRevision:CmsPage', 'GeneratePreviewLink:CmsPage',
            // CmsPost
            'ViewAny:CmsPost', 'View:CmsPost', 'Create:CmsPost', 'Update:CmsPost', 'Delete:CmsPost',
            'SubmitForReview:CmsPost', 'ViewRevisions:CmsPost', 'RestoreRevision:CmsPost', 'GeneratePreviewLink:CmsPost',
            // CmsCategory
            'ViewAny:CmsCategory', 'View:CmsCategory', 'Create:CmsCategory', 'Update:CmsCategory', 'Delete:CmsCategory',
            // TallcmsMedia
            'ViewAny:TallcmsMedia', 'View:TallcmsMedia', 'Create:TallcmsMedia', 'Update:TallcmsMedia', 'Delete:TallcmsMedia',
            // TallcmsMenu
            'ViewAny:TallcmsMenu', 'View:TallcmsMenu', 'Create:TallcmsMenu', 'Update:TallcmsMenu', 'Delete:TallcmsMenu',
            // CmsComment (moderation)
            'ViewAny:CmsComment', 'View:CmsComment', 'Update:CmsComment', 'Delete:CmsComment',
            'Approve:CmsComment', 'Reject:CmsComment', 'MarkAsSpam:CmsComment',
            // TallcmsContactSubmission
            'ViewAny:TallcmsContactSubmission', 'View:TallcmsContactSubmission',
            'Update:TallcmsContactSubmission', 'Delete:TallcmsContactSubmission',
            // Admin pages
            'View:MenuItemsManager', 'View:SiteSettings', 'View:ThemeManager',
            // Template gallery
            'View:TemplateGallery',
        ];
    }
}
