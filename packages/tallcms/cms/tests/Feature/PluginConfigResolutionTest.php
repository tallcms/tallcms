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

    /**
     * Evaluate the raw config file with current env state.
     * This bypasses Laravel's cached config and tests actual env() resolution.
     */
    protected function evaluateRawConfig(): array
    {
        return require dirname(__DIR__, 2).'/config/tallcms.php';
    }

    /**
     * Set an env var for testing and track it for cleanup.
     */
    protected function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $this->envCleanup[] = $key;
    }

    /**
     * Remove a previously set env var.
     */
    protected function clearEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key]);
    }

    protected array $envCleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->envCleanup as $key) {
            $this->clearEnv($key);
        }
        $this->envCleanup = [];

        parent::tearDown();
    }

    public function test_deprecated_plugin_env_var_is_used_as_fallback(): void
    {
        // Set only the deprecated PLUGIN_* var (no TALLCMS_ prefix)
        $this->setEnv('PLUGIN_ALLOW_UPLOADS', 'false');

        $config = $this->evaluateRawConfig();

        $this->assertFalse(
            $config['plugins']['allow_uploads'],
            'Deprecated PLUGIN_ALLOW_UPLOADS=false should resolve to false via fallback'
        );
    }

    public function test_tallcms_env_takes_precedence_over_deprecated_env(): void
    {
        // Set both: TALLCMS_ should win over deprecated PLUGIN_*
        $this->setEnv('PLUGIN_ALLOW_UPLOADS', 'false');
        $this->setEnv('TALLCMS_PLUGIN_ALLOW_UPLOADS', 'true');

        $config = $this->evaluateRawConfig();

        $this->assertTrue(
            $config['plugins']['allow_uploads'],
            'TALLCMS_PLUGIN_ALLOW_UPLOADS must take precedence over PLUGIN_ALLOW_UPLOADS'
        );
    }

    public function test_all_deprecated_env_fallbacks_resolve(): void
    {
        // Set all four deprecated env vars
        $this->setEnv('PLUGIN_ALLOW_UPLOADS', 'false');
        $this->setEnv('PLUGIN_MAX_UPLOAD_SIZE', '1048576');
        $this->setEnv('PLUGIN_CACHE_ENABLED', 'false');
        $this->setEnv('PLUGIN_AUTO_MIGRATE', 'false');

        $config = $this->evaluateRawConfig();
        $plugins = $config['plugins'];

        $this->assertFalse($plugins['allow_uploads'], 'PLUGIN_ALLOW_UPLOADS fallback');
        $this->assertSame('1048576', $plugins['max_upload_size'], 'PLUGIN_MAX_UPLOAD_SIZE fallback');
        $this->assertFalse($plugins['cache_enabled'], 'PLUGIN_CACHE_ENABLED fallback');
        $this->assertFalse($plugins['auto_migrate'], 'PLUGIN_AUTO_MIGRATE fallback');
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
