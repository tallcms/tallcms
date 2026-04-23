<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Filament\Pages\ThemeManager;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

/**
 * Regression test for ThemeManager's site-ownership fallback.
 *
 * When a site_owner opens the ThemeManager and activates a theme, the write
 * must land on THEIR site's `theme` column — not in the install-wide
 * `config/theme.php`. Pre-fix, getMultisiteContext() only looked at the
 * admin Site Switcher's session value, which a SaaS site_owner never
 * touches (they only have one site). With no session value, the context
 * came back null and the activation fell into the "write global config"
 * branch, changing the theme for every other site on the install.
 */
class ThemeManagerSiteOwnerScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'site_owner', 'guard_name' => 'web']);
    }

    public function test_site_owner_context_resolves_to_their_owned_site_without_session_switch(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('site_owner');

        $site = Site::create([
            'name' => 'Portal',
            'domain' => 'portal.test',
            'is_active' => true,
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner);

        $context = $this->callGetMultisiteContext();

        $this->assertNotNull($context, 'site_owner with an owned site must resolve to it even without Site Switcher use.');
        $this->assertSame($site->id, $context->id);
    }

    public function test_super_admin_context_returns_null_for_global_management(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Site::create([
            'name' => 'Default',
            'domain' => 'default.test',
            'is_active' => true,
            'user_id' => $admin->id,
            'is_default' => true,
        ]);

        $this->actingAs($admin);

        $context = $this->callGetMultisiteContext();

        $this->assertNull($context, 'super_admin without a session site manages installation-wide defaults; returns null.');
    }

    public function test_session_site_still_wins_over_fallback(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('site_owner');

        // Two sites: one "default" the user would resolve to via fallback,
        // another that the session is explicitly pointed at. Bypass the Site
        // model's boot-time quota check by inserting via DB directly.
        $ownedSiteId = \DB::table('tallcms_sites')->insertGetId([
            'name' => 'Owned',
            'domain' => 'owned.test',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'is_active' => true,
            'user_id' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherSiteId = \DB::table('tallcms_sites')->insertGetId([
            'name' => 'Other',
            'domain' => 'other.test',
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'is_active' => true,
            'user_id' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner);
        session(['multisite_admin_site_id' => $otherSiteId]);

        $context = $this->callGetMultisiteContext();

        $this->assertSame($otherSiteId, $context->id);
    }

    public function test_all_sites_sentinel_returns_null(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin);
        session(['multisite_admin_site_id' => '__all_sites__']);

        $this->assertNull($this->callGetMultisiteContext());
    }

    public function test_user_owning_no_sites_returns_null(): void
    {
        $orphan = User::factory()->create();
        $orphan->assignRole('site_owner');

        $this->actingAs($orphan);

        $this->assertNull($this->callGetMultisiteContext());
    }

    protected function callGetMultisiteContext()
    {
        $page = new ThemeManager;
        $method = new \ReflectionMethod($page, 'getMultisiteContext');
        $method->setAccessible(true);

        return $method->invoke($page);
    }
}
