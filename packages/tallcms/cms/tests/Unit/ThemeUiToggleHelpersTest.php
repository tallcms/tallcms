<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\Theme;
use TallCms\Cms\Tests\TestCase;

/**
 * Coverage for tallcms_show_theme_switcher / tallcms_show_search /
 * tallcms_show_language_dropdown. Each helper composes:
 *   - theme support gate (active_theme()->supports*)
 *   - relevant global config flag
 *   - per-site SiteSetting toggle (default true)
 */
class ThemeUiToggleHelpersTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['key', 'group']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tallcms.search.enabled', true);
        config()->set('tallcms.i18n.enabled', false);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Bind a stub Theme as the active theme.
     *
     * Note: supportsThemeController() is preset-count derived (not the supports
     * flag), so callers controlling that gate must vary $presetsAll, not the
     * supports['theme_controller'] key.
     */
    protected function bindActiveTheme(array $supports, bool $presetsAll = true): void
    {
        $themeData = [
            'name' => 'Test',
            'slug' => 'test-theme',
            'daisyui' => array_filter([
                'preset' => 'light',
                'presets' => $presetsAll ? 'all' : null,
            ]),
            'supports' => $supports,
        ];

        $theme = new Theme($themeData, '/tmp/test-theme');

        $manager = Mockery::mock(\TallCms\Cms\Services\ThemeManager::class);
        $manager->shouldReceive('getActiveTheme')->andReturn($theme);
        $manager->shouldReceive('themeAsset')->andReturn('');
        $this->app->instance(\TallCms\Cms\Services\ThemeManager::class, $manager);
    }

    public function test_theme_switcher_helper_requires_theme_support(): void
    {
        // Single preset → supportsThemeController() returns false
        $this->bindActiveTheme(supports: [], presetsAll: false);
        $this->assertFalse(tallcms_show_theme_switcher());
    }

    public function test_theme_switcher_helper_respects_site_setting(): void
    {
        $this->bindActiveTheme(supports: [], presetsAll: true);

        SiteSetting::set('show_theme_switcher', false, 'boolean', 'branding');
        $this->assertFalse(tallcms_show_theme_switcher());

        SiteSetting::set('show_theme_switcher', true, 'boolean', 'branding');
        $this->assertTrue(tallcms_show_theme_switcher());
    }

    public function test_search_helper_requires_theme_support(): void
    {
        $this->bindActiveTheme(['search' => false]);
        $this->assertFalse(tallcms_show_search());
    }

    public function test_search_helper_respects_global_config_flag(): void
    {
        $this->bindActiveTheme(['search' => true]);
        config()->set('tallcms.search.enabled', false);
        SiteSetting::set('show_search', true, 'boolean', 'branding');
        $this->assertFalse(tallcms_show_search());
    }

    public function test_search_helper_respects_site_setting(): void
    {
        $this->bindActiveTheme(['search' => true]);
        config()->set('tallcms.search.enabled', true);

        SiteSetting::set('show_search', false, 'boolean', 'branding');
        $this->assertFalse(tallcms_show_search());

        SiteSetting::set('show_search', true, 'boolean', 'branding');
        $this->assertTrue(tallcms_show_search());
    }

    public function test_language_dropdown_helper_requires_i18n_enabled(): void
    {
        $this->bindActiveTheme(['language_switcher' => true]);
        config()->set('tallcms.i18n.enabled', false);
        SiteSetting::set('show_language_dropdown', true, 'boolean', 'branding');
        $this->assertFalse(tallcms_show_language_dropdown());
    }

    public function test_language_dropdown_helper_returns_true_when_all_conditions_met(): void
    {
        $this->bindActiveTheme(['language_switcher' => true]);
        config()->set('tallcms.i18n.enabled', true);
        SiteSetting::set('show_language_dropdown', true, 'boolean', 'branding');

        $this->assertTrue(tallcms_show_language_dropdown());
    }

    public function test_helpers_default_to_true_when_setting_unset_but_theme_supports(): void
    {
        $this->bindActiveTheme(supports: ['search' => true], presetsAll: true);

        // No SiteSetting written — defaults must keep controls visible.
        $this->assertTrue(tallcms_show_theme_switcher());
        $this->assertTrue(tallcms_show_search());
    }
}
