<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;

/**
 * Tests that verify the config file has all required keys.
 * These catch issues where package config is missing required fields
 * that cause runtime errors.
 */
class ConfigSchemaTest extends TestCase
{
    public function test_config_has_version(): void
    {
        $this->assertNotNull(
            config('tallcms.version'),
            'Config must have version key'
        );
    }

    public function test_config_has_database_section(): void
    {
        $this->assertIsArray(
            config('tallcms.database'),
            'Config must have database section'
        );

        $this->assertArrayHasKey(
            'prefix',
            config('tallcms.database'),
            'Database config must have prefix key'
        );
    }

    public function test_config_has_plugin_mode_section(): void
    {
        $pluginMode = config('tallcms.plugin_mode');

        $this->assertIsArray($pluginMode, 'Config must have plugin_mode section');

        $requiredKeys = [
            'routes_enabled',
            'routes_prefix',
            'route_name_prefix',
            'catch_all_enabled',
            'api_routes_enabled',
            'preview_routes_enabled',
            'plugins_enabled',
            'themes_enabled',
            'user_model',
            'skip_installer_check',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $pluginMode,
                "plugin_mode config must have '{$key}' key"
            );
        }
    }

    public function test_config_has_filament_section(): void
    {
        $filament = config('tallcms.filament');

        $this->assertIsArray($filament, 'Config must have filament section');

        $requiredKeys = [
            'panel_id',
            'panel_path',
            'navigation_group',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $filament,
                "filament config must have '{$key}' key"
            );
        }
    }

    public function test_config_has_plugins_section(): void
    {
        $plugins = config('tallcms.plugins');

        $this->assertIsArray($plugins, 'Config must have plugins section');

        $requiredKeys = [
            'path',
            'allow_uploads',
            'max_upload_size',
            'cache_enabled',
            'auto_migrate',
            'license',
            'catalog',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $plugins,
                "plugins config must have '{$key}' key"
            );
        }
    }

    public function test_config_has_themes_section(): void
    {
        $themes = config('tallcms.themes');

        $this->assertIsArray($themes, 'Config must have themes section');

        $requiredKeys = [
            'path',
            'allow_uploads',
            'max_upload_size',
            'cache_enabled',
            'preview_duration',
            'rollback_duration',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $themes,
                "themes config must have '{$key}' key"
            );
        }
    }

    public function test_config_has_publishing_section(): void
    {
        $publishing = config('tallcms.publishing');

        $this->assertIsArray($publishing, 'Config must have publishing section');

        $requiredKeys = [
            'revision_limit',
            'revision_manual_limit',
            'notification_channels',
            'default_preview_expiry_hours',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $publishing,
                "publishing config must have '{$key}' key"
            );
        }
    }

    public function test_plugin_catalog_entries_have_required_fields(): void
    {
        $catalog = config('tallcms.plugins.catalog', []);

        foreach ($catalog as $slug => $plugin) {
            $requiredFields = [
                'name',
                'slug',
                'vendor',
                'description',
                'author',
                'download_url', // This was the missing field that caused issues!
            ];

            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $plugin,
                    "Plugin catalog entry [{$slug}] must have '{$field}' field"
                );
            }
        }
    }

    public function test_plugin_license_config_has_required_fields(): void
    {
        $license = config('tallcms.plugins.license');

        $this->assertIsArray($license, 'Plugin license config must exist');

        $requiredKeys = [
            'proxy_url',
            'cache_ttl',
            'offline_grace_days',
            'renewal_grace_days',
            'update_check_interval',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $license,
                "plugins.license config must have '{$key}' key"
            );
        }
    }

    public function test_config_has_updates_section(): void
    {
        $updates = config('tallcms.updates');

        $this->assertIsArray($updates, 'Config must have updates section');

        $requiredKeys = [
            'enabled',
            'check_interval',
            'cache_ttl',
            'github_repo',
            'backup_retention',
            'public_key',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $updates,
                "updates config must have '{$key}' key"
            );
        }
    }

    public function test_config_values_have_sensible_defaults(): void
    {
        // Routes should be disabled by default in plugin mode (safety)
        $this->assertFalse(
            config('tallcms.plugin_mode.routes_enabled'),
            'Frontend routes should be disabled by default in plugin mode'
        );

        // But essential routes should be enabled by default
        $this->assertTrue(
            config('tallcms.plugin_mode.preview_routes_enabled'),
            'Preview routes should be enabled by default'
        );

        $this->assertTrue(
            config('tallcms.plugin_mode.api_routes_enabled'),
            'API routes should be enabled by default'
        );

        // Themes and plugins disabled by default in plugin mode
        $this->assertFalse(
            config('tallcms.plugin_mode.themes_enabled'),
            'Themes should be disabled by default in plugin mode'
        );

        $this->assertFalse(
            config('tallcms.plugin_mode.plugins_enabled'),
            'Plugins should be disabled by default in plugin mode'
        );
    }
}
