<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Support\Facades\File;
use TallCms\Cms\Tests\TestCase;

/**
 * Regression test: zero-downtime deploys (Ploi/Forge/Envoyer) replace the
 * release directory each push, leaving public/themes/<slug> as a dangling
 * symlink — broken theme assets until someone re-flips the active theme
 * through the admin. `theme:publish-all` is the post-deploy hook that
 * restores every symlink in one call without the admin round-trip.
 */
class ThemePublishAllCommandTest extends TestCase
{
    protected array $createdSymlinks = [];

    protected function tearDown(): void
    {
        foreach ($this->createdSymlinks as $path) {
            if (is_link($path)) {
                @unlink($path);
            } elseif (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }

        parent::tearDown();
    }

    public function test_command_republishes_symlinks_for_all_bundled_themes(): void
    {
        $bundledSlugs = array_map('basename', File::directories(
            dirname(__DIR__, 2).'/resources/themes'
        ));

        // Track these for cleanup before we touch anything
        foreach ($bundledSlugs as $slug) {
            $this->createdSymlinks[] = public_path("themes/{$slug}");
        }

        $this->artisan('theme:publish-all')
            ->assertSuccessful();

        foreach ($bundledSlugs as $slug) {
            $publicPath = public_path("themes/{$slug}");
            $this->assertTrue(
                is_link($publicPath) || File::isDirectory($publicPath),
                "Expected public/themes/{$slug} to exist after theme:publish-all (got nothing — deploy hook would leave assets broken).",
            );
        }
    }

    public function test_command_repairs_dangling_symlink(): void
    {
        // Simulate the post-deploy state: a stale symlink pointing at a
        // previous release directory that no longer exists.
        $bundledSlugs = array_map('basename', File::directories(
            dirname(__DIR__, 2).'/resources/themes'
        ));

        $this->assertNotEmpty($bundledSlugs, 'Need at least one bundled theme for this test.');

        $slug = $bundledSlugs[0];
        $publicPath = public_path("themes/{$slug}");
        $this->createdSymlinks[] = $publicPath;

        if (! File::isDirectory(public_path('themes'))) {
            File::makeDirectory(public_path('themes'), 0755, true);
        }

        // Drop a dangling symlink in place
        if (is_link($publicPath) || File::exists($publicPath)) {
            @unlink($publicPath);
        }
        @symlink('/nonexistent/release/dir/'.$slug.'/public', $publicPath);
        $this->assertTrue(is_link($publicPath), 'Failed to set up dangling symlink fixture.');
        $this->assertFalse(file_exists($publicPath), 'Fixture symlink should be dangling.');

        $this->artisan('theme:publish-all')
            ->assertSuccessful();

        $this->assertTrue(
            (is_link($publicPath) && file_exists($publicPath)) || File::isDirectory($publicPath),
            "Dangling symlink for {$slug} was not repaired — Ploi deploys would still serve broken theme assets.",
        );
    }
}
