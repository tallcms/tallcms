<?php

namespace TallCms\Cms\Tests\Unit;

use ReflectionMethod;
use TallCms\Cms\Providers\PluginServiceProvider;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for PluginServiceProvider::isRouteSafe() method.
 *
 * Ensures segment-aware panel path matching:
 * - /app is blocked when panel path is "app"
 * - /app/foo is blocked
 * - /app2 is NOT blocked (different segment)
 */
class IsRouteSafeTest extends TestCase
{
    protected PluginServiceProvider $provider;

    protected ReflectionMethod $method;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new PluginServiceProvider($this->app);
        $this->method = new ReflectionMethod($this->provider, 'isRouteSafe');
        $this->method->setAccessible(true);
    }

    protected function callIsRouteSafe(string $path): bool
    {
        return $this->method->invoke($this->provider, $path);
    }

    // ── Default panel path (admin) ────────────────────────────

    public function test_blocks_admin_path(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'admin');

        $this->assertFalse($this->callIsRouteSafe('/admin'));
    }

    public function test_blocks_admin_subpath(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'admin');

        $this->assertFalse($this->callIsRouteSafe('/admin/cms-pages'));
    }

    public function test_allows_admin_sibling(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'admin');

        // /admin2 should NOT be blocked (different segment)
        $this->assertTrue($this->callIsRouteSafe('/admin2'));
    }

    // ── Custom panel path (app) ───────────────────────────────

    public function test_blocks_custom_panel_path(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'app');

        $this->assertFalse($this->callIsRouteSafe('/app'));
    }

    public function test_blocks_custom_panel_subpath(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'app');

        $this->assertFalse($this->callIsRouteSafe('/app/foo'));
    }

    public function test_allows_custom_panel_sibling(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', 'app');

        // /app2 should NOT be blocked
        $this->assertTrue($this->callIsRouteSafe('/app2'));
    }

    // ── Empty panel path (root) ───────────────────────────────

    public function test_empty_panel_path_does_not_block_everything(): void
    {
        $this->app['config']->set('tallcms.filament.panel_path', '');

        // When panel is at root, the panel check should be skipped
        // (routes are still blocked by root check separately)
        $this->assertTrue($this->callIsRouteSafe('/about'));
    }

    // ── API and install routes always blocked ─────────────────

    public function test_blocks_api_routes(): void
    {
        $this->assertFalse($this->callIsRouteSafe('/api'));
        $this->assertFalse($this->callIsRouteSafe('/api/v1/data'));
    }

    public function test_blocks_install_routes(): void
    {
        $this->assertFalse($this->callIsRouteSafe('/install'));
        $this->assertFalse($this->callIsRouteSafe('/install/step-1'));
    }

    public function test_blocks_root(): void
    {
        $this->assertFalse($this->callIsRouteSafe('/'));
    }

    public function test_requires_leading_slash(): void
    {
        $this->assertFalse($this->callIsRouteSafe('about'));
    }

    public function test_allows_normal_paths(): void
    {
        $this->assertTrue($this->callIsRouteSafe('/about'));
        $this->assertTrue($this->callIsRouteSafe('/blog/my-post'));
    }
}
