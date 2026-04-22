<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\TallcmsMenu;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

/**
 * Explicit site-ownership policy tests (Pass 3 of the multisite refactor).
 *
 * Rule:
 *   site.user_id === auth()->id()  OR  user->hasRole('super_admin')
 *
 * No collaborator granularity yet; that's a later pass.
 *
 * These tests cover Gate-level authorization for CmsPage / TallcmsMenu.
 * SitePolicy already enforces the rule; its own tests live in SitePlanTest
 * and SiteResourceTest.
 */
class MultisitePolicyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $ownerA;

    protected User $ownerB;

    protected User $stranger;

    protected Site $siteA;

    protected Site $siteB;

    protected int $pageInSiteA;

    protected int $pageInSiteB;

    protected int $menuInSiteA;

    protected int $menuInSiteB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        $this->seedPermissionsAndRoles();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('author');

        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('author');

        $this->stranger = User::factory()->create();
        $this->stranger->assignRole('author');

        $this->siteA = Site::create([
            'name' => 'Site A',
            'domain' => 'site-a.test',
            'is_default' => false,
            'is_active' => true,
            'user_id' => $this->ownerA->id,
        ]);

        $this->siteB = Site::create([
            'name' => 'Site B',
            'domain' => 'site-b.test',
            'is_default' => false,
            'is_active' => true,
            'user_id' => $this->ownerB->id,
        ]);

        $this->pageInSiteA = $this->insertPage($this->siteA->id, 'Page on A', $this->ownerA->id);
        $this->pageInSiteB = $this->insertPage($this->siteB->id, 'Page on B', $this->ownerB->id);
        $this->menuInSiteA = $this->insertMenu($this->siteA->id, 'Menu on A');
        $this->menuInSiteB = $this->insertMenu($this->siteB->id, 'Menu on B');
    }

    protected function seedPermissionsAndRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $author = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);

        // Grant the author role the Shield permissions the policy layers
        // ownership on top of. The ownership check is independent of the
        // Shield permission: you need BOTH to pass.
        foreach ([
            'ViewAny:CmsPage', 'View:CmsPage', 'Update:CmsPage', 'Delete:CmsPage',
            'ViewAny:TallcmsMenu', 'View:TallcmsMenu', 'Update:TallcmsMenu', 'Delete:TallcmsMenu',
        ] as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $author->givePermissionTo($permission);
        }
    }

    protected function insertPage(int $siteId, string $title, int $authorId): int
    {
        return DB::table('tallcms_pages')->insertGetId([
            'site_id' => $siteId,
            'title' => json_encode(['en' => $title]),
            'slug' => json_encode(['en' => Str::slug($title).'-'.uniqid()]),
            'status' => 'published',
            'is_homepage' => false,
            'sort_order' => 0,
            'author_id' => $authorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertMenu(int $siteId, string $name): int
    {
        return DB::table('tallcms_menus')->insertGetId([
            'site_id' => $siteId,
            'name' => $name,
            'location' => Str::slug($name),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------
    // CmsPage — ownership
    // -------------------------------------------------------

    public function test_owner_can_update_their_sites_page(): void
    {
        $page = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteA);

        $this->assertTrue($this->ownerA->can('update', $page));
    }

    public function test_non_owner_cannot_update_another_users_page(): void
    {
        $page = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteA);

        $this->assertFalse($this->ownerB->can('update', $page));
        $this->assertFalse($this->stranger->can('update', $page));
    }

    public function test_super_admin_can_update_any_page(): void
    {
        $pageA = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteA);
        $pageB = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteB);

        $this->assertTrue($this->superAdmin->can('update', $pageA));
        $this->assertTrue($this->superAdmin->can('update', $pageB));
    }

    public function test_owner_cannot_view_another_users_page(): void
    {
        $page = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteB);

        $this->assertFalse($this->ownerA->can('view', $page));
    }

    public function test_owner_can_delete_their_page_and_not_others(): void
    {
        $pageA = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteA);
        $pageB = CmsPage::withoutGlobalScopes()->findOrFail($this->pageInSiteB);

        $this->assertTrue($this->ownerA->can('delete', $pageA));
        $this->assertFalse($this->ownerA->can('delete', $pageB));
    }

    public function test_orphaned_page_denied_to_non_super_admin(): void
    {
        $orphanId = DB::table('tallcms_pages')->insertGetId([
            'site_id' => null,
            'title' => json_encode(['en' => 'Orphan']),
            'slug' => json_encode(['en' => 'orphan-'.uniqid()]),
            'status' => 'draft',
            'is_homepage' => false,
            'sort_order' => 0,
            'author_id' => $this->ownerA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orphan = CmsPage::withoutGlobalScopes()->findOrFail($orphanId);

        $this->assertFalse($this->ownerA->can('update', $orphan));
        $this->assertTrue($this->superAdmin->can('update', $orphan));
    }

    // -------------------------------------------------------
    // TallcmsMenu — ownership
    // -------------------------------------------------------

    public function test_owner_can_update_their_sites_menu(): void
    {
        $menu = TallcmsMenu::withoutGlobalScopes()->findOrFail($this->menuInSiteA);

        $this->assertTrue($this->ownerA->can('update', $menu));
    }

    public function test_non_owner_cannot_update_another_users_menu(): void
    {
        $menu = TallcmsMenu::withoutGlobalScopes()->findOrFail($this->menuInSiteA);

        $this->assertFalse($this->ownerB->can('update', $menu));
    }

    public function test_super_admin_can_update_any_menu(): void
    {
        $menuA = TallcmsMenu::withoutGlobalScopes()->findOrFail($this->menuInSiteA);
        $menuB = TallcmsMenu::withoutGlobalScopes()->findOrFail($this->menuInSiteB);

        $this->assertTrue($this->superAdmin->can('update', $menuA));
        $this->assertTrue($this->superAdmin->can('update', $menuB));
    }

    public function test_owner_can_delete_their_menu_and_not_others(): void
    {
        $menuA = TallcmsMenu::withoutGlobalScopes()->findOrFail($this->menuInSiteA);
        $menuB = TallcmsMenu::withoutGlobalScopes()->findOrFail($this->menuInSiteB);

        $this->assertTrue($this->ownerA->can('delete', $menuA));
        $this->assertFalse($this->ownerA->can('delete', $menuB));
    }

    // -------------------------------------------------------
    // Route-level: the edit page honors the policy
    // -------------------------------------------------------

    public function test_non_owner_hitting_edit_url_gets_denied(): void
    {
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $this->actingAs($this->ownerA);

        // ownerA tries to edit a page on ownerB's site via direct URL.
        $response = $this->get("/{$panelPath}/cms-pages/{$this->pageInSiteB}/edit");

        // Filament returns 403 when policy denies (not 404).
        $this->assertTrue(
            in_array($response->status(), [403, 404], true),
            "Expected 403/404 when non-owner hits other-site edit URL, got {$response->status()}"
        );
        $this->assertNotEquals(200, $response->status());
    }

    public function test_owner_hitting_edit_url_for_their_page_gets_through(): void
    {
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $this->actingAs($this->ownerA);

        $this->get("/{$panelPath}/cms-pages/{$this->pageInSiteA}/edit")
            ->assertOk();
    }

    public function test_super_admin_hitting_any_edit_url_gets_through(): void
    {
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $this->actingAs($this->superAdmin);

        $this->get("/{$panelPath}/cms-pages/{$this->pageInSiteB}/edit")
            ->assertOk();
    }
}
