<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use TallCms\Cms\Support\MenuCache;
use TallCms\Cms\Tests\TestCase;

class MenuCacheTest extends TestCase
{
    public function test_it_uses_tagged_cache_when_the_store_supports_tags(): void
    {
        Cache::setDefaultDriver('array');
        Cache::purge('array');
        Cache::flush();

        $this->assertTrue(Cache::supportsTags());

        $calls = 0;
        $key = 'tallcms.menu.test.'.Str::random(8);

        $this->assertSame('cached-menu', MenuCache::remember($key, now()->addHour(), function () use (&$calls): string {
            $calls++;

            return 'cached-menu';
        }));

        $this->assertSame('cached-menu', MenuCache::remember($key, now()->addHour(), function () use (&$calls): string {
            $calls++;

            return 'cached-menu';
        }));

        $this->assertSame(1, $calls);
        $this->assertTrue(MenuCache::flush());

        $this->assertSame('cached-menu', MenuCache::remember($key, now()->addHour(), function () use (&$calls): string {
            $calls++;

            return 'cached-menu';
        }));

        $this->assertSame(2, $calls);
    }

    public function test_it_uses_versioned_fallback_for_cache_stores_without_tags(): void
    {
        config()->set('cache.default', 'file');
        config()->set('cache.stores.file', [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ]);
        Cache::setDefaultDriver('file');
        Cache::purge('file');

        Cache::flush();

        $this->assertFalse(Cache::supportsTags());

        $calls = 0;
        $key = 'tallcms.menu.test.'.Str::random(8);

        $this->assertSame('cached-menu', MenuCache::remember($key, now()->addHour(), function () use (&$calls): string {
            $calls++;

            return 'cached-menu';
        }));

        $this->assertSame('cached-menu', MenuCache::remember($key, now()->addHour(), function () use (&$calls): string {
            $calls++;

            return 'cached-menu';
        }));

        $this->assertSame(1, $calls);
        $this->assertTrue(MenuCache::flush());

        $this->assertSame('cached-menu', MenuCache::remember($key, now()->addHour(), function () use (&$calls): string {
            $calls++;

            return 'cached-menu';
        }));

        $this->assertSame(2, $calls);
    }
}
