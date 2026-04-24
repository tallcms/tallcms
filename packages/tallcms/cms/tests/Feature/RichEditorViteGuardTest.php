<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use TallCms\Cms\Tests\TestCase;

/**
 * Blade-level regression test for cms-rich-editor.blade.php's @vite guard.
 *
 * The bug we're guarding against isn't "ViteManifest::hasEntry returns
 * the wrong value" (covered by ViteManifestTest) — it's "rendering the
 * rich editor in plugin mode with no preview.css manifest entry throws
 * Illuminate\Foundation\ViteException." Helper-only coverage doesn't
 * prove the Blade-level guard actually works, so this test compiles the
 * exact guard snippet from the view via Blade::render() and verifies
 * both the regression case (manifest absent → guard skips) and the
 * standalone-protection case (manifest present → guard allows @vite).
 */
class RichEditorViteGuardTest extends TestCase
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

    public function test_rich_editor_vite_guard_does_not_throw_when_manifest_is_missing(): void
    {
        // Plugin-mode regression: with no manifest entry, the @vite directive
        // must not run — otherwise the page editor 500s the moment a user
        // opens it. If the guard regresses, this test throws ViteException.
        $rendered = $this->renderGuardSnippet();

        $this->assertStringNotContainsString('<link', $rendered,
            'With no manifest entry, the @vite directive must not emit a '
            .'<link> tag. The guard regressed if this assertion fails.');
    }

    public function test_rich_editor_vite_guard_runs_directive_when_manifest_contains_entry(): void
    {
        // Standalone-mode protection: when the manifest contains the entry,
        // the guard must let @vite run so block-preview styles still load.
        // Asserting on the file slug ('preview-test.css') is env-independent
        // — generated attributes and absolute paths can vary across test
        // configurations.
        $this->writeManifest([
            'resources/css/filament/admin/preview.css' => [
                'file' => 'assets/preview-test.css',
                'src' => 'resources/css/filament/admin/preview.css',
                'isEntry' => true,
            ],
        ]);

        $rendered = $this->renderGuardSnippet();

        $this->assertStringContainsString('preview-test.css', $rendered,
            'Guard regression: standalone (manifest-with-entry) must continue '
            .'to emit the Vite-built preview.css link. If this fails, the '
            .'guard is too restrictive and standalone block previews break.');
    }

    /**
     * Byte-identical to the @if() guard in cms-rich-editor.blade.php.
     * Keep in sync if the guard ever changes shape.
     */
    protected function renderGuardSnippet(): string
    {
        return Blade::render(<<<'BLADE'
            @if (\TallCms\Cms\Support\ViteManifest::hasEntry('resources/css/filament/admin/preview.css'))
                @vite('resources/css/filament/admin/preview.css')
            @endif
        BLADE);
    }

    protected function writeManifest(array $entries): void
    {
        $dir = public_path('build');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->manifestPath, json_encode($entries));
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
