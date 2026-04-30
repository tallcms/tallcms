<?php

declare(strict_types=1);

namespace Tests\Feature\Widgets;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

class LicenseStatusWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Tallcms\Pro\Filament\Widgets\LicenseStatusWidget::class)) {
            $this->markTestSkipped('Pro plugin not installed.');
        }

        // Skip when Pro is installed at a version that predates the multisite-
        // aware refactor (v1.10.0+) — older versions don't have getHeading() etc.
        if (! method_exists(\Tallcms\Pro\Filament\Widgets\LicenseStatusWidget::class, 'getHeading')) {
            $this->markTestSkipped('Pro plugin too old (need 1.10.0+ for multisite-aware widgets).');
        }

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
        $this->actingAs($user);

        return $user;
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

    public function test_all_sites_returns_sentinel_status(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => '__all_sites__']);

        $widget = new \Tallcms\Pro\Filament\Widgets\LicenseStatusWidget();
        $status = $widget->getStatus();

        $this->assertSame('all_sites', $status['scope']);
    }

    public function test_can_view_returns_true_on_all_sites_so_empty_state_renders(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => '__all_sites__']);

        $this->assertTrue(\Tallcms\Pro\Filament\Widgets\LicenseStatusWidget::canView());
    }

    public function test_all_sites_renders_empty_state_copy(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => '__all_sites__']);

        Livewire::test(\Tallcms\Pro\Filament\Widgets\LicenseStatusWidget::class)
            ->assertSee('License status is per-site');
    }

    public function test_heading_includes_site_name_when_specific_site_selected(): void
    {
        $site = $this->makeSite(['name' => 'My Brand', 'is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => $site->id]);

        $widget = new \Tallcms\Pro\Filament\Widgets\LicenseStatusWidget();

        $this->assertSame('License — My Brand', $widget->getHeading());
    }

    public function test_heading_falls_back_to_plain_label_when_site_id_does_not_resolve(): void
    {
        // Session points at a site_id that has no matching row → getMultisiteName
        // returns null → heading drops the "— Site Name" suffix.
        session(['multisite_admin_site_id' => 999999]);

        $widget = new \Tallcms\Pro\Filament\Widgets\LicenseStatusWidget();

        $this->assertSame('License', $widget->getHeading());
    }
}
