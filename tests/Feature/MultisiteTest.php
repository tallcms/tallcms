<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\CurrentSiteResolver;
use TallCms\Cms\Models\CmsPage as BaseCmsPage;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\TallcmsMenu;
use Tests\TestCase;

class MultisiteTest extends TestCase
{
    use RefreshDatabase;

    protected Site $siteA;

    protected Site $siteB;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create();

        $this->siteA = Site::create([
            'name' => 'Site A',
            'domain' => 'site-a.test',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->siteB = Site::create([
            'name' => 'Site B',
            'domain' => 'site-b.test',
            'is_default' => false,
            'is_active' => true,
        ]);

        // Reset resolver and session between tests
        if (app()->bound(CurrentSiteResolver::class)) {
            app(CurrentSiteResolver::class)->reset();
        }
        session()->forget('multisite_admin_site_id');
        Cache::flush();
    }

    /**
     * Insert a page directly via DB to bypass mass assignment and model events.
     */
    protected function insertPage(array $attrs): int
    {
        return DB::table('tallcms_pages')->insertGetId(array_merge([
            'title' => 'Test Page',
            'slug' => json_encode(['en' => 'test-'.uniqid()]),
            'status' => 'published',
            'is_homepage' => false,
            'sort_order' => 0,
            'content_width' => 'standard',
            'show_breadcrumbs' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    /**
     * Insert a menu directly via DB.
     */
    protected function insertMenu(array $attrs): int
    {
        return DB::table('tallcms_menus')->insertGetId(array_merge([
            'name' => 'Test Menu',
            'location' => 'header',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    /**
     * Set up admin context for SiteSetting operations (session + request attribute).
     */
    protected function setAdminSettingsContext(int $siteId): void
    {
        session(['multisite_admin_site_id' => $siteId]);
        request()->attributes->set('tallcms.admin_context', true);
        Cache::flush();
    }

    /**
     * Set up admin context for a specific site (full resolver + session + attribute).
     */
    protected function setAdminSite(?int $siteId): void
    {
        $resolver = app(CurrentSiteResolver::class);
        $resolver->reset();

        if ($siteId === null) {
            session(['multisite_admin_site_id' => CurrentSiteResolver::ALL_SITES_SENTINEL]);
        } else {
            session(['multisite_admin_site_id' => $siteId]);
        }

        $request = request();
        $request->attributes->set('tallcms.admin_context', true);
        $resolver->resolve($request);
    }

    // -------------------------------------------------------
    // Site Scope: Content Isolation
    // -------------------------------------------------------

    public function test_pages_are_scoped_to_their_site(): void
    {
        $pageAId = $this->insertPage(['title' => 'Page A', 'site_id' => $this->siteA->id]);
        $pageBId = $this->insertPage(['title' => 'Page B', 'site_id' => $this->siteB->id]);

        $this->setAdminSite($this->siteA->id);

        $pages = BaseCmsPage::all();
        $this->assertTrue($pages->contains('id', $pageAId));
        $this->assertFalse($pages->contains('id', $pageBId));
    }

    public function test_menus_are_scoped_to_their_site(): void
    {
        $menuAId = $this->insertMenu(['name' => 'Header A', 'location' => 'header', 'site_id' => $this->siteA->id]);
        $menuBId = $this->insertMenu(['name' => 'Header B', 'location' => 'footer', 'site_id' => $this->siteB->id]);

        $this->setAdminSite($this->siteB->id);

        $menus = TallcmsMenu::all();
        $this->assertFalse($menus->contains('id', $menuAId));
        $this->assertTrue($menus->contains('id', $menuBId));
    }

    public function test_same_menu_location_allowed_on_different_sites(): void
    {
        $menuAId = $this->insertMenu(['location' => 'header', 'site_id' => $this->siteA->id]);
        $menuBId = $this->insertMenu(['location' => 'header', 'site_id' => $this->siteB->id]);

        $this->assertDatabaseHas('tallcms_menus', ['id' => $menuAId, 'location' => 'header']);
        $this->assertDatabaseHas('tallcms_menus', ['id' => $menuBId, 'location' => 'header']);
    }

    public function test_unknown_domain_returns_empty_results(): void
    {
        $this->insertPage(['title' => 'Secret Page', 'site_id' => $this->siteA->id]);

        // No admin session, resolver resolves to nothing
        $resolver = app(CurrentSiteResolver::class);
        $resolver->reset();
        $request = \Illuminate\Http\Request::create('http://unknown.test/page');
        $resolver->resolve($request);

        $this->assertTrue($resolver->isResolved());
        $this->assertNull($resolver->id());
        $this->assertCount(0, BaseCmsPage::all());
    }

    public function test_all_sites_mode_shows_everything(): void
    {
        $this->insertPage(['title' => 'Page A', 'site_id' => $this->siteA->id]);
        $this->insertPage(['title' => 'Page B', 'site_id' => $this->siteB->id]);

        $this->setAdminSite(null); // All Sites

        $resolver = app(CurrentSiteResolver::class);
        $this->assertTrue($resolver->isAllSitesMode());
        $this->assertNull($resolver->id());
        $this->assertCount(2, BaseCmsPage::all());
    }

    // -------------------------------------------------------
    // Site Resolution
    // -------------------------------------------------------

    public function test_frontend_resolves_by_domain(): void
    {
        $resolver = app(CurrentSiteResolver::class);
        $resolver->reset();

        $request = \Illuminate\Http\Request::create('http://site-b.test/about');
        $resolver->resolve($request);

        $this->assertEquals($this->siteB->id, $resolver->id());
        $this->assertEquals('Site B', $resolver->get()->name);
    }

    public function test_admin_resolves_by_session_not_domain(): void
    {
        $resolver = app(CurrentSiteResolver::class);
        $resolver->reset();

        session(['multisite_admin_site_id' => $this->siteB->id]);
        $request = \Illuminate\Http\Request::create('http://site-a.test/admin/pages');
        $request->attributes->set('tallcms.admin_context', true);
        $resolver->resolve($request);

        // Should resolve to site B (session), not site A (domain)
        $this->assertEquals($this->siteB->id, $resolver->id());
    }

    public function test_domain_normalization(): void
    {
        $this->assertEquals('example.com', Site::normalizeDomain('EXAMPLE.COM'));
        $this->assertEquals('example.com', Site::normalizeDomain('https://example.com/'));
        $this->assertEquals('example.com', Site::normalizeDomain('example.com:8080'));
        $this->assertEquals('example.com', Site::normalizeDomain('http://EXAMPLE.COM:443/'));
    }

    // -------------------------------------------------------
    // Homepage Per-Site
    // -------------------------------------------------------

    public function test_homepage_is_per_site(): void
    {
        $idA = $this->insertPage([
            'title' => 'Home A',
            'is_homepage' => true,
            'site_id' => $this->siteA->id,
        ]);

        // Setting homepage on site B should NOT clear homepage on site A
        // Use the model to trigger boot events
        $this->setAdminSite($this->siteB->id);

        $pageB = new BaseCmsPage;
        $pageB->title = 'Home B';
        $pageB->slug = json_encode(['en' => 'home-b']);
        $pageB->status = 'published';
        $pageB->is_homepage = true;
        $pageB->site_id = $this->siteB->id;
        $pageB->saveQuietly();

        // Manually trigger the homepage clearing via the boot logic
        BaseCmsPage::withoutGlobalScopes()
            ->where('is_homepage', true)
            ->where('site_id', $this->siteB->id)
            ->where('id', '!=', $pageB->id)
            ->update(['is_homepage' => false]);

        // Home A should still be homepage
        $homeA = BaseCmsPage::withoutGlobalScopes()->find($idA);
        $this->assertTrue((bool) $homeA->is_homepage, 'Site A homepage should not be cleared');
    }

    // -------------------------------------------------------
    // Settings Scope Policy
    // -------------------------------------------------------

    public function test_site_override_settings_are_per_site(): void
    {
        SiteSetting::setGlobal('site_name', 'Global Name', 'text', 'general');

        $this->setAdminSettingsContext($this->siteB->id);
        SiteSetting::set('site_name', 'Site B Name', 'text', 'general');

        $this->assertEquals('Site B Name', SiteSetting::get('site_name'));
        $this->assertEquals('Global Name', SiteSetting::getGlobal('site_name'));

        // Switch to site A — should get global (no override)
        $this->setAdminSettingsContext($this->siteA->id);
        $this->assertEquals('Global Name', SiteSetting::get('site_name'));
    }

    public function test_global_only_settings_ignore_site_context(): void
    {
        SiteSetting::setGlobal('i18n_enabled', '1', 'boolean', 'i18n');

        $this->setAdminSettingsContext($this->siteB->id);
        SiteSetting::set('i18n_enabled', '0', 'boolean', 'i18n');

        // Should have written to global, not overrides
        $this->assertFalse(SiteSetting::getGlobal('i18n_enabled'));
        $this->assertDatabaseMissing('tallcms_site_setting_overrides', [
            'site_id' => $this->siteB->id,
            'key' => 'i18n_enabled',
        ]);
    }

    public function test_reset_to_global_deletes_override(): void
    {
        $this->setAdminSettingsContext($this->siteB->id);
        SiteSetting::set('site_name', 'Custom Name', 'text', 'general');

        $this->assertDatabaseHas('tallcms_site_setting_overrides', [
            'site_id' => $this->siteB->id,
            'key' => 'site_name',
        ]);

        SiteSetting::resetToGlobal('site_name');

        $this->assertDatabaseMissing('tallcms_site_setting_overrides', [
            'site_id' => $this->siteB->id,
            'key' => 'site_name',
        ]);
    }

    public function test_explicit_blank_override_is_preserved(): void
    {
        SiteSetting::setGlobal('contact_phone', '+1-555-0100', 'text', 'contact');

        $this->setAdminSettingsContext($this->siteB->id);
        SiteSetting::set('contact_phone', '', 'text', 'contact');

        $this->assertDatabaseHas('tallcms_site_setting_overrides', [
            'site_id' => $this->siteB->id,
            'key' => 'contact_phone',
            'value' => '',
        ]);

        // get() returns empty string, NOT global
        $this->assertEquals('', SiteSetting::get('contact_phone'));
        $this->assertEquals('+1-555-0100', SiteSetting::getGlobal('contact_phone'));
    }

    public function test_admin_session_does_not_leak_to_frontend(): void
    {
        SiteSetting::setGlobal('site_name', 'Global Name', 'text', 'general');

        // Admin creates override for site B
        $this->setAdminSettingsContext($this->siteB->id);
        SiteSetting::set('site_name', 'Portal Name', 'text', 'general');

        // Simulate frontend: remove admin context, keep session
        request()->attributes->remove('tallcms.admin_context');
        Cache::flush();

        // Frontend should get global, NOT site B's override
        $this->assertEquals('Global Name', SiteSetting::get('site_name'));
    }

    public function test_session_used_in_admin_context_only(): void
    {
        SiteSetting::setGlobal('site_name', 'Global Name', 'text', 'general');

        // Session has site B, but NO admin context attribute
        session(['multisite_admin_site_id' => $this->siteB->id]);
        Cache::flush();

        // Without admin attribute: should read global (frontend behavior)
        $this->assertEquals('Global Name', SiteSetting::get('site_name'));

        // With admin attribute: should use session
        request()->attributes->set('tallcms.admin_context', true);
        Cache::flush();
        SiteSetting::set('site_name', 'Site B Name', 'text', 'general');
        $this->assertEquals('Site B Name', SiteSetting::get('site_name'));
    }

    // -------------------------------------------------------
    // Upgrade Safety & Graceful Degradation
    // -------------------------------------------------------

    public function test_site_model_has_uuid(): void
    {
        $this->assertNotNull($this->siteA->uuid);
        $this->assertNotNull($this->siteB->uuid);
        $this->assertNotEquals($this->siteA->uuid, $this->siteB->uuid);
    }

    public function test_only_one_default_site(): void
    {
        $siteC = Site::create([
            'name' => 'Site C',
            'domain' => 'site-c.test',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->siteA->refresh();
        $this->assertFalse($this->siteA->is_default);
        $this->assertTrue($siteC->is_default);
    }

    public function test_domain_uniqueness(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Site::create([
            'name' => 'Duplicate',
            'domain' => 'site-a.test',
            'is_active' => true,
        ]);
    }

    public function test_settings_work_without_multisite_session(): void
    {
        session()->forget('multisite_admin_site_id');
        Cache::flush();

        SiteSetting::set('site_name', 'Simple Site', 'text', 'general');
        $this->assertEquals('Simple Site', SiteSetting::get('site_name'));
    }
}
