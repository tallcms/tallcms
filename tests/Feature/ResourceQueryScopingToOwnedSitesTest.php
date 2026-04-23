<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Filament\Resources\CmsComments\CmsCommentResource;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

/**
 * Regression test for site-owner list-query scoping.
 *
 * Filament policies gate record-level access, but list queries bypass
 * policy checks entirely — the resource just hits the database and renders
 * whatever comes back. Without a query-level filter, a site_owner's
 * Comments / Contact Submissions pages listed every tenant's records,
 * even though the policy would block them from clicking in. Horizontal
 * information disclosure.
 *
 * These tests fail if that regression ever creeps back in.
 */
class ResourceQueryScopingToOwnedSitesTest extends TestCase
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

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'site_owner', 'guard_name' => 'web']);

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

    public function test_site_owner_comment_list_is_scoped_to_their_sites(): void
    {
        $postA = $this->insertPost($this->siteA->id);
        $postB = $this->insertPost($this->siteB->id);

        $this->insertComment($this->siteA->id, $postA, 'A-comment');
        $this->insertComment($this->siteB->id, $postB, 'B-comment');

        $this->actingAs($this->ownerA);

        $visible = CmsCommentResource::getEloquentQuery()->pluck('content')->all();

        $this->assertContains('A-comment', $visible);
        $this->assertNotContains('B-comment', $visible);
    }

    public function test_site_owner_submission_list_is_scoped_to_their_sites(): void
    {
        $this->insertSubmission($this->siteA->id, 'A-user');
        $this->insertSubmission($this->siteB->id, 'B-user');

        $this->actingAs($this->ownerB);

        $visible = TallcmsContactSubmissionResource::getEloquentQuery()->pluck('name')->all();

        $this->assertContains('B-user', $visible);
        $this->assertNotContains('A-user', $visible);
    }

    public function test_super_admin_sees_everything(): void
    {
        $postA = $this->insertPost($this->siteA->id);
        $postB = $this->insertPost($this->siteB->id);
        $this->insertComment($this->siteA->id, $postA, 'A-comment');
        $this->insertComment($this->siteB->id, $postB, 'B-comment');

        $this->actingAs($this->superAdmin);

        $visible = CmsCommentResource::getEloquentQuery()->pluck('content')->all();

        $this->assertContains('A-comment', $visible);
        $this->assertContains('B-comment', $visible);
    }

    public function test_user_owning_no_sites_sees_nothing(): void
    {
        $postA = $this->insertPost($this->siteA->id);
        $this->insertComment($this->siteA->id, $postA, 'A-comment');

        $orphanedSiteOwner = User::factory()->create();
        $orphanedSiteOwner->assignRole('site_owner');

        $this->actingAs($orphanedSiteOwner);

        $this->assertSame(0, CmsCommentResource::getEloquentQuery()->count());
        $this->assertSame(0, TallcmsContactSubmissionResource::getEloquentQuery()->count());
    }

    protected function insertPost(int $siteId): int
    {
        return DB::table('tallcms_posts')->insertGetId([
            'site_id' => $siteId,
            'author_id' => $this->superAdmin->id,
            'title' => json_encode(['en' => 'Host post']),
            'slug' => json_encode(['en' => 'host-'.uniqid()]),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertComment(int $siteId, int $postId, string $content): void
    {
        DB::table('tallcms_comments')->insert([
            'site_id' => $siteId,
            'post_id' => $postId,
            'author_name' => 'Commenter',
            'author_email' => 'x@example.com',
            'content' => $content,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertSubmission(int $siteId, string $name): void
    {
        DB::table('tallcms_contact_submissions')->insert([
            'site_id' => $siteId,
            'name' => $name,
            'email' => 'x@example.com',
            'form_data' => json_encode([]),
            'page_url' => 'http://x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
