<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Support\Facades\View;
use TallCms\Cms\Tests\TestCase;

class PluginModeIntegrationTest extends TestCase
{
    public function test_package_views_are_registered(): void
    {
        $this->assertTrue(
            View::exists('tallcms::layouts.app'),
            'tallcms::layouts.app view should be registered'
        );
    }

    public function test_block_views_are_accessible(): void
    {
        $blockViews = [
            'tallcms::cms.blocks.hero',
            'tallcms::cms.blocks.content-block',
            'tallcms::cms.blocks.call-to-action',
            'tallcms::cms.blocks.contact-form',
            'tallcms::cms.blocks.contact-form-preview',
            'tallcms::cms.blocks.faq',
            'tallcms::cms.blocks.features',
            'tallcms::cms.blocks.pricing',
            'tallcms::cms.blocks.testimonials',
        ];

        foreach ($blockViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Block view {$view} should be accessible"
            );
        }
    }

    public function test_component_views_are_accessible(): void
    {
        $componentViews = [
            'tallcms::components.menu',
            'tallcms::components.menu-item',
            'tallcms::components.form.dynamic-field',
        ];

        foreach ($componentViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Component view {$view} should be accessible"
            );
        }
    }

    public function test_livewire_views_are_accessible(): void
    {
        $livewireViews = [
            'tallcms::livewire.page',
            'tallcms::livewire.revision-history',
        ];

        foreach ($livewireViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Livewire view {$view} should be accessible"
            );
        }
    }

    public function test_preview_routes_are_registered(): void
    {
        $this->assertTrue(
            $this->app['router']->has('tallcms.preview.page'),
            'Preview page route should exist'
        );

        $this->assertTrue(
            $this->app['router']->has('tallcms.preview.post'),
            'Preview post route should exist'
        );

        $this->assertTrue(
            $this->app['router']->has('tallcms.preview.token'),
            'Preview token route should exist'
        );
    }

    public function test_contact_submit_route_exists(): void
    {
        $this->assertTrue(
            $this->app['router']->has('tallcms.contact.submit'),
            'Contact submit route should exist'
        );

        $route = $this->app['router']->getRoutes()->getByName('tallcms.contact.submit');
        $this->assertEquals(['POST'], $route->methods(), 'Contact route should accept POST');
    }

    public function test_middleware_aliases_registered(): void
    {
        $router = $this->app['router'];

        // Get middleware aliases
        $middlewareAliases = $router->getMiddleware();

        $this->assertArrayHasKey(
            'tallcms.maintenance',
            $middlewareAliases,
            'tallcms.maintenance middleware alias should be registered'
        );

        $this->assertArrayHasKey(
            'tallcms.theme-preview',
            $middlewareAliases,
            'tallcms.theme-preview middleware alias should be registered'
        );
    }

    public function test_config_is_loaded(): void
    {
        // Verify the package config is merged
        $this->assertNotNull(
            config('tallcms.version'),
            'TallCMS config should be loaded'
        );

        $this->assertNotNull(
            config('tallcms.plugin_mode'),
            'Plugin mode config should exist'
        );
    }

    public function test_helpers_are_loaded(): void
    {
        // Test that helper functions from the package are available
        $this->assertTrue(
            function_exists('active_theme'),
            'active_theme() helper should be available'
        );

        $this->assertTrue(
            function_exists('supports_theme_controller'),
            'supports_theme_controller() helper should be available'
        );

        $this->assertTrue(
            function_exists('daisyui_presets'),
            'daisyui_presets() helper should be available'
        );
    }
}
