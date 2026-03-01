<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Tests\TestCase;

/**
 * Tests for tallcms_panel_url() helper function.
 *
 * Note: tallcms_panel_route() calls Laravel's route() which requires
 * registered Filament panel routes — tested via PanelRouteHelperTest.
 */
class PanelHelperTest extends TestCase
{
    // ── tallcms_panel_url() ───────────────────────────────────

    public function test_panel_url_returns_default_admin_path(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'admin');

        $this->assertStringEndsWith('/admin', tallcms_panel_url());
    }

    public function test_panel_url_with_custom_panel_path(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'app');

        $this->assertStringEndsWith('/app', tallcms_panel_url());
    }

    public function test_panel_url_appends_subpath(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'admin');

        $this->assertStringEndsWith('/admin/cms-pages/create', tallcms_panel_url('cms-pages/create'));
    }

    public function test_panel_url_with_custom_path_and_subpath(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'dashboard');

        $this->assertStringEndsWith('/dashboard/site-settings', tallcms_panel_url('site-settings'));
    }

    public function test_panel_url_with_empty_subpath(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'admin');

        $url = tallcms_panel_url('');
        $this->assertStringEndsWith('/admin', $url);
    }

    public function test_panel_url_normalizes_slashes(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', '/admin/');

        $url = tallcms_panel_url('/cms-pages/');
        $this->assertStringEndsWith('/admin/cms-pages', $url);
        // Should not have double slashes
        $this->assertStringNotContainsString('//', parse_url($url, PHP_URL_PATH));
    }

    public function test_panel_url_with_empty_panel_path(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', '');

        // Empty panel path = root URL
        $url = tallcms_panel_url();
        $this->assertEquals(url('/'), $url);
    }

    public function test_panel_url_with_empty_panel_path_and_subpath(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', '');

        $url = tallcms_panel_url('cms-pages');
        $this->assertStringEndsWith('/cms-pages', $url);
    }
}
