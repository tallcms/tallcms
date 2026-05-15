<?php

declare(strict_types=1);

namespace TallCms\Cms\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class MenuCache
{
    private const VersionKey = 'tallcms.menu.version';

    /**
     * @template TCacheValue
     *
     * @param  Closure(): TCacheValue  $callback
     * @return TCacheValue
     */
    public static function remember(string $key, mixed $ttl, Closure $callback): mixed
    {
        $cacheKey = $key.'|v'.self::version();

        if (self::supportsTags()) {
            return Cache::tags(['cms', 'cms:menus'])->remember($cacheKey, $ttl, $callback);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    public static function flush(): bool
    {
        self::incrementVersion();

        if (! self::supportsTags()) {
            return true;
        }

        return Cache::tags(['cms', 'cms:menus'])->flush();
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VersionKey, 1);
    }

    private static function incrementVersion(): void
    {
        Cache::add(self::VersionKey, 1, now()->addYears(10));

        if (Cache::increment(self::VersionKey) === false) {
            Cache::forever(self::VersionKey, self::version() + 1);
        }
    }

    private static function supportsTags(): bool
    {
        return method_exists(Cache::getFacadeRoot(), 'supportsTags')
            && Cache::supportsTags();
    }
}
