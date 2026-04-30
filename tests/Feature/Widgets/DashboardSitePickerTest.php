<?php

declare(strict_types=1);

namespace Tests\Feature\Widgets;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tallcms\Multisite\Models\Site;
use TallCms\Cms\Filament\Widgets\DashboardSitePicker;
use Tests\TestCase;

/**
 * Lives in the standalone test suite (not the cms package) because the
 * picker view uses Filament's <x-filament-widgets::widget> + <x-filament::section>
 * components that only resolve when the full panel boots. Same reason the
 * FeaturesBlock render tests landed here in PR #72.
 */
class DashboardSitePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    private function makeSuperAdmin(): User
    {
        $user = User::create([
            'name' => 'Boss',
            'email' => 'boss-'.uniqid().'@example.com',
            'password' => bcrypt('x'),
        ]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function makeRegularUser(): User
    {
        return User::create([
            'name' => 'User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => bcrypt('x'),
        ]);
    }

    private function makeSite(array $attrs = []): Site
    {
        return Site::create(array_merge([
            'name' => 'Test Site',
            'domain' => uniqid('test-').'.example.com',
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'is_default' => false,
            'is_active' => true,
            'domain_verified' => true,
            'domain_status' => 'verified',
        ], $attrs));
    }

    public function test_super_admin_with_no_session_defaults_to_default_site(): void
    {
        $defaultSite = $this->makeSite(['is_default' => true, 'name' => 'Main']);
        $this->makeSite(['name' => 'Other']);
        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin);
        session()->forget('multisite_admin_site_id');

        Livewire::test(DashboardSitePicker::class)
            ->assertSet('selected', (string) $defaultSite->id);

        $this->assertSame($defaultSite->id, session('multisite_admin_site_id'));
    }

    public function test_non_super_admin_with_one_site_defaults_to_that_site(): void
    {
        $user = $this->makeRegularUser();
        $site = $this->makeSite(['user_id' => $user->id, 'name' => 'Mine']);
        $this->actingAs($user);
        session()->forget('multisite_admin_site_id');

        Livewire::test(DashboardSitePicker::class)
            ->assertSet('selected', (string) $site->id);

        $this->assertSame($site->id, session('multisite_admin_site_id'));
    }

    public function test_explicit_specific_site_selection_writes_session_and_dispatches_event(): void
    {
        $a = $this->makeSite(['is_default' => true, 'name' => 'A']);
        $b = $this->makeSite(['name' => 'B']);
        $this->actingAs($this->makeSuperAdmin());

        Livewire::test(DashboardSitePicker::class)
            ->set('selected', (string) $b->id)
            ->assertDispatched('dashboard.site-changed', siteId: $b->id);

        $this->assertSame($b->id, session('multisite_admin_site_id'));
    }

    public function test_explicit_all_sites_selection_writes_sentinel_and_dispatches_event(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->actingAs($this->makeSuperAdmin());

        Livewire::test(DashboardSitePicker::class)
            ->set('selected', '__all_sites__')
            ->assertDispatched('dashboard.site-changed', siteId: '__all_sites__');

        $this->assertSame('__all_sites__', session('multisite_admin_site_id'));
    }

    public function test_can_view_returns_false_when_user_owns_no_sites(): void
    {
        $this->actingAs($this->makeRegularUser());

        $this->assertFalse(DashboardSitePicker::canView());
    }

    public function test_can_view_returns_true_for_super_admin_even_with_no_owned_sites(): void
    {
        $this->actingAs($this->makeSuperAdmin());

        $this->assertTrue(DashboardSitePicker::canView());
    }

    // -------------------------------------------------------------------------
    // Authorization — server-side validation of Livewire-supplied selection
    // -------------------------------------------------------------------------

    public function test_regular_user_cannot_select_another_users_site(): void
    {
        // Insert a foreign-owned site directly via DB (bypassing the model's
        // quota guard, which isn't what's under test here).
        $owner = $this->makeRegularUser();
        $foreignId = DB::table('tallcms_sites')->insertGetId([
            'name' => 'Owner',
            'domain' => uniqid('owner-').'.example.com',
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $owner->id,
            'is_default' => false,
            'is_active' => true,
            'domain_verified' => true,
            'domain_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tamperer = $this->makeRegularUser();
        $ownSite = $this->makeSite(['name' => 'Mine', 'user_id' => $tamperer->id]);
        $this->actingAs($tamperer);

        Livewire::test(DashboardSitePicker::class)
            ->set('selected', (string) $foreignId)
            ->assertNotDispatched('dashboard.site-changed');

        $this->assertNotSame($foreignId, session('multisite_admin_site_id'));
    }

    public function test_regular_user_cannot_select_all_sites(): void
    {
        $user = $this->makeRegularUser();
        $site = $this->makeSite(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(DashboardSitePicker::class)
            ->set('selected', '__all_sites__')
            ->assertNotDispatched('dashboard.site-changed');

        $this->assertNotSame('__all_sites__', session('multisite_admin_site_id'));
    }

    public function test_regular_user_cannot_select_inactive_site(): void
    {
        $user = $this->makeRegularUser();
        $active = $this->makeSite(['name' => 'Active', 'user_id' => $user->id, 'is_active' => true]);
        // Second site for same user — bypass quota guard via raw insert.
        $inactiveId = DB::table('tallcms_sites')->insertGetId([
            'name' => 'Inactive',
            'domain' => uniqid('inactive-').'.example.com',
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $user->id,
            'is_default' => false,
            'is_active' => false,
            'domain_verified' => true,
            'domain_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($user);

        Livewire::test(DashboardSitePicker::class)
            ->set('selected', (string) $inactiveId)
            ->assertNotDispatched('dashboard.site-changed');

        $this->assertNotSame($inactiveId, session('multisite_admin_site_id'));
    }

    public function test_mount_normalizes_stale_unauthorized_session_value(): void
    {
        $owner = $this->makeRegularUser();
        $foreignId = DB::table('tallcms_sites')->insertGetId([
            'name' => 'Owner',
            'domain' => uniqid('owner-').'.example.com',
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $owner->id,
            'is_default' => false,
            'is_active' => true,
            'domain_verified' => true,
            'domain_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->makeRegularUser();
        $ownSite = $this->makeSite(['name' => 'Mine', 'user_id' => $user->id]);
        $this->actingAs($user);

        // Stale session (perhaps left over from a previous login) points at
        // a site this user can't access. Picker mount should ignore it and
        // fall back to the user's own site.
        session(['multisite_admin_site_id' => $foreignId]);

        Livewire::test(DashboardSitePicker::class)
            ->assertSet('selected', (string) $ownSite->id);

        $this->assertSame($ownSite->id, session('multisite_admin_site_id'));
    }

    public function test_mount_rejects_stale_all_sites_for_non_super_admin(): void
    {
        $user = $this->makeRegularUser();
        $site = $this->makeSite(['name' => 'Mine', 'user_id' => $user->id]);
        $this->actingAs($user);
        session(['multisite_admin_site_id' => '__all_sites__']);

        Livewire::test(DashboardSitePicker::class)
            ->assertSet('selected', (string) $site->id);

        $this->assertSame($site->id, session('multisite_admin_site_id'));
    }
}
