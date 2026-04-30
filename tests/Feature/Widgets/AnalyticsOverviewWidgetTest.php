<?php

declare(strict_types=1);

namespace Tests\Feature\Widgets;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tallcms\Multisite\Models\Site;
use Tests\TestCase;

class AnalyticsOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget::class)) {
            $this->markTestSkipped('Pro plugin not installed.');
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

    public function test_all_sites_renders_empty_state(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => '__all_sites__']);

        Livewire::test(\Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget::class)
            ->assertSet('isAllSites', true)
            ->assertSet('metrics', [])
            ->assertSet('topPages', [])
            ->assertSet('trafficSources', [])
            ->assertSet('visitorTrend', [])
            ->assertSee('Analytics is per-site');
    }

    public function test_specific_site_does_not_emit_empty_state(): void
    {
        $site = $this->makeSite(['name' => 'Mine', 'is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => $site->id]);

        $component = Livewire::test(\Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget::class);

        $component->assertSet('isAllSites', false);
        $component->assertSet('siteName', 'Mine');
        $component->assertDontSee('Analytics is per-site. Pick a specific site');
    }

    public function test_set_period_short_circuits_on_all_sites(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => '__all_sites__']);

        Livewire::test(\Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget::class)
            ->call('setPeriod', '30d')
            ->assertSet('period', '30d')
            ->assertSet('metrics', []);
    }

    public function test_refresh_data_short_circuits_on_all_sites(): void
    {
        $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => '__all_sites__']);

        Livewire::test(\Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget::class)
            ->call('refreshData')
            ->assertSet('metrics', []);
    }

    public function test_site_changed_event_clears_data_when_switching_to_all_sites(): void
    {
        $site = $this->makeSite(['is_default' => true]);
        $this->makeSuperAdmin();
        session(['multisite_admin_site_id' => $site->id]);

        $component = Livewire::test(\Tallcms\Pro\Filament\Widgets\AnalyticsOverviewWidget::class)
            ->set('metrics', ['some' => 'stale-data'])
            ->set('topPages', [['url' => 'old']]);

        // Simulate the picker dispatching the event
        session(['multisite_admin_site_id' => '__all_sites__']);
        $component->call('onSiteChanged');

        $component->assertSet('isAllSites', true);
        $component->assertSet('metrics', []);
        $component->assertSet('topPages', []);
    }
}
