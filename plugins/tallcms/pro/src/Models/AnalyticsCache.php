<?php

namespace Tallcms\Pro\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsCache extends Model
{
    protected $table = 'tallcms_pro_analytics_cache';

    protected $fillable = [
        'provider',
        'metric',
        'period',
        'value',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'array',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the cached data is still fresh
     */
    public function isFresh(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Get cached data or fetch fresh if expired
     */
    public static function getOrFetch(
        string $provider,
        string $metric,
        string $period,
        callable $fetcher
    ): array {
        $cached = static::where([
            'provider' => $provider,
            'metric' => $metric,
            'period' => $period,
        ])->first();

        if ($cached && $cached->isFresh()) {
            return $cached->value;
        }

        // Fetch fresh data
        try {
            $value = $fetcher();
        } catch (\Exception $e) {
            // If fetch fails and we have stale data, return it
            if ($cached) {
                return $cached->value;
            }
            throw $e;
        }

        $cacheTtl = config('tallcms-pro.analytics.cache_ttl', 900);

        static::updateOrCreate(
            [
                'provider' => $provider,
                'metric' => $metric,
                'period' => $period,
            ],
            [
                'value' => $value,
                'fetched_at' => now(),
                'expires_at' => now()->addSeconds($cacheTtl),
            ]
        );

        return $value;
    }

    /**
     * Clear all cached data for a provider
     */
    public static function clearProvider(string $provider): int
    {
        return static::where('provider', $provider)->delete();
    }

    /**
     * Clear all expired cache entries
     */
    public static function clearExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }

    /**
     * Clear all analytics cache
     */
    public static function clearAll(): int
    {
        return static::truncate();
    }
}
