<?php

namespace TallCms\Cms\Tests\Feature;

use TallCms\Cms\Services\ThemeManager;
use TallCms\Cms\Tests\TestCase;

/**
 * Regression test: plugin-mode hosts don't have `resources/js/tallcms-core.js`
 * in their Vite manifest (the entry only exists in the standalone scaffold's
 * vite.config.js). Before the fallback, @tallcmsCoreJs emitted an empty
 * string, the contact-form / comments Alpine components never registered,
 * and the frontend logged:
 *
 *   Alpine Expression Error: contactForm is not defined
 *   Alpine Expression Error: formError is not defined
 *
 * The fallback resolves to public/vendor/tallcms/tallcms.js, the pre-built
 * copy of the runtime that vendor:publish writes to a host's public dir.
 */
class CoreJsTagPluginModeFallbackTest extends TestCase
{
    protected string $manifestPath;

    protected string $hotFile;

    protected string $vendorJsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifestPath = public_path('build/manifest.json');
        $this->hotFile = public_path('hot');
        $this->vendorJsPath = public_path('vendor/tallcms/tallcms.js');

        $this->cleanupTestArtefacts();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestArtefacts();

        parent::tearDown();
    }

    public function test_returns_empty_when_no_manifest_and_no_vendor_published_js(): void
    {
        $tag = $this->getTag();

        $this->assertSame('', $tag,
            'Without any source available, the directive should emit nothing — '
            .'better than emitting a broken script src.');
    }

    public function test_returns_vendor_published_script_when_only_that_exists(): void
    {
        // Plugin-mode case: no host Vite manifest, but vendor:publish has
        // written tallcms.js to public/vendor/tallcms/.
        $this->writeVendorPublishedJs();

        $tag = $this->getTag();

        $this->assertStringContainsString('vendor/tallcms/tallcms.js', $tag,
            'Plugin-mode hosts have no Vite entry for tallcms-core.js — the '
            .'directive must fall back to the published copy under '
            .'public/vendor/tallcms/ so contact-form and comments Alpine '
            .'components register at runtime.');
    }

    public function test_prefers_vite_manifest_over_vendor_published_js(): void
    {
        // Standalone case: host's Vite manifest has the entry. Even if the
        // vendor file also exists (e.g. user re-published assets), the Vite
        // build is the authoritative source.
        $this->writeManifestWithEntry();
        $this->writeVendorPublishedJs();

        $tag = $this->getTag();

        $this->assertStringContainsString('build/assets/tallcms-core-test.js', $tag);
        $this->assertStringNotContainsString('vendor/tallcms', $tag,
            'When both sources are available, the host Vite build wins — '
            .'protects standalone behavior so dev hot-reload and source '
            .'iteration on tallcms-core.js still work.');
    }

    public function test_dev_hot_mode_takes_absolute_precedence(): void
    {
        $this->writeHotFile();
        $this->writeManifestWithEntry();
        $this->writeVendorPublishedJs();

        $tag = $this->getTag();

        $this->assertStringContainsString('http://localhost:5173/resources/js/tallcms-core.js', $tag,
            'When the dev server is running, defer to it — neither the manifest '
            .'nor the vendor-published copy should override.');
    }

    protected function getTag(): string
    {
        return app(ThemeManager::class)->getCoreJsTag();
    }

    protected function writeManifestWithEntry(): void
    {
        $this->ensureBuildDir();
        file_put_contents($this->manifestPath, json_encode([
            'resources/js/tallcms-core.js' => [
                'file' => 'assets/tallcms-core-test.js',
                'src' => 'resources/js/tallcms-core.js',
                'isEntry' => true,
            ],
        ]));
    }

    protected function writeVendorPublishedJs(): void
    {
        $dir = dirname($this->vendorJsPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->vendorJsPath, '/* fake tallcms.js for tests */');
    }

    protected function writeHotFile(): void
    {
        if (! is_dir(public_path())) {
            mkdir(public_path(), 0755, true);
        }
        file_put_contents($this->hotFile, 'http://localhost:5173');
    }

    protected function ensureBuildDir(): void
    {
        $dir = public_path('build');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function cleanupTestArtefacts(): void
    {
        @unlink($this->manifestPath);
        @unlink($this->hotFile);
        @unlink($this->vendorJsPath);
    }
}
