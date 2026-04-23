<?php

namespace Tests\Unit;

use TallCms\Cms\Services\TallCmsUpdater;
use Tests\TestCase;

/**
 * Regression test for the cache-poisoning bug.
 *
 * If the GitHub release exists but its asset upload (zip + checksums + sig)
 * hasn't completed yet, fetching the release returns a release object with
 * empty assets. Caching that for the default 1-hour TTL meant every
 * tallcms:update for the next hour failed with "Missing required release
 * files" — even though the assets had since uploaded — because the cache
 * served the stale empty-assets object.
 */
class TallCmsUpdaterReleaseAssetsTest extends TestCase
{
    public function test_release_with_all_three_assets_is_complete(): void
    {
        $release = [
            'tag' => 'v4.0.10',
            'assets' => [
                ['name' => 'tallcms-v4.0.10.zip', 'url' => 'x', 'size' => 1],
                ['name' => 'checksums.json', 'url' => 'x', 'size' => 1],
                ['name' => 'checksums.json.sig', 'url' => 'x', 'size' => 1],
            ],
        ];

        $this->assertTrue(TallCmsUpdater::releaseHasRequiredAssets($release));
    }

    public function test_release_with_empty_assets_is_incomplete(): void
    {
        $release = ['tag' => 'v4.0.10', 'assets' => []];

        $this->assertFalse(TallCmsUpdater::releaseHasRequiredAssets($release));
    }

    public function test_release_missing_signature_is_incomplete(): void
    {
        $release = [
            'tag' => 'v4.0.10',
            'assets' => [
                ['name' => 'tallcms-v4.0.10.zip', 'url' => 'x', 'size' => 1],
                ['name' => 'checksums.json', 'url' => 'x', 'size' => 1],
            ],
        ];

        $this->assertFalse(TallCmsUpdater::releaseHasRequiredAssets($release));
    }

    public function test_release_missing_zip_is_incomplete(): void
    {
        $release = [
            'tag' => 'v4.0.10',
            'assets' => [
                ['name' => 'checksums.json', 'url' => 'x', 'size' => 1],
                ['name' => 'checksums.json.sig', 'url' => 'x', 'size' => 1],
            ],
        ];

        $this->assertFalse(TallCmsUpdater::releaseHasRequiredAssets($release));
    }
}
