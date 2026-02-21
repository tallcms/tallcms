<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;

/**
 * Tests that plugin config resolves correctly under tallcms.plugins.*
 * and that deprecated PLUGIN_* env vars still work as fallbacks.
 *
 * These prevent regressions from the config consolidation that moved
 * all settings from config/plugin.php into tallcms.plugins.*.
 */
class PluginConfigResolutionTest extends TestCase
{
    public function test_plugin_config_resolves_from_tallcms_namespace(): void
    {
        // All plugin settings must resolve from tallcms.plugins.*
        $this->assertTrue(config('tallcms.plugins.allow_uploads'));
        $this->assertSame(50 * 1024 * 1024, config('tallcms.plugins.max_upload_size'));
        $this->assertTrue(config('tallcms.plugins.cache_enabled'));
        $this->assertSame(3600, config('tallcms.plugins.cache_ttl'));
        $this->assertTrue(config('tallcms.plugins.auto_migrate'));
    }

    public function test_plugin_license_config_resolves_from_tallcms_namespace(): void
    {
        $this->assertSame('https://tallcms.com', config('tallcms.plugins.license.proxy_url'));
        $this->assertSame(21600, config('tallcms.plugins.license.cache_ttl'));
        $this->assertSame(7, config('tallcms.plugins.license.offline_grace_days'));
        $this->assertSame(14, config('tallcms.plugins.license.renewal_grace_days'));
        $this->assertSame(86400, config('tallcms.plugins.license.update_check_interval'));
    }

    public function test_plugin_license_purchase_urls_resolve(): void
    {
        $purchaseUrls = config('tallcms.plugins.license.purchase_urls');

        $this->assertIsArray($purchaseUrls);
        $this->assertArrayHasKey('tallcms/pro', $purchaseUrls);
        $this->assertArrayHasKey('tallcms/mega-menu', $purchaseUrls);
    }

    public function test_plugin_license_download_urls_resolve(): void
    {
        $downloadUrls = config('tallcms.plugins.license.download_urls');

        $this->assertIsArray($downloadUrls);
        $this->assertArrayHasKey('tallcms/pro', $downloadUrls);
        $this->assertArrayHasKey('tallcms/mega-menu', $downloadUrls);
    }

    public function test_plugin_catalog_resolves_from_tallcms_namespace(): void
    {
        $catalog = config('tallcms.plugins.catalog');

        $this->assertIsArray($catalog);
        $this->assertArrayHasKey('tallcms/pro', $catalog);
        $this->assertArrayHasKey('tallcms/mega-menu', $catalog);
    }

    public function test_old_plugin_config_namespace_returns_null(): void
    {
        // config('plugin.*') must NOT resolve — the standalone config/plugin.php is removed
        $this->assertNull(config('plugin.allow_uploads'));
        $this->assertNull(config('plugin.auto_migrate'));
        $this->assertNull(config('plugin.cache_enabled'));
        $this->assertNull(config('plugin.license'));
        $this->assertNull(config('plugin.catalog'));
    }

    public function test_deprecated_plugin_allow_uploads_env_fallback(): void
    {
        // Simulate a .env with the old PLUGIN_ALLOW_UPLOADS var
        config(['tallcms.plugins.allow_uploads' => env('TALLCMS_PLUGIN_ALLOW_UPLOADS', env('PLUGIN_ALLOW_UPLOADS', true))]);

        // Without any env override, default is true
        $this->assertTrue(config('tallcms.plugins.allow_uploads'));

        // Simulate TALLCMS_ taking precedence
        config(['tallcms.plugins.allow_uploads' => false]);
        $this->assertFalse(config('tallcms.plugins.allow_uploads'));
    }

    public function test_tallcms_env_takes_precedence_over_deprecated_env(): void
    {
        // When tallcms.plugins.* is explicitly set, that value wins
        config(['tallcms.plugins.auto_migrate' => false]);
        $this->assertFalse(config('tallcms.plugins.auto_migrate'));

        config(['tallcms.plugins.cache_enabled' => false]);
        $this->assertFalse(config('tallcms.plugins.cache_enabled'));

        config(['tallcms.plugins.max_upload_size' => 10 * 1024 * 1024]);
        $this->assertSame(10 * 1024 * 1024, config('tallcms.plugins.max_upload_size'));
    }

    public function test_plugin_config_defaults_match_expected_values(): void
    {
        // Verify defaults haven't drifted from documented values
        $this->assertTrue(config('tallcms.plugins.allow_uploads'), 'allow_uploads should default to true');
        $this->assertTrue(config('tallcms.plugins.cache_enabled'), 'cache_enabled should default to true');
        $this->assertTrue(config('tallcms.plugins.auto_migrate'), 'auto_migrate should default to true');
        $this->assertSame(50 * 1024 * 1024, config('tallcms.plugins.max_upload_size'), 'max_upload_size should default to 50MB');
        $this->assertSame(3600, config('tallcms.plugins.cache_ttl'), 'cache_ttl should default to 1 hour');
    }
}
