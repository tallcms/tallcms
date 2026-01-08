<?php

namespace Tallcms\Pro\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Tallcms\Pro\Models\AnalyticsCache;
use Tallcms\Pro\Models\ProSetting;

class AnalyticsManager
{
    protected ?AnalyticsProviderInterface $provider = null;

    protected array $providers = [
        'google' => GoogleAnalyticsProvider::class,
    ];

    /**
     * Get the configured analytics provider
     */
    public function getProvider(): ?AnalyticsProviderInterface
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $providerName = ProSetting::get('analytics_provider', 'google');

        if (! isset($this->providers[$providerName])) {
            return null;
        }

        $providerClass = $this->providers[$providerName];
        $this->provider = app($providerClass);

        return $this->provider;
    }

    /**
     * Check if analytics is configured
     */
    public function isConfigured(): bool
    {
        $provider = $this->getProvider();

        return $provider !== null && $provider->isConfigured();
    }

    /**
     * Get overview metrics with caching
     */
    public function getOverviewMetrics(string $period = '7d'): array
    {
        return $this->getCached('overview', $period, function () use ($period) {
            $provider = $this->getProvider();

            if (! $provider || ! $provider->isConfigured()) {
                return $this->getEmptyOverviewMetrics();
            }

            try {
                return $provider->getOverviewMetrics($period);
            } catch (\Throwable $e) {
                \Log::warning('Analytics overview fetch failed', [
                    'error' => $e->getMessage(),
                ]);

                return $this->getEmptyOverviewMetrics();
            }
        });
    }

    /**
     * Get top pages with caching
     */
    public function getTopPages(int $limit = 5, string $period = '7d'): array
    {
        return $this->getCached("top_pages_{$limit}", $period, function () use ($limit, $period) {
            $provider = $this->getProvider();

            if (! $provider || ! $provider->isConfigured()) {
                return [];
            }

            try {
                return $provider->getTopPages($limit, $period);
            } catch (\Throwable $e) {
                \Log::warning('Analytics top pages fetch failed', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get traffic sources with caching
     */
    public function getTrafficSources(int $limit = 5, string $period = '7d'): array
    {
        return $this->getCached("traffic_sources_{$limit}", $period, function () use ($limit, $period) {
            $provider = $this->getProvider();

            if (! $provider || ! $provider->isConfigured()) {
                return [];
            }

            try {
                return $provider->getTrafficSources($limit, $period);
            } catch (\Throwable $e) {
                \Log::warning('Analytics traffic sources fetch failed', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get visitor trend with caching
     */
    public function getVisitorTrend(string $period = '7d'): array
    {
        return $this->getCached('visitor_trend', $period, function () use ($period) {
            $provider = $this->getProvider();

            if (! $provider || ! $provider->isConfigured()) {
                return [];
            }

            try {
                return $provider->getVisitorTrend($period);
            } catch (\Throwable $e) {
                \Log::warning('Analytics visitor trend fetch failed', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Test the analytics connection
     */
    public function testConnection(): bool
    {
        $provider = $this->getProvider();

        if (! $provider) {
            return false;
        }

        try {
            return $provider->testConnection();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Clear all analytics cache
     */
    public function clearCache(): void
    {
        AnalyticsCache::query()->delete();
    }

    /**
     * Get cached data or fetch fresh
     */
    protected function getCached(string $metric, string $period, callable $fetcher): array
    {
        $provider = $this->getProvider();
        $providerName = $provider ? $provider->getName() : 'none';

        $cached = AnalyticsCache::where('provider', $providerName)
            ->where('metric', $metric)
            ->where('period', $period)
            ->where('expires_at', '>', now())
            ->first();

        if ($cached) {
            return $cached->value ?? [];
        }

        $value = $fetcher();

        // Cache based on config TTL (default 900 seconds = 15 minutes)
        AnalyticsCache::updateOrCreate(
            [
                'provider' => $providerName,
                'metric' => $metric,
                'period' => $period,
            ],
            [
                'value' => $value,
                'fetched_at' => now(),
                'expires_at' => now()->addSeconds(config('tallcms-pro.analytics.cache_ttl', 900)),
            ]
        );

        return $value;
    }

    /**
     * Get empty overview metrics structure
     */
    protected function getEmptyOverviewMetrics(): array
    {
        return [
            'visitors' => 0,
            'pageviews' => 0,
            'bounce_rate' => 0,
            'avg_session_duration' => 0,
            'visitors_change' => 0,
            'pageviews_change' => 0,
        ];
    }

    /**
     * Get available provider names
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }
}
