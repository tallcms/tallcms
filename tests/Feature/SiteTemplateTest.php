<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Models\SitePlan;
use Tallcms\Multisite\Models\SiteTemplate;
use Tallcms\Multisite\Models\TemplateCategory;
use Tallcms\Multisite\Policies\SitePolicy;
use Tallcms\Multisite\Policies\SiteTemplatePolicy;
use Tallcms\Multisite\Services\SitePlanService;
use Tallcms\Multisite\Services\TemplateCloneService;
use Tests\TestCase;

class SiteTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularUser;

    protected Site $sourceSite;

    protected TemplateCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('author');

        // Create a super-admin-owned source site
        $this->sourceSite = $this->createSite([
            'name' => 'Template Source',
            'domain' => 'template-source.test',
            'user_id' => $this->superAdmin->id,
        ]);

        // Add a page to the source site
        DB::table('tallcms_pages')->insert([
            'site_id' => $this->sourceSite->id,
            'title' => json_encode(['en' => 'Home']),
            'slug' => json_encode(['en' => '/']),
            'content' => null,
            'status' => 'published',
            'is_homepage' => true,
            'sort_order' => 0,
            'author_id' => $this->superAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->category = TemplateCategory::firstOrCreate(
            ['slug' => 'restaurant'],
            ['name' => 'Restaurant', 'is_active' => true, 'sort_order' => 0]
        );
    }

    protected function createSite(array $attrs = []): Site
    {
        return Site::create(array_merge([
            'name' => 'Test Site',
            'domain' => 'test-'.Str::random(8).'.test',
            'is_default' => false,
            'is_active' => true,
            'user_id' => $this->superAdmin->id,
        ], $attrs));
    }

    protected function createTemplate(array $attrs = []): SiteTemplate
    {
        $template = SiteTemplate::create(array_merge([
            'name' => 'Test Template',
            'slug' => 'test-template-'.Str::random(6),
            'description' => 'A test template',
            'site_id' => $this->sourceSite->id,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'created_by' => $this->superAdmin->id,
        ], $attrs));

        $template->categories()->sync([$this->category->id]);

        return $template;
    }

    // -------------------------------------------------------
    // 1. Super-admin can CRUD templates
    // -------------------------------------------------------

    public function test_super_admin_can_create_template(): void
    {
        $this->actingAs($this->superAdmin);

        $template = $this->createTemplate([
            'name' => 'Restaurant Starter',
            'slug' => 'restaurant-starter',
        ]);

        $this->assertDatabaseHas('tallcms_site_templates', [
            'id' => $template->id,
            'name' => 'Restaurant Starter',
            'slug' => 'restaurant-starter',
            'site_id' => $this->sourceSite->id,
        ]);
    }

    public function test_super_admin_can_update_template(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        $template->update(['name' => 'Updated Name', 'is_featured' => true]);

        $this->assertDatabaseHas('tallcms_site_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'is_featured' => true,
        ]);
    }

    public function test_super_admin_can_delete_template(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();
        $templateId = $template->id;

        $template->delete();

        $this->assertDatabaseMissing('tallcms_site_templates', ['id' => $templateId]);
    }

    // -------------------------------------------------------
    // 2. Regular user cannot create/edit/delete templates
    // -------------------------------------------------------

    public function test_regular_user_cannot_create_template_via_policy(): void
    {
        $policy = new SiteTemplatePolicy;

        $this->assertFalse($policy->create($this->regularUser));
        $this->assertTrue($policy->create($this->superAdmin));
    }

    public function test_regular_user_cannot_update_template_via_policy(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        $policy = new SiteTemplatePolicy;

        $this->assertFalse($policy->update($this->regularUser, $template));
        $this->assertTrue($policy->update($this->superAdmin, $template));
    }

    public function test_regular_user_cannot_delete_template_via_policy(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        $policy = new SiteTemplatePolicy;

        $this->assertFalse($policy->delete($this->regularUser, $template));
        $this->assertTrue($policy->delete($this->superAdmin, $template));
    }

    // -------------------------------------------------------
    // 3. Gallery shows only published templates
    // -------------------------------------------------------

    public function test_gallery_shows_only_published_templates(): void
    {
        $this->actingAs($this->superAdmin);

        $published = $this->createTemplate(['name' => 'Published', 'is_published' => true]);
        $draft = $this->createTemplate(['name' => 'Draft', 'slug' => 'draft', 'is_published' => false]);

        $templates = SiteTemplate::published()->get();

        $this->assertTrue($templates->contains('id', $published->id));
        $this->assertFalse($templates->contains('id', $draft->id));
    }

    public function test_resource_policy_blocks_regular_users(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        $policy = new SiteTemplatePolicy;

        // Resource viewAny is super-admin-only
        $this->assertFalse($policy->viewAny($this->regularUser));
        $this->assertFalse($policy->view($this->regularUser, $template));

        $this->assertTrue($policy->viewAny($this->superAdmin));
        $this->assertTrue($policy->view($this->superAdmin, $template));
    }

    // -------------------------------------------------------
    // 4. Clone from template creates independent site
    // -------------------------------------------------------

    public function test_clone_from_template_creates_independent_site(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        $cloneService = app(TemplateCloneService::class);
        $newSite = $cloneService->cloneForUser(
            $template,
            $this->regularUser,
            'My Restaurant',
            'my-restaurant.test'
        );

        // Site was created
        $this->assertDatabaseHas('tallcms_sites', [
            'id' => $newSite->id,
            'name' => 'My Restaurant',
            'domain' => 'my-restaurant.test',
            'user_id' => $this->regularUser->id,
            'is_template_source' => false,
        ]);

        // Pages were cloned with remapped author_id
        $clonedPages = DB::table('tallcms_pages')
            ->where('site_id', $newSite->id)
            ->get();

        $this->assertGreaterThan(0, $clonedPages->count());

        foreach ($clonedPages as $page) {
            $this->assertEquals($this->regularUser->id, $page->author_id,
                'Cloned page author_id should be remapped to the target user');
        }

        // Source site is unaffected
        $sourcePages = DB::table('tallcms_pages')
            ->where('site_id', $this->sourceSite->id)
            ->get();

        $this->assertGreaterThan(0, $sourcePages->count());
        foreach ($sourcePages as $page) {
            $this->assertEquals($this->superAdmin->id, $page->author_id,
                'Source page author_id should remain unchanged');
        }
    }

    // -------------------------------------------------------
    // 5. Quota enforcement on clone
    // -------------------------------------------------------

    public function test_quota_enforcement_on_template_clone(): void
    {
        // Create a plan with max 1 site
        $plan = SitePlan::where('slug', 'free')->first()
            ?? SitePlan::create([
                'name' => 'Free',
                'slug' => 'free',
                'max_sites' => 1,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]);

        // User already has one site (at quota limit)
        $this->createSite([
            'domain' => 'existing-user-site.test',
            'user_id' => $this->regularUser->id,
        ]);

        $this->actingAs($this->regularUser);
        $template = $this->createTemplate();

        $cloneService = app(TemplateCloneService::class);

        $this->expectException(\Tallcms\Multisite\Exceptions\SiteQuotaExceededException::class);

        $cloneService->cloneForUser(
            $template,
            $this->regularUser,
            'Over Quota Site',
            'over-quota.test'
        );
    }

    // -------------------------------------------------------
    // 6. Source site hidden from tenants
    // -------------------------------------------------------

    public function test_source_site_hidden_from_non_super_admins(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        // Source site should be marked as template source
        $this->sourceSite->refresh();
        $this->assertTrue($this->sourceSite->is_template_source);

        // Simulate the SiteResource query for non-super-admins
        $visibleSites = Site::where('user_id', $this->regularUser->id)
            ->where('is_template_source', false)
            ->get();

        $this->assertFalse($visibleSites->contains('id', $this->sourceSite->id));
    }

    // -------------------------------------------------------
    // 7. Template deletion preserves source site
    // -------------------------------------------------------

    public function test_template_deletion_preserves_source_site(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();
        $siteId = $this->sourceSite->id;

        // Verify flag is set
        $this->sourceSite->refresh();
        $this->assertTrue($this->sourceSite->is_template_source);

        // Delete the template
        $template->delete();

        // Source site still exists
        $this->assertDatabaseHas('tallcms_sites', ['id' => $siteId]);

        // Flag cleared (no more templates reference this site)
        $this->sourceSite->refresh();
        $this->assertFalse($this->sourceSite->is_template_source);
    }

    // -------------------------------------------------------
    // 8. Source site deletion blocked while template exists
    // -------------------------------------------------------

    public function test_source_site_deletion_blocked_by_policy(): void
    {
        $this->actingAs($this->superAdmin);
        $this->createTemplate();

        $this->sourceSite->refresh();
        $this->assertTrue($this->sourceSite->is_template_source);

        $policy = new SitePolicy;
        $this->assertFalse($policy->delete($this->superAdmin, $this->sourceSite));
    }

    // -------------------------------------------------------
    // 9. Category filtering works
    // -------------------------------------------------------

    public function test_category_filtering(): void
    {
        $this->actingAs($this->superAdmin);

        $lawCategory = TemplateCategory::firstOrCreate(
            ['slug' => 'law-office'],
            ['name' => 'Law Office', 'is_active' => true]
        );

        $restaurantTemplate = $this->createTemplate([
            'name' => 'Restaurant',
            'slug' => 'restaurant-tmpl',
        ]);
        // createTemplate already syncs $this->category; no extra sync needed

        $lawSite = $this->createSite(['domain' => 'law-template.test']);
        DB::table('tallcms_pages')->insert([
            'site_id' => $lawSite->id,
            'title' => json_encode(['en' => 'Home']),
            'slug' => json_encode(['en' => '/']),
            'content' => null,
            'status' => 'published',
            'is_homepage' => true,
            'sort_order' => 0,
            'author_id' => $this->superAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lawTemplate = $this->createTemplate([
            'name' => 'Law Office',
            'slug' => 'law-office-tmpl',
            'site_id' => $lawSite->id,
        ]);
        $lawTemplate->categories()->sync([$lawCategory->id]);

        // Filter by restaurant category
        $filtered = SiteTemplate::published()
            ->whereHas('categories', fn ($q) => $q->where('tallcms_template_categories.id', $this->category->id))
            ->get();

        $this->assertTrue($filtered->contains('id', $restaurantTemplate->id));
        $this->assertFalse($filtered->contains('id', $lawTemplate->id));

        // Search by name
        $searched = SiteTemplate::published()
            ->where('name', 'like', '%Law%')
            ->get();

        $this->assertFalse($searched->contains('id', $restaurantTemplate->id));
        $this->assertTrue($searched->contains('id', $lawTemplate->id));

        // Featured filter
        $restaurantTemplate->update(['is_featured' => true]);
        $featured = SiteTemplate::published()->featured()->get();

        $this->assertTrue($featured->contains('id', $restaurantTemplate->id));
        $this->assertFalse($featured->contains('id', $lawTemplate->id));
    }

    // -------------------------------------------------------
    // 10. Source site provenance enforced
    // -------------------------------------------------------

    public function test_cannot_create_template_with_non_super_admin_source(): void
    {
        $this->actingAs($this->superAdmin);

        // Create a site owned by regular user
        $userSite = $this->createSite([
            'domain' => 'user-site.test',
            'user_id' => $this->regularUser->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template source sites must be owned by a super-admin.');

        SiteTemplate::create([
            'name' => 'Bad Template',
            'slug' => 'bad-template',
            'site_id' => $userSite->id,
            'is_published' => false,
        ]);
    }

    public function test_cannot_create_template_with_ownerless_source(): void
    {
        $this->actingAs($this->superAdmin);

        // Create a site with no owner (user_id = null)
        DB::table('tallcms_sites')->insert([
            'name' => 'Ownerless Site',
            'domain' => 'ownerless-'.Str::random(6).'.test',
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ownerlessSite = Site::where('domain', 'like', 'ownerless-%')->first();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template source sites must be owned by a super-admin.');

        SiteTemplate::create([
            'name' => 'Ownerless Template',
            'slug' => 'ownerless-template',
            'site_id' => $ownerlessSite->id,
            'is_published' => false,
        ]);
    }

    // -------------------------------------------------------
    // 11. Preview route requires auth and respects publish state
    // -------------------------------------------------------

    public function test_preview_requires_authentication(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate();

        // Unauthenticated request — controller returns 403
        auth()->logout();
        $response = $this->get(route('plugin.tallcms.multisite.template.preview', $template));

        $response->assertForbidden();
    }

    public function test_preview_blocks_unpublished_for_regular_users(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate(['is_published' => false, 'slug' => 'draft-preview']);

        $this->actingAs($this->regularUser);
        $response = $this->get(route('plugin.tallcms.multisite.template.preview', $template));

        $response->assertForbidden();
    }

    public function test_preview_allows_published_for_regular_users(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate(['is_published' => true, 'slug' => 'pub-preview']);

        $this->actingAs($this->regularUser);
        $response = $this->get(route('plugin.tallcms.multisite.template.preview', $template));

        $response->assertOk();
    }

    // -------------------------------------------------------
    // 12. Preview homepage resolution
    // -------------------------------------------------------

    public function test_preview_renders_homepage(): void
    {
        $this->actingAs($this->superAdmin);
        $template = $this->createTemplate(['slug' => 'homepage-test']);

        $response = $this->get(route('plugin.tallcms.multisite.template.preview', $template));

        $response->assertOk();
    }

    public function test_preview_shows_unavailable_when_no_pages(): void
    {
        $this->actingAs($this->superAdmin);

        // Create a super-admin-owned source site with no pages
        $emptySite = $this->createSite([
            'domain' => 'empty-source.test',
            'user_id' => $this->superAdmin->id,
        ]);
        $template = $this->createTemplate([
            'slug' => 'empty-template',
            'site_id' => $emptySite->id,
        ]);

        $response = $this->get(route('plugin.tallcms.multisite.template.preview', $template));

        $response->assertOk();
        $response->assertSee('Preview Unavailable');
    }

    // -------------------------------------------------------
    // 13. Multiple templates sharing one source site
    // -------------------------------------------------------

    public function test_multiple_templates_sharing_source_site(): void
    {
        $this->actingAs($this->superAdmin);

        $template1 = $this->createTemplate(['name' => 'Template 1', 'slug' => 'tmpl-1']);
        $template2 = $this->createTemplate(['name' => 'Template 2', 'slug' => 'tmpl-2']);

        $this->sourceSite->refresh();
        $this->assertTrue($this->sourceSite->is_template_source);

        // Delete first template — flag should stay
        $template1->delete();
        $this->sourceSite->refresh();
        $this->assertTrue($this->sourceSite->is_template_source,
            'Flag should remain while other templates still reference the site');

        // Delete second template — flag should clear
        $template2->delete();
        $this->sourceSite->refresh();
        $this->assertFalse($this->sourceSite->is_template_source,
            'Flag should clear when no templates reference the site');
    }

    // -------------------------------------------------------
    // 14. Template auto-generates slug
    // -------------------------------------------------------

    public function test_template_auto_generates_slug(): void
    {
        $this->actingAs($this->superAdmin);

        $template = SiteTemplate::create([
            'name' => 'My Great Template',
            'site_id' => $this->sourceSite->id,
            'is_published' => false,
        ]);

        $this->assertEquals('my-great-template', $template->slug);
    }

    // -------------------------------------------------------
    // Source site is_template_source flag sync
    // -------------------------------------------------------

    public function test_creating_template_sets_source_flag(): void
    {
        $this->actingAs($this->superAdmin);

        $freshSite = $this->createSite([
            'domain' => 'fresh-flag-test.test',
            'user_id' => $this->superAdmin->id,
        ]);
        DB::table('tallcms_pages')->insert([
            'site_id' => $freshSite->id,
            'title' => json_encode(['en' => 'Home']),
            'slug' => json_encode(['en' => '/']),
            'content' => null,
            'status' => 'published',
            'is_homepage' => true,
            'sort_order' => 0,
            'author_id' => $this->superAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $freshSite->refresh();
        $this->assertFalse($freshSite->is_template_source);

        SiteTemplate::create([
            'name' => 'Flag Test Template',
            'slug' => 'flag-test-template',
            'site_id' => $freshSite->id,
            'is_published' => false,
            'created_by' => $this->superAdmin->id,
        ]);

        $freshSite->refresh();
        $this->assertTrue($freshSite->is_template_source);
    }

    // -------------------------------------------------------
    // Template source sites excluded from quota count
    // -------------------------------------------------------

    public function test_template_source_excluded_from_quota(): void
    {
        // Mark source site as template source
        $this->sourceSite->update(['is_template_source' => true]);

        $planService = app(SitePlanService::class);

        // Source site should not count toward the super-admin's quota
        $count = $planService->siteCount($this->superAdmin);

        // The super-admin has the source site but it shouldn't count
        $nonTemplateSites = Site::withoutGlobalScopes()
            ->where('user_id', $this->superAdmin->id)
            ->where('is_template_source', false)
            ->count();

        $this->assertEquals($nonTemplateSites, $count);
    }
}
