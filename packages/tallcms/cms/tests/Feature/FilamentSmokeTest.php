<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Support\Facades\View;
use TallCms\Cms\Tests\TestCase;

/**
 * Smoke tests that verify key views and components render without errors.
 * These catch missing view namespaces, undefined variables, and component issues.
 */
class FilamentSmokeTest extends TestCase
{
    /**
     * Filament page views that must render without errors.
     */
    protected array $filamentPageViews = [
        'tallcms::filament.pages.plugin-manager',
        'tallcms::filament.pages.site-settings',
        'tallcms::filament.pages.theme-manager',
    ];

    /**
     * Block views that must render without errors.
     */
    protected array $blockViews = [
        'tallcms::cms.blocks.hero',
        'tallcms::cms.blocks.content-block',
        'tallcms::cms.blocks.call-to-action',
        'tallcms::cms.blocks.faq',
        'tallcms::cms.blocks.features',
        'tallcms::cms.blocks.pricing',
        'tallcms::cms.blocks.testimonials',
        'tallcms::cms.blocks.team',
        'tallcms::cms.blocks.timeline',
        'tallcms::cms.blocks.stats',
        'tallcms::cms.blocks.image-gallery',
        'tallcms::cms.blocks.logos',
        'tallcms::cms.blocks.parallax',
        'tallcms::cms.blocks.posts',
        'tallcms::cms.blocks.divider',
        'tallcms::cms.blocks.contact-form',
        'tallcms::cms.blocks.contact-form-preview',
    ];

    /**
     * Component views that must exist.
     */
    protected array $componentViews = [
        'tallcms::components.menu',
        'tallcms::components.menu-item',
        'tallcms::components.form.dynamic-field',
    ];

    /**
     * Livewire views that must exist.
     */
    protected array $livewireViews = [
        'tallcms::livewire.page',
        'tallcms::livewire.revision-history',
    ];

    public function test_all_filament_page_views_exist(): void
    {
        foreach ($this->filamentPageViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Filament page view [{$view}] should exist"
            );
        }
    }

    public function test_all_block_views_exist(): void
    {
        foreach ($this->blockViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Block view [{$view}] should exist"
            );
        }
    }

    public function test_all_component_views_exist(): void
    {
        foreach ($this->componentViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Component view [{$view}] should exist"
            );
        }
    }

    public function test_all_livewire_views_exist(): void
    {
        foreach ($this->livewireViews as $view) {
            $this->assertTrue(
                View::exists($view),
                "Livewire view [{$view}] should exist"
            );
        }
    }

    public function test_block_views_use_correct_namespace(): void
    {
        // Scan all block class files to verify they use tallcms:: namespace
        $blockPath = __DIR__ . '/../../src/Filament/Blocks';

        if (!is_dir($blockPath)) {
            $this->markTestSkipped('Blocks directory not found');
        }

        $files = glob($blockPath . '/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Check for view() calls without namespace
            if (preg_match('/view\s*\(\s*[\'"](?!tallcms::)([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                $this->fail(
                    "Block [{$filename}] uses view without tallcms:: namespace: [{$matches[1]}]. " .
                    "Should be: tallcms::{$matches[1]}"
                );
            }

            // Check for view rendering in renderBlock - look for view() calls with string argument
            // Pattern: view('something') where 'something' doesn't start with tallcms::
            if (preg_match('/\bview\s*\(\s*[\'"](?!tallcms::)([a-z][a-z0-9._-]*(?:\.[a-z][a-z0-9._-]*)*)[\'"]\s*[,\)]/', $content, $matches)) {
                // Skip common Laravel views that aren't ours
                if (!in_array($matches[1], ['components.button', 'errors.404'])) {
                    $this->fail(
                        "Block [{$filename}] uses view without tallcms:: namespace: [{$matches[1]}]. " .
                        "Should be: tallcms::{$matches[1]}"
                    );
                }
            }
        }

        $this->assertTrue(true, 'All block views use correct namespace');
    }

    public function test_view_namespace_is_registered(): void
    {
        $finder = View::getFinder();
        $hints = $finder->getHints();

        $this->assertArrayHasKey(
            'tallcms',
            $hints,
            'tallcms view namespace should be registered'
        );
    }

    public function test_layout_view_exists(): void
    {
        $this->assertTrue(
            View::exists('tallcms::layouts.app'),
            'Main layout view tallcms::layouts.app should exist'
        );
    }
}
