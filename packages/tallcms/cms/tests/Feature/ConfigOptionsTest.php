<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;

/**
 * Tests for config options affecting service provider behavior.
 * These tests verify that config settings properly control route registration.
 */
class ConfigOptionsTest extends TestCase
{
    public function test_preview_routes_enabled_config_defaults_to_true(): void
    {
        // Check the package config file directly
        $configPath = __DIR__ . '/../../config/tallcms.php';
        $config = require $configPath;

        $this->assertTrue(
            $config['plugin_mode']['preview_routes_enabled'] ?? true,
            'preview_routes_enabled should default to true'
        );
    }

    public function test_api_routes_enabled_config_defaults_to_true(): void
    {
        $configPath = __DIR__ . '/../../config/tallcms.php';
        $config = require $configPath;

        $this->assertTrue(
            $config['plugin_mode']['api_routes_enabled'] ?? true,
            'api_routes_enabled should default to true'
        );
    }

    public function test_essential_routes_prefix_config_defaults_to_empty(): void
    {
        $configPath = __DIR__ . '/../../config/tallcms.php';
        $config = require $configPath;

        $this->assertEquals(
            '',
            $config['plugin_mode']['essential_routes_prefix'] ?? '',
            'essential_routes_prefix should default to empty string'
        );
    }

    public function test_config_has_all_plugin_mode_keys(): void
    {
        $configPath = __DIR__ . '/../../config/tallcms.php';
        $config = require $configPath;

        $requiredKeys = [
            'routes_enabled',
            'routes_prefix',
            'route_name_prefix',
            'route_exclusions',
            'api_routes_enabled',
            'preview_routes_enabled',
            'essential_routes_prefix',
            'plugins_enabled',
            'themes_enabled',
            'user_model',
            'skip_installer_check',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $config['plugin_mode'] ?? [],
                "plugin_mode config should have '{$key}' key"
            );
        }
    }

    public function test_preview_routes_are_registered_when_enabled(): void
    {
        // The TestCase sets preview_routes_enabled to true by default
        $this->assertTrue(
            $this->app['router']->has('tallcms.preview.page'),
            'tallcms.preview.page route should be registered when preview_routes_enabled is true'
        );
    }

    public function test_contact_route_is_registered_when_api_enabled(): void
    {
        // The TestCase sets api_routes_enabled to true by default
        $this->assertTrue(
            $this->app['router']->has('tallcms.contact.submit'),
            'tallcms.contact.submit route should be registered when api_routes_enabled is true'
        );
    }
}
