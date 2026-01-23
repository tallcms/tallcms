<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Http\Request;
use Mockery;
use TallCms\Cms\Services\LocaleRegistry;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for tallcms_current_slug() helper function.
 *
 * This helper extracts the clean content slug from the current request path,
 * stripping routes_prefix and locale prefix. Critical for language switcher
 * and hreflang URL generation.
 */
class CurrentSlugHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure i18n with test locales
        $this->app['config']->set('tallcms.i18n.enabled', true);
        $this->app['config']->set('tallcms.i18n.url_strategy', 'prefix');
        $this->app['config']->set('tallcms.i18n.locales', [
            'en' => ['label' => 'English', 'native' => 'English', 'rtl' => false],
            'zh_CN' => ['label' => 'Chinese', 'native' => '简体中文', 'rtl' => false],
        ]);
        $this->app['config']->set('tallcms.i18n.default_locale', 'en');
        $this->app['config']->set('tallcms.i18n.hide_default_locale', true);

        // Mock LocaleRegistry to avoid database dependency
        $mockRegistry = Mockery::mock(LocaleRegistry::class);
        $mockRegistry->shouldReceive('getLocaleCodes')->andReturn(['en', 'zh_CN']);
        $mockRegistry->shouldReceive('getLocales')->andReturn([
            'en' => ['label' => 'English', 'native' => 'English', 'rtl' => false],
            'zh_CN' => ['label' => 'Chinese', 'native' => '简体中文', 'rtl' => false],
        ]);
        $mockRegistry->shouldReceive('getDefaultLocale')->andReturn('en');

        $this->app->instance(LocaleRegistry::class, $mockRegistry);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to simulate a request to a given path.
     */
    protected function simulateRequest(string $path): void
    {
        $request = Request::create($path);
        $this->app->instance('request', $request);
    }

    // -------------------------------------------------------------------------
    // Basic paths (no prefixes)
    // -------------------------------------------------------------------------

    public function test_simple_slug_returned_as_is(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/about');

        $this->assertEquals('about', tallcms_current_slug());
    }

    public function test_nested_slug_returned_as_is(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/blog/my-post');

        $this->assertEquals('blog/my-post', tallcms_current_slug());
    }

    public function test_homepage_returns_empty_string(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/');

        $this->assertEquals('', tallcms_current_slug());
    }

    // -------------------------------------------------------------------------
    // Locale prefix stripping
    // -------------------------------------------------------------------------

    public function test_strips_locale_prefix_from_path(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/zh-CN/about');

        $this->assertEquals('about', tallcms_current_slug());
    }

    public function test_strips_locale_prefix_from_nested_path(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/zh-CN/blog/my-post');

        $this->assertEquals('blog/my-post', tallcms_current_slug());
    }

    public function test_locale_only_path_returns_empty_string(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/zh-CN');

        $this->assertEquals('', tallcms_current_slug());
    }

    public function test_does_not_strip_locale_when_i18n_disabled(): void
    {
        $this->app['config']->set('tallcms.i18n.enabled', false);
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/zh-CN/about');

        // When i18n is disabled, zh-CN is treated as part of the slug
        $this->assertEquals('zh-CN/about', tallcms_current_slug());
    }

    // -------------------------------------------------------------------------
    // Routes prefix stripping (plugin mode)
    // -------------------------------------------------------------------------

    public function test_strips_routes_prefix_from_path(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        $this->simulateRequest('/cms/about');

        $this->assertEquals('about', tallcms_current_slug());
    }

    public function test_strips_routes_prefix_from_nested_path(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        $this->simulateRequest('/cms/blog/my-post');

        $this->assertEquals('blog/my-post', tallcms_current_slug());
    }

    public function test_routes_prefix_only_returns_empty_string(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        $this->simulateRequest('/cms');

        $this->assertEquals('', tallcms_current_slug());
    }

    // -------------------------------------------------------------------------
    // Combined prefixes (plugin mode + i18n)
    // -------------------------------------------------------------------------

    public function test_strips_both_routes_prefix_and_locale(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        $this->simulateRequest('/cms/zh-CN/about');

        $this->assertEquals('about', tallcms_current_slug());
    }

    public function test_strips_both_prefixes_from_nested_path(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        $this->simulateRequest('/cms/zh-CN/blog/my-post');

        $this->assertEquals('blog/my-post', tallcms_current_slug());
    }

    public function test_routes_prefix_plus_locale_returns_empty(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        $this->simulateRequest('/cms/zh-CN');

        $this->assertEquals('', tallcms_current_slug());
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function test_handles_trailing_slashes(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        $this->simulateRequest('/about/');

        $this->assertEquals('about', tallcms_current_slug());
    }

    public function test_does_not_strip_partial_locale_match(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');

        // 'zh-CNN' should not be stripped as it's not an exact locale match
        $this->simulateRequest('/zh-CNN/about');

        $this->assertEquals('zh-CNN/about', tallcms_current_slug());
    }

    public function test_does_not_strip_partial_routes_prefix_match(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', 'cms');

        // 'cmsadmin' should not be stripped as it's not an exact prefix match
        $this->simulateRequest('/cmsadmin/about');

        $this->assertEquals('cmsadmin/about', tallcms_current_slug());
    }

    public function test_handles_default_locale_in_url_when_not_hidden(): void
    {
        $this->app['config']->set('tallcms.plugin_mode.routes_prefix', '');
        $this->app['config']->set('tallcms.i18n.hide_default_locale', false);

        $this->simulateRequest('/en/about');

        // Should strip 'en' locale prefix even when it's the default
        $this->assertEquals('about', tallcms_current_slug());
    }
}
