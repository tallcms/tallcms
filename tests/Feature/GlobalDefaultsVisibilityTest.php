<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Filament\Pages\GlobalDefaults;
use Tests\TestCase;

/**
 * Regression test for the standalone-vs-multisite GlobalDefaults UX gate.
 *
 * Showing both "Site Settings" and "Global Defaults" on a single-site install
 * is confusing — they edit the same surface. GlobalDefaults must hide itself
 * (canAccess + shouldRegisterNavigation) when no multisite resolver is bound,
 * and re-appear only once the multisite plugin activates inheritance across
 * multiple sites.
 */
class GlobalDefaultsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);
    }

    public function test_globaldefaults_hidden_in_standalone_mode(): void
    {
        $this->unbindMultisiteResolver();

        $this->assertFalse(GlobalDefaults::canAccess());
        $this->assertFalse(GlobalDefaults::shouldRegisterNavigation());
    }

    public function test_globaldefaults_visible_when_multisite_resolver_bound(): void
    {
        // Test suite has the multisite plugin installed, so the resolver is
        // bound by default — no setup needed.
        $this->assertTrue($this->app->bound('tallcms.multisite.resolver'));

        $this->assertTrue(GlobalDefaults::canAccess());
        $this->assertTrue(GlobalDefaults::shouldRegisterNavigation());
    }

    protected function unbindMultisiteResolver(): void
    {
        unset($this->app['tallcms.multisite.resolver']);

        $refl = new \ReflectionClass($this->app);

        $aliasesProp = $refl->getProperty('aliases');
        $aliasesProp->setAccessible(true);
        $aliases = $aliasesProp->getValue($this->app);
        unset($aliases['tallcms.multisite.resolver']);
        $aliasesProp->setValue($this->app, $aliases);

        $abstractAliasesProp = $refl->getProperty('abstractAliases');
        $abstractAliasesProp->setAccessible(true);
        $abstractAliases = $abstractAliasesProp->getValue($this->app);
        foreach ($abstractAliases as $abstract => $list) {
            $abstractAliases[$abstract] = array_values(array_diff($list, ['tallcms.multisite.resolver']));
        }
        $abstractAliasesProp->setValue($this->app, $abstractAliases);
    }
}
