<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SiteSettingsService;
use Tests\TestCase;

/**
 * Regression tests for the single-site settings-read path.
 *
 * The Site edit page writes site-scoped settings (logo, favicon, contact info,
 * etc.) as rows in tallcms_site_setting_overrides keyed on the default site's
 * id. On a single-site install — no multisite plugin, so no domain resolver —
 * SiteSetting::get() must still be able to find those overrides when rendering
 * the frontend. Without the default-site fallback in resolveCurrentSiteId(),
 * every override is invisible to frontend views and the setting silently
 * "disappears" from the user's perspective.
 */
class SiteSettingDefaultSiteFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected int $defaultSiteId;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        SiteSetting::forgetMemoizedDefaultSiteId();

        // The core create_tallcms_sites_core_table migration ensures a default
        // site exists. Use it directly rather than creating another one.
        $existing = DB::table('tallcms_sites')->where('is_default', true)->value('id');

        $this->defaultSiteId = $existing
            ? (int) $existing
            : (int) DB::table('tallcms_sites')->insertGetId([
                'name' => 'Default',
                'domain' => 'example.test',
                'uuid' => (string) Str::uuid(),
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // Simulate single-site install: the multisite plugin is not registered,
        // so SiteSetting::resolveCurrentSiteId() cannot consult a resolver and
        // must fall through to the default-site branch. The plugin registers
        // via an alias, so unset() is not enough — we strip the alias too.
        $this->unbindMultisiteResolver();
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

        $this->assertFalse(
            $this->app->bound('tallcms.multisite.resolver'),
            'Failed to simulate single-site install: resolver is still bound.',
        );
    }

    public function test_single_site_frontend_reads_override_written_for_default_site(): void
    {
        app(SiteSettingsService::class)->setForSite(
            $this->defaultSiteId,
            'logo',
            'site-assets/logo.png',
            'file',
        );

        SiteSetting::clearCache();

        // Sanity-check the test setup
        $this->assertFalse($this->app->bound('tallcms.multisite.resolver'));
        $this->assertSame($this->defaultSiteId, (int) DB::table('tallcms_sites')->where('is_default', true)->value('id'));
        $this->assertSame('site-assets/logo.png', DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $this->defaultSiteId)->where('key', 'logo')->value('value'));

        $this->assertSame('site-assets/logo.png', SiteSetting::get('logo'));
    }

    public function test_single_site_read_falls_back_to_global_when_no_override(): void
    {
        SiteSetting::setGlobal('site_description', 'Global description', 'text', 'general');

        SiteSetting::clearCache();

        $this->assertSame('Global description', SiteSetting::get('site_description'));
    }

    public function test_single_site_override_takes_precedence_over_global(): void
    {
        SiteSetting::setGlobal('site_description', 'Global description', 'text', 'general');
        app(SiteSettingsService::class)->setForSite(
            $this->defaultSiteId,
            'site_description',
            'Per-site description',
            'text',
        );

        SiteSetting::clearCache();

        $this->assertSame('Per-site description', SiteSetting::get('site_description'));
    }

    public function test_global_only_keys_ignore_the_default_site_fallback(): void
    {
        SiteSetting::setGlobal('default_locale', 'fr', 'text', 'i18n');

        // Even if someone wrote an override for a global-only key, it must be ignored.
        app(SiteSettingsService::class)->setForSite(
            $this->defaultSiteId,
            'default_locale',
            'de',
            'text',
        );

        SiteSetting::clearCache();

        $this->assertSame('fr', SiteSetting::get('default_locale'));
    }

    public function test_clear_cache_invalidates_default_site_cached_read(): void
    {
        app(SiteSettingsService::class)->setForSite(
            $this->defaultSiteId,
            'logo',
            'site-assets/old.png',
            'file',
        );
        SiteSetting::clearCache();

        // Prime the cache
        $this->assertSame('site-assets/old.png', SiteSetting::get('logo'));

        // Overwrite the override and clear the cache as EditSite::save() does
        app(SiteSettingsService::class)->setForSite(
            $this->defaultSiteId,
            'logo',
            'site-assets/new.png',
            'file',
        );
        SiteSetting::clearCache();

        $this->assertSame('site-assets/new.png', SiteSetting::get('logo'));
    }
}
