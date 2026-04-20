<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Filament\Pages\GlobalDefaults;
use TallCms\Cms\Filament\Resources\SiteResource\Pages\EditSite;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SiteSettingsService;
use Tests\TestCase;

class SiteResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->site = Site::firstOrCreate(
            ['is_default' => true],
            [
                'name' => 'Test Site',
                'domain' => 'test.localhost',
                'is_active' => true,
            ]
        );
    }

    // ─── Admin Page Tests ───────────────────────────────────────────────

    public function test_site_edit_page_renders(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/site-resource/sites')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_site_edit(): void
    {
        $this->get('/admin/site-resource/sites')
            ->assertRedirect();
    }

    public function test_site_edit_page_loads_site_data(): void
    {
        $service = app(SiteSettingsService::class);
        $service->setForSite($this->site->id, 'site_tagline', 'My Tagline');

        $this->actingAs($this->superAdmin);

        Livewire::test(EditSite::class)
            ->assertFormSet([
                'name' => $this->site->name,
                'domain' => $this->site->domain,
                'site_tagline' => 'My Tagline',
            ]);
    }

    public function test_site_edit_saves_model_and_settings(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(EditSite::class)
            ->fillForm([
                'name' => 'Updated Name',
                'site_tagline' => 'New tagline',
                'site_type' => 'multi-page',
                'contact_email' => 'test@example.com',
            ])
            ->call('save');

        $this->site->refresh();
        $this->assertEquals('Updated Name', $this->site->name);

        $service = app(SiteSettingsService::class);
        $this->assertEquals('New tagline', $service->getForSite($this->site->id, 'site_tagline'));
        $this->assertEquals('test@example.com', $service->getForSite($this->site->id, 'contact_email'));
    }

    public function test_site_edit_save_preserves_global_inheritance(): void
    {
        $this->actingAs($this->superAdmin);
        $service = app(SiteSettingsService::class);

        // Set a global value
        SiteSetting::setGlobal('site_tagline', 'Global Tagline');

        // Save the form without changing site_tagline (it still shows the global value)
        Livewire::test(EditSite::class)
            ->fillForm([
                'name' => $this->site->name,
                'site_tagline' => 'Global Tagline', // matches global
                'site_type' => 'multi-page',
            ])
            ->call('save');

        // No override should have been created — value matches global
        $this->assertFalse(
            $service->hasOverride($this->site->id, 'site_tagline'),
            'Override should not be created when value matches global'
        );

        // Change the value — now an override should be created
        Livewire::test(EditSite::class)
            ->fillForm([
                'name' => $this->site->name,
                'site_tagline' => 'Custom Tagline',
                'site_type' => 'multi-page',
            ])
            ->call('save');

        $this->assertTrue(
            $service->hasOverride($this->site->id, 'site_tagline'),
            'Override should be created when value differs from global'
        );
        $this->assertEquals('Custom Tagline', $service->getForSite($this->site->id, 'site_tagline'));

        // Change back to match global — override should be removed (restore inheritance)
        Livewire::test(EditSite::class)
            ->fillForm([
                'name' => $this->site->name,
                'site_tagline' => 'Global Tagline', // matches global again
                'site_type' => 'multi-page',
            ])
            ->call('save');

        $this->assertFalse(
            $service->hasOverride($this->site->id, 'site_tagline'),
            'Override should be removed when value reverts to match global'
        );
        $this->assertEquals('Global Tagline', $service->getForSite($this->site->id, 'site_tagline'));
    }

    public function test_site_edit_creates_default_site_if_none_exists(): void
    {
        Site::query()->delete();

        $this->actingAs($this->superAdmin);

        Livewire::test(EditSite::class)
            ->assertSuccessful();

        $this->assertDatabaseHas('tallcms_sites', [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    // ─── SiteSettingsService Tests ──────────────────────────────────────

    public function test_service_persists_and_reads_settings(): void
    {
        $service = app(SiteSettingsService::class);

        $service->setForSite($this->site->id, 'site_tagline', 'My tagline');
        $service->setForSite($this->site->id, 'contact_email', 'hello@example.com');
        $service->setForSite($this->site->id, 'show_powered_by', true, 'boolean');

        $this->assertEquals('My tagline', $service->getForSite($this->site->id, 'site_tagline'));
        $this->assertEquals('hello@example.com', $service->getForSite($this->site->id, 'contact_email'));
        $this->assertTrue($service->getForSite($this->site->id, 'show_powered_by'));
    }

    public function test_service_falls_back_to_global(): void
    {
        $service = app(SiteSettingsService::class);

        // No override — should return default
        $this->assertEquals('fallback', $service->getForSite($this->site->id, 'site_tagline', 'fallback'));

        // Set global
        SiteSetting::setGlobal('site_tagline', 'Global tagline');
        $this->assertEquals('Global tagline', $service->getForSite($this->site->id, 'site_tagline', 'fallback'));

        // Override takes precedence
        $service->setForSite($this->site->id, 'site_tagline', 'Site tagline');
        $this->assertEquals('Site tagline', $service->getForSite($this->site->id, 'site_tagline', 'fallback'));
    }

    public function test_service_reset_removes_override(): void
    {
        $service = app(SiteSettingsService::class);

        SiteSetting::setGlobal('site_tagline', 'Global tagline');
        $service->setForSite($this->site->id, 'site_tagline', 'Override tagline');
        $this->assertTrue($service->hasOverride($this->site->id, 'site_tagline'));

        $service->resetForSite($this->site->id, 'site_tagline');

        $this->assertFalse($service->hasOverride($this->site->id, 'site_tagline'));
        $this->assertEquals('Global tagline', $service->getForSite($this->site->id, 'site_tagline'));
    }

    public function test_boolean_settings_are_cast_correctly(): void
    {
        $service = app(SiteSettingsService::class);

        $service->setForSite($this->site->id, 'maintenance_mode', true, 'boolean');
        $this->assertTrue($service->getForSite($this->site->id, 'maintenance_mode'));

        $service->setForSite($this->site->id, 'maintenance_mode', false, 'boolean');
        $this->assertFalse($service->getForSite($this->site->id, 'maintenance_mode'));
    }

    public function test_overridden_keys_returns_correct_list(): void
    {
        $service = app(SiteSettingsService::class);

        $service->setForSite($this->site->id, 'site_tagline', 'My tagline');
        $service->setForSite($this->site->id, 'contact_email', 'test@test.com');

        $keys = $service->getOverriddenKeys($this->site->id);

        $this->assertContains('site_tagline', $keys);
        $this->assertContains('contact_email', $keys);
        $this->assertNotContains('company_name', $keys);
    }

    // ─── SiteSetting Alias Tests ────────────────────────────────────────

    public function test_site_name_alias_resolves_from_site_model(): void
    {
        $this->site->update(['name' => 'Brand Name']);

        $result = SiteSetting::get('site_name', 'default');
        $this->assertEquals('Brand Name', $result);
    }

    // ─── Global Defaults Page Tests ─────────────────────────────────────

    public function test_global_defaults_page_renders(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/global-defaults')
            ->assertSuccessful();
    }

    public function test_global_defaults_page_loads_existing_values(): void
    {
        SiteSetting::setGlobal('site_tagline', 'Global Tagline', 'text', 'general');
        SiteSetting::setGlobal('contact_email', 'global@example.com', 'text', 'contact');

        $this->actingAs($this->superAdmin);

        Livewire::test(GlobalDefaults::class)
            ->assertFormSet([
                'site_tagline' => 'Global Tagline',
                'contact_email' => 'global@example.com',
            ]);
    }

    public function test_global_defaults_page_saves_values(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(GlobalDefaults::class)
            ->fillForm([
                'site_tagline' => 'New Global Tagline',
                'contact_email' => 'new@example.com',
                'site_type' => 'multi-page',
            ])
            ->call('save');

        $this->assertEquals('New Global Tagline', SiteSetting::getGlobal('site_tagline'));
        $this->assertEquals('new@example.com', SiteSetting::getGlobal('contact_email'));
    }

    public function test_global_defaults_inherited_by_site(): void
    {
        $this->actingAs($this->superAdmin);

        // Set a global default
        Livewire::test(GlobalDefaults::class)
            ->fillForm([
                'site_tagline' => 'Inherited Tagline',
                'site_type' => 'multi-page',
            ])
            ->call('save');

        // Site should inherit via SiteSettingsService (no override)
        $service = app(SiteSettingsService::class);
        $this->assertEquals('Inherited Tagline', $service->getForSite($this->site->id, 'site_tagline'));

        // Override on site takes precedence
        $service->setForSite($this->site->id, 'site_tagline', 'Site Override');
        $this->assertEquals('Site Override', $service->getForSite($this->site->id, 'site_tagline'));
    }

    public function test_global_defaults_not_accessible_without_super_admin(): void
    {
        $editor = User::factory()->create(['is_active' => true]);
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->assignRole('editor');

        $this->actingAs($editor)
            ->get('/admin/global-defaults')
            ->assertForbidden();
    }
}
