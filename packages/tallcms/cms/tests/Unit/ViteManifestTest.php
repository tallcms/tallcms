<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Support\ViteManifest;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for the helper that the CMS rich editor view uses to guard
 * its @vite() call against missing manifest entries — the difference
 * between plugin mode crashing on every admin page and silently
 * deferring to the FilamentAsset-registered fallback.
 */
class ViteManifestTest extends TestCase
{
    protected string $manifestPath;

    protected string $hotPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = public_path('build/manifest.json');
        $this->hotPath = public_path('hot');

        $this->cleanupVitePaths();
    }

    protected function tearDown(): void
    {
        $this->cleanupVitePaths();

        parent::tearDown();
    }

    public function test_returns_false_when_manifest_does_not_exist(): void
    {
        $this->assertFalse(
            ViteManifest::hasEntry('resources/css/anything.css'),
            'No manifest, no hot file → no Vite available; must return false so '
            .'the @vite() guard skips and avoids ViteException.'
        );
    }

    public function test_returns_true_when_entry_in_manifest(): void
    {
        $this->writeManifest([
            'resources/css/filament/admin/preview.css' => [
                'file' => 'assets/preview-abc123.css',
                'src' => 'resources/css/filament/admin/preview.css',
                'isEntry' => true,
            ],
        ]);

        $this->assertTrue(
            ViteManifest::hasEntry('resources/css/filament/admin/preview.css')
        );
    }

    public function test_returns_false_when_entry_not_in_manifest(): void
    {
        // Simulates a plugin-mode host that has its own Vite build with
        // resources/css/app.css but doesn't include the TallCMS preview.css.
        $this->writeManifest([
            'resources/css/app.css' => [
                'file' => 'assets/app-xyz789.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
        ]);

        $this->assertFalse(
            ViteManifest::hasEntry('resources/css/filament/admin/preview.css'),
            'Manifest exists but does not include the preview entry — must '
            .'return false so the @vite() guard skips. This is the regression '
            .'we are guarding against in plugin-mode installs.'
        );
    }

    public function test_returns_true_in_hot_mode_regardless_of_entry(): void
    {
        // No manifest, but `public/hot` exists — Vite dev server is running.
        // Defer to @vite (which uses dev URL) so missing entries get a 404
        // instead of being silently dropped.
        $this->writeHotFile();

        $this->assertTrue(ViteManifest::hasEntry('resources/css/anything.css'));
    }

    public function test_returns_false_when_manifest_is_unreadable_or_invalid_json(): void
    {
        $this->ensureBuildDir();
        file_put_contents($this->manifestPath, '{ this is not valid json');

        $this->assertFalse(
            ViteManifest::hasEntry('resources/css/anything.css'),
            'A malformed manifest must fail closed (return false) instead of '
            .'crashing the page render with a JSON decode error.'
        );
    }

    protected function writeManifest(array $entries): void
    {
        $this->ensureBuildDir();
        file_put_contents($this->manifestPath, json_encode($entries));
    }

    protected function writeHotFile(): void
    {
        if (! is_dir(public_path())) {
            mkdir(public_path(), 0755, true);
        }
        file_put_contents($this->hotPath, 'http://localhost:5173');
    }

    protected function ensureBuildDir(): void
    {
        $dir = public_path('build');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function cleanupVitePaths(): void
    {
        if (file_exists($this->manifestPath)) {
            @unlink($this->manifestPath);
        }
        if (file_exists($this->hotPath)) {
            @unlink($this->hotPath);
        }
    }
}
