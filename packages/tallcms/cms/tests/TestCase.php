<?php

namespace TallCms\Cms\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TallCms\Cms\TallCmsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TallCmsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use SQLite in-memory for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up basic app config
        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');

        // Configure for plugin mode by default in tests
        $app['config']->set('tallcms.mode', 'plugin');
        $app['config']->set('tallcms.plugin_mode.preview_routes_enabled', true);
        $app['config']->set('tallcms.plugin_mode.api_routes_enabled', true);
    }

    /**
     * Configure the test to run in standalone mode
     */
    protected function configureStandaloneMode($app): void
    {
        $app['config']->set('tallcms.mode', 'standalone');
    }

    /**
     * Configure the test to run in plugin mode
     */
    protected function configurePluginMode($app): void
    {
        $app['config']->set('tallcms.mode', 'plugin');
    }
}
