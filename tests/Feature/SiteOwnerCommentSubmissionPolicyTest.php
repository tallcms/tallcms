<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Models\CmsComment;
use TallCms\Cms\Models\TallcmsContactSubmission;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

/**
 * Regression tests for site-ownership scoping on CmsCommentPolicy and
 * TallcmsContactSubmissionPolicy.
 *
 * Without the ownership check layered on top of Shield permissions, a
 * site_owner in multisite would see every tenant's comments and submissions —
 * a horizontal privilege-escalation path. These tests fail if that regression
 * ever creeps back in.
 */
class SiteOwnerCommentSubmissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $ownerA;

    protected User $ownerB;

    protected Site $siteA;

    protected Site $siteB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        $this->seedRolesAndPermissions();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('site_owner');

        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('site_owner');

        $this->siteA = Site::create([
            'name' => 'Site A',
            'domain' => 'site-a.test',
            'is_active' => true,
            'user_id' => $this->ownerA->id,
        ]);

        $this->siteB = Site::create([
            'name' => 'Site B',
            'domain' => 'site-b.test',
            'is_active' => true,
            'user_id' => $this->ownerB->id,
        ]);
    }

    public function test_site_owner_can_view_their_own_site_comment(): void
    {
        $comment = $this->makeComment($this->siteA->id);

        $this->actingAs($this->ownerA);

        $this->assertTrue($this->ownerA->can('view', $comment));
        $this->assertTrue($this->ownerA->can('update', $comment));
        $this->assertTrue($this->ownerA->can('approve', $comment));
    }

    public function test_site_owner_cannot_view_another_sites_comment(): void
    {
        $commentOnB = $this->makeComment($this->siteB->id);

        $this->actingAs($this->ownerA);

        $this->assertFalse($this->ownerA->can('view', $commentOnB));
        $this->assertFalse($this->ownerA->can('update', $commentOnB));
        $this->assertFalse($this->ownerA->can('approve', $commentOnB));
        $this->assertFalse($this->ownerA->can('delete', $commentOnB));
    }

    public function test_super_admin_sees_all_comments(): void
    {
        $commentOnA = $this->makeComment($this->siteA->id);
        $commentOnB = $this->makeComment($this->siteB->id);

        $this->actingAs($this->superAdmin);

        $this->assertTrue($this->superAdmin->can('view', $commentOnA));
        $this->assertTrue($this->superAdmin->can('view', $commentOnB));
    }

    public function test_site_owner_can_view_their_own_submission(): void
    {
        $submission = $this->makeSubmission($this->siteA->id);

        $this->actingAs($this->ownerA);

        $this->assertTrue($this->ownerA->can('view', $submission));
        $this->assertTrue($this->ownerA->can('update', $submission));
        $this->assertTrue($this->ownerA->can('delete', $submission));
    }

    public function test_site_owner_cannot_view_another_sites_submission(): void
    {
        $submissionOnB = $this->makeSubmission($this->siteB->id);

        $this->actingAs($this->ownerA);

        $this->assertFalse($this->ownerA->can('view', $submissionOnB));
        $this->assertFalse($this->ownerA->can('update', $submissionOnB));
        $this->assertFalse($this->ownerA->can('delete', $submissionOnB));
    }

    public function test_orphaned_record_blocks_non_super_admin(): void
    {
        $orphan = $this->makeComment(null);

        $this->actingAs($this->ownerA);

        $this->assertFalse($this->ownerA->can('view', $orphan));

        $this->actingAs($this->superAdmin);

        $this->assertTrue($this->superAdmin->can('view', $orphan));
    }

    protected function seedRolesAndPermissions(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $siteOwner = Role::firstOrCreate(['name' => 'site_owner', 'guard_name' => 'web']);

        // Grant the Shield permissions the policy layers ownership on top of.
        foreach ([
            'ViewAny:CmsComment', 'View:CmsComment', 'Update:CmsComment', 'Delete:CmsComment',
            'Approve:CmsComment', 'Reject:CmsComment', 'MarkAsSpam:CmsComment',
            'ViewAny:TallcmsContactSubmission', 'View:TallcmsContactSubmission',
            'Update:TallcmsContactSubmission', 'Delete:TallcmsContactSubmission',
        ] as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $siteOwner->givePermissionTo($permission);
        }
    }

    protected function makeComment(?int $siteId): CmsComment
    {
        $postId = DB::table('tallcms_posts')->insertGetId([
            'site_id' => $siteId,
            'author_id' => $this->superAdmin->id,
            'title' => json_encode(['en' => 'Host post']),
            'slug' => json_encode(['en' => 'host-'.uniqid()]),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('tallcms_comments')->insertGetId([
            'site_id' => $siteId,
            'post_id' => $postId,
            'author_name' => 'Commenter',
            'author_email' => 'x@example.com',
            'content' => 'test',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return CmsComment::withoutGlobalScopes()->findOrFail($id);
    }

    protected function makeSubmission(int $siteId): TallcmsContactSubmission
    {
        $id = DB::table('tallcms_contact_submissions')->insertGetId([
            'site_id' => $siteId,
            'name' => 'Submitter',
            'email' => 'x@example.com',
            'form_data' => json_encode([]),
            'page_url' => 'http://x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TallcmsContactSubmission::withoutGlobalScopes()->findOrFail($id);
    }
}
