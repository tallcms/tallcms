<?php

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplaceCatalogService
{
    /**
     * Get all plugin items from the marketplace catalog
     */
    public function getPlugins(): array
    {
        return $this->fetchCatalog('plugin');
    }

    /**
     * Get all theme items from the marketplace catalog
     */
    public function getThemes(): array
    {
        return $this->fetchCatalog('theme');
    }

    /**
     * Get all items from the marketplace catalog
     */
    public function getAll(): array
    {
        return $this->fetchCatalog();
    }

    /**
     * Find a catalog item by its full slug
     */
    public function findBySlug(string $fullSlug): ?array
    {
        $all = $this->getAll();

        return collect($all)->firstWhere('full_slug', $fullSlug);
    }

    /**
     * Get purchase URL for a plugin/theme, with config fallback
     */
    public function getPurchaseUrl(string $fullSlug): ?string
    {
        // 1. Config override (durable fallback)
        $configUrl = config("tallcms.plugins.license.purchase_urls.{$fullSlug}");
        if ($configUrl) {
            return $configUrl;
        }

        // 2. From cached remote catalog
        $item = $this->findBySlug($fullSlug);

        return $item['purchase_url'] ?? null;
    }

    /**
     * Get download URL for a plugin/theme, with config fallback
     */
    public function getDownloadUrl(string $fullSlug): ?string
    {
        // 1. Config override (durable fallback)
        $configUrl = config("tallcms.plugins.license.download_urls.{$fullSlug}");
        if ($configUrl) {
            return $configUrl;
        }

        // 2. From cached remote catalog
        $item = $this->findBySlug($fullSlug);

        return $item['download_url'] ?? null;
    }

    /**
     * Clear all cached catalog data
     */
    public function clearCache(): void
    {
        Cache::forget('marketplace_catalog');
        Cache::forget('marketplace_catalog_plugin');
        Cache::forget('marketplace_catalog_theme');
    }

    /**
     * Fetch catalog from the remote marketplace API with caching
     */
    protected function fetchCatalog(?string $type = null): array
    {
        $cacheKey = 'marketplace_catalog'.($type ? "_{$type}" : '');
        $cacheTtl = config('tallcms.plugins.catalog_cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($type) {
            $url = config('tallcms.plugins.catalog_url');
            if (empty($url)) {
                return [];
            }

            try {
                $params = $type ? ['type' => $type] : [];
                $response = Http::timeout(10)->acceptJson()->get($url, $params);

                if ($response->successful()) {
                    return $response->json('items', []);
                }

                Log::warning('Marketplace catalog fetch failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Marketplace catalog unreachable', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        });
    }
}
