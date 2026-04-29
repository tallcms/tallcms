<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tallcms\Multisite\Exceptions\SiteQuotaExceededException;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Models\SitePlan;
use Tallcms\Multisite\Models\UserSitePlan;
use Tallcms\Multisite\Services\SitePlanService;
use Tallcms\Multisite\Support\QuotaDecision;
use Tests\TestCase;

class SitePlanTest extends TestCase
{
    use RefreshDatabase;

    protected SitePlanService $planService;

    protected SitePlan $freePlan;

    protected SitePlan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        $this->planService = app(SitePlanService::class);

        // Ensure super_admin role exists for Spatie Permission
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // The migration seeds a default "Free" plan — use it
        $this->freePlan = SitePlan::where('slug', 'free')->first()
            ?? SitePlan::create([
                'name' => 'Free',
                'slug' => 'free',
                'max_sites' => 1,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $this->proPlan = SitePlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'max_sites' => 5,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function createUser(string $role = 'panel_user'): User
    {
        $user = User::factory()->create();

        if ($role === 'super_admin') {
            $user->assignRole('super_admin');
        }

        return $user;
    }

    /**
     * Insert a site via raw DB to bypass model events (uuid, quota safety net).
     */
    protected function insertSite(array $attrs): void
    {
        DB::table('tallcms_sites')->insert(array_merge([
            'name' => 'Test Site',
            'domain' => 'test-'.Str::random(8).'.test',
            'uuid' => (string) Str::uuid(),
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    // -------------------------------------------------------
    // SitePlan Model
    // -------------------------------------------------------

    public function test_only_one_default_plan(): void
    {
        $this->proPlan->update(['is_default' => true]);

        $this->freePlan->refresh();
        $this->assertFalse($this->freePlan->is_default);
        $this->assertTrue($this->proPlan->is_default);
    }

    public function test_get_default_plan(): void
    {
        $default = SitePlan::getDefault();
        $this->assertEquals('free', $default->slug);
    }

    public function test_is_unlimited(): void
    {
        $unlimited = SitePlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_sites' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($unlimited->isUnlimited());
        $this->assertFalse($this->freePlan->isUnlimited());
    }

    public function test_has_assigned_users(): void
    {
        $this->assertFalse($this->freePlan->hasAssignedUsers());

        $user = $this->createUser();
        $this->planService->assignPlan($user, $this->freePlan);

        $this->assertTrue($this->freePlan->hasAssignedUsers());
    }

    // -------------------------------------------------------
    // QuotaDecision
    // -------------------------------------------------------

    public function test_quota_decision_allowed(): void
    {
        $decision = QuotaDecision::allowed();
        $this->assertTrue($decision->isAllowed());
        $this->assertNull($decision->reason);
    }

    public function test_quota_decision_denied(): void
    {
        $decision = QuotaDecision::denied('Over quota');
        $this->assertFalse($decision->isAllowed());
        $this->assertEquals('Over quota', $decision->reason);
    }

    // -------------------------------------------------------
    // SitePlanService: Plan Resolution
    // -------------------------------------------------------

    public function test_unassigned_user_gets_default_plan(): void
    {
        $user = $this->createUser();
        $plan = $this->planService->getPlanForUser($user);

        $this->assertEquals($this->freePlan->id, $plan->id);
    }

    public function test_assigned_user_gets_their_plan(): void
    {
        $user = $this->createUser();
        $this->planService->assignPlan($user, $this->proPlan);

        $plan = $this->planService->getPlanForUser($user);
        $this->assertEquals($this->proPlan->id, $plan->id);
    }

    public function test_assign_plan_upserts(): void
    {
        $user = $this->createUser();
        $this->planService->assignPlan($user, $this->freePlan);
        $this->planService->assignPlan($user, $this->proPlan, auth()->id());

        $this->assertCount(1, UserSitePlan::where('user_id', $user->id)->get());
        $this->assertEquals($this->proPlan->id, $this->planService->getPlanForUser($user)->id);
    }

    // -------------------------------------------------------
    // SitePlanService: ensureAssignment
    // -------------------------------------------------------

    public function test_ensure_assignment_creates_row(): void
    {
        $user = $this->createUser();
        $this->assertNull(UserSitePlan::where('user_id', $user->id)->first());

        $assignment = $this->planService->ensureAssignment($user);

        $this->assertNotNull($assignment);
        $this->assertEquals($this->freePlan->id, $assignment->site_plan_id);
        $this->assertDatabaseHas('tallcms_user_site_plans', [
            'user_id' => $user->id,
            'site_plan_id' => $this->freePlan->id,
        ]);
    }

    public function test_ensure_assignment_is_idempotent(): void
    {
        $user = $this->createUser();

        $first = $this->planService->ensureAssignment($user);
        $second = $this->planService->ensureAssignment($user);

        $this->assertEquals($first->id, $second->id);
        $this->assertCount(1, UserSitePlan::where('user_id', $user->id)->get());
    }

    public function test_ensure_assignment_returns_null_without_default_plan(): void
    {
        SitePlan::query()->delete();

        $user = $this->createUser();
        $result = $this->planService->ensureAssignment($user);

        $this->assertNull($result);
    }

    // -------------------------------------------------------
    // SitePlanService: Quota Decision
    // -------------------------------------------------------

    public function test_super_admin_always_allowed(): void
    {
        $admin = $this->createUser('super_admin');
        $decision = $this->planService->resolveQuotaDecision($admin);

        $this->assertTrue($decision->isAllowed());
    }

    public function test_user_under_quota_allowed(): void
    {
        $user = $this->createUser();
        // Free plan = 1 site, user has 0
        $decision = $this->planService->resolveQuotaDecision($user);

        $this->assertTrue($decision->isAllowed());
    }

    public function test_user_at_quota_denied(): void
    {
        $user = $this->createUser();
        $this->insertSite([
            'name' => 'User Site',
            'domain' => 'user.test',
            'user_id' => $user->id,
        ]);

        // Free plan = 1 site, user has 1
        $decision = $this->planService->resolveQuotaDecision($user);

        $this->assertFalse($decision->isAllowed());
        $this->assertStringContainsString('1 site(s)', $decision->reason);
    }

    public function test_unlimited_plan_always_allowed(): void
    {
        $unlimited = SitePlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_sites' => null,
            'is_active' => true,
        ]);

        $user = $this->createUser();
        $this->planService->assignPlan($user, $unlimited);

        // Create several sites
        for ($i = 0; $i < 10; $i++) {
            $this->insertSite([
                'name' => "Site {$i}",
                'domain' => "site-{$i}.test",
                'user_id' => $user->id,
            ]);
        }

        $this->assertTrue($this->planService->canCreateSite($user));
    }

    public function test_strict_mode_denies_without_plan(): void
    {
        config(['tallcms.multisite.quota_enforcement' => 'strict']);
        SitePlan::query()->delete();

        $user = $this->createUser();
        $decision = $this->planService->resolveQuotaDecision($user);

        $this->assertFalse($decision->isAllowed());
    }

    public function test_permissive_mode_allows_without_plan(): void
    {
        config(['tallcms.multisite.quota_enforcement' => 'permissive']);
        SitePlan::query()->delete();

        $user = $this->createUser();
        $decision = $this->planService->resolveQuotaDecision($user);

        $this->assertTrue($decision->isAllowed());
    }

    // -------------------------------------------------------
    // SitePlanService: createSiteWithQuota
    // -------------------------------------------------------

    public function test_create_site_with_quota_succeeds_under_limit(): void
    {
        $user = $this->createUser();

        $site = $this->planService->createSiteWithQuota($user, [
            'name' => 'My Site',
            'domain' => 'my-site.test',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Site::class, $site);
        $this->assertEquals($user->id, $site->user_id);
        $this->assertDatabaseHas('tallcms_sites', ['domain' => 'my-site.test']);
    }

    public function test_create_site_with_quota_fails_at_limit(): void
    {
        $user = $this->createUser();

        // Use up the free plan's single site
        $this->planService->createSiteWithQuota($user, [
            'name' => 'First Site',
            'domain' => 'first.test',
            'is_active' => true,
        ]);

        $this->expectException(SiteQuotaExceededException::class);

        $this->planService->createSiteWithQuota($user, [
            'name' => 'Second Site',
            'domain' => 'second.test',
            'is_active' => true,
        ]);
    }

    public function test_create_site_with_quota_creates_assignment_row(): void
    {
        $user = $this->createUser();
        $this->assertNull(UserSitePlan::where('user_id', $user->id)->first());

        $this->planService->createSiteWithQuota($user, [
            'name' => 'My Site',
            'domain' => 'my-site.test',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('tallcms_user_site_plans', [
            'user_id' => $user->id,
            'site_plan_id' => $this->freePlan->id,
        ]);
    }

    public function test_permissive_mode_create_works_without_default_plan(): void
    {
        config(['tallcms.multisite.quota_enforcement' => 'permissive']);
        SitePlan::query()->delete();

        $user = $this->createUser();

        $site = $this->planService->createSiteWithQuota($user, [
            'name' => 'No Plan Site',
            'domain' => 'noplan.test',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Site::class, $site);
    }

    // -------------------------------------------------------
    // Over-Quota Grandfathering
    // -------------------------------------------------------

    public function test_over_quota_detected_after_downgrade(): void
    {
        $user = $this->createUser();
        $this->planService->assignPlan($user, $this->proPlan);

        // Create 3 sites on Pro plan
        for ($i = 0; $i < 3; $i++) {
            $this->insertSite([
                'name' => "Site {$i}",
                'domain' => "site-{$i}.test",
                'user_id' => $user->id,
            ]);
        }

        $this->assertFalse($this->planService->isOverQuota($user));

        // Downgrade to Free (max 1)
        $this->planService->assignPlan($user, $this->freePlan);

        $this->assertTrue($this->planService->isOverQuota($user));
        $this->assertFalse($this->planService->canCreateSite($user));
        $this->assertEquals(3, $this->planService->siteCount($user));
    }

    // -------------------------------------------------------
    // SitePolicy
    // -------------------------------------------------------

    public function test_policy_allows_super_admin_to_create(): void
    {
        $admin = $this->createUser('super_admin');
        $this->assertTrue($admin->can('create', Site::class));
    }

    public function test_policy_blocks_user_at_quota(): void
    {
        $user = $this->createUser();
        $this->insertSite([
            'name' => 'User Site',
            'domain' => 'user.test',
            'user_id' => $user->id,
        ]);

        $this->assertFalse($user->can('create', Site::class));
    }

    public function test_policy_allows_user_under_quota(): void
    {
        $user = $this->createUser();
        $this->assertTrue($user->can('create', Site::class));
    }

    // -------------------------------------------------------
    // Safe Plan Deletion
    // -------------------------------------------------------

    public function test_plan_with_users_cannot_be_deleted(): void
    {
        $user = $this->createUser();
        $this->planService->assignPlan($user, $this->proPlan);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->proPlan->delete();
    }

    public function test_plan_without_users_can_be_deleted(): void
    {
        $this->proPlan->delete();
        $this->assertDatabaseMissing('tallcms_site_plans', ['id' => $this->proPlan->id]);
    }

    // -------------------------------------------------------
    // Remaining Quota
    // -------------------------------------------------------

    public function test_remaining_quota(): void
    {
        $user = $this->createUser();
        $this->planService->assignPlan($user, $this->proPlan);

        $this->assertEquals(5, $this->planService->remainingQuota($user));

        $this->insertSite([
            'name' => 'Site 1',
            'domain' => 'site-1.test',
            'user_id' => $user->id,
        ]);

        $this->assertEquals(4, $this->planService->remainingQuota($user));
    }

    public function test_unlimited_plan_remaining_quota_is_null(): void
    {
        $unlimited = SitePlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_sites' => null,
            'is_active' => true,
        ]);

        $user = $this->createUser();
        $this->planService->assignPlan($user, $unlimited);

        $this->assertNull($this->planService->remainingQuota($user));
    }
}
