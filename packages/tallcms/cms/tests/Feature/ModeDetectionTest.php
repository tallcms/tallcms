<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Tests\TestCase;
use TallCms\Cms\TallCmsServiceProvider;

class ModeDetectionTest extends TestCase
{
    public function test_explicit_standalone_mode_config(): void
    {
        $this->app['config']->set('tallcms.mode', 'standalone');

        $provider = new TallCmsServiceProvider($this->app);

        $this->assertTrue($provider->isStandaloneMode());
    }

    public function test_explicit_plugin_mode_config(): void
    {
        $this->app['config']->set('tallcms.mode', 'plugin');

        $provider = new TallCmsServiceProvider($this->app);

        $this->assertFalse($provider->isStandaloneMode());
    }

    public function test_auto_detect_plugin_mode_without_marker(): void
    {
        // Ensure no explicit mode is set
        $this->app['config']->set('tallcms.mode', null);

        // Ensure marker file doesn't exist (default for plugin mode)
        $markerPath = base_path('.tallcms-standalone');
        if (file_exists($markerPath)) {
            unlink($markerPath);
        }

        $provider = new TallCmsServiceProvider($this->app);

        $this->assertFalse($provider->isStandaloneMode());
    }

    public function test_mode_config_is_respected(): void
    {
        // Configure plugin mode
        $this->app['config']->set('tallcms.mode', 'plugin');

        $provider = new TallCmsServiceProvider($this->app);

        $this->assertFalse(
            $provider->isStandaloneMode(),
            'isStandaloneMode() should return false when mode is plugin'
        );
    }
}
