<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Models\SiteSetting;
use Tests\TestCase;

/**
 * Regression test for the theme-preset-doesn't-persist bug.
 *
 * ThemeManager::changeDefaultPreset() writes via SiteSetting::set(), which
 * (post-4.0.8) stores the value as an override against the default site.
 * But ThemeManager::activeTheme() previously read via SiteSetting::getGlobal()
 * when no multisite session context existed — missing the override entirely.
 * Result on standalone installs: pick a preset, save, reload the page, and the
 * page reverted to the theme's default. Same on the frontend via
 * daisyui_default_preset().
 *
 * The read path now uses SiteSetting::get() unconditionally so the default-site
 * resolver fallback handles the standalone case.
 */
class ThemeManagerPresetPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        SiteSetting::forgetMemoizedDefaultSiteId();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    public function test_preset_written_via_set_is_read_back_via_get_on_standalone(): void
    {
        $this->unbindMultisiteResolver();

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        // Simulate ThemeManager::changeDefaultPreset() saving a preset.
        SiteSetting::set('theme_default_preset', 'dark', 'text', 'theme');

        $this->assertSame(
            'dark',
            SiteSetting::get('theme_default_preset'),
            'Preset saved via set() on standalone must be read back by get() — '.
            'otherwise the admin page and frontend helper both silently revert to the default.',
        );
    }

    public function test_preset_override_row_lands_on_default_site(): void
    {
        $this->unbindMultisiteResolver();

        SiteSetting::set('theme_default_preset', 'forest', 'text', 'theme');

        $defaultSiteId = DB::table('tallcms_sites')->where('is_default', true)->value('id');
        $override = DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $defaultSiteId)
            ->where('key', 'theme_default_preset')
            ->value('value');

        $this->assertSame('forest', $override);
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
