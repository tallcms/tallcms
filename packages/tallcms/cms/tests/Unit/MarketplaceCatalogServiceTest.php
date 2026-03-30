<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use TallCms\Cms\Services\MarketplaceCatalogService;
use TallCms\Cms\Tests\TestCase;

class MarketplaceCatalogServiceTest extends TestCase
{
    protected MarketplaceCatalogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarketplaceCatalogService;
        Cache::flush();
    }

    protected function fakeApiResponse(array $items = []): void
    {
        Http::fake([
            '*/marketplace-api/v1/catalog*' => Http::response([
                'items' => $items,
                'version' => '2',
            ], 200),
        ]);
    }

    protected function sampleItems(): array
    {
        return [
            [
                'full_slug' => 'tallcms/pro',
                'name' => 'TallCMS Pro',
                'description' => 'Advanced blocks',
                'author' => 'TallCMS',
                'item_type' => 'plugin',
                'download_url' => 'https://example.com/download/pro',
                'purchase_url' => 'https://example.com/purchase/pro',
                'homepage' => 'https://tallcms.com/pro',
                'featured' => true,
                'is_paid' => true,
                'requires_license' => true,
            ],
            [
                'full_slug' => 'tallcms/theme-elevate',
                'name' => 'Elevate Theme',
                'description' => 'A premium theme',
                'author' => 'TallCMS',
                'item_type' => 'theme',
                'download_url' => 'https://example.com/download/elevate',
                'purchase_url' => 'https://example.com/purchase/elevate',
                'homepage' => null,
                'featured' => false,
                'is_paid' => true,
                'requires_license' => false,
            ],
            [
                'full_slug' => 'community/hello-world',
                'name' => 'Hello World',
                'description' => 'A free plugin',
                'author' => 'Community Dev',
                'item_type' => 'plugin',
                'download_url' => 'https://example.com/download/hello',
                'purchase_url' => null,
                'homepage' => null,
                'featured' => false,
                'is_paid' => false,
                'requires_license' => false,
            ],
        ];
    }

    public function test_get_plugins_filters_by_type(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        $plugins = $this->service->getPlugins();

        // Should only return plugins (API is called with type=plugin filter)
        $this->assertIsArray($plugins);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'type=plugin'));
    }

    public function test_get_themes_filters_by_type(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        $themes = $this->service->getThemes();

        $this->assertIsArray($themes);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'type=theme'));
    }

    public function test_get_all_fetches_without_type_filter(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        $all = $this->service->getAll();

        $this->assertIsArray($all);
        $this->assertCount(3, $all);
        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'type='));
    }

    public function test_find_by_slug_returns_matching_item(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        $item = $this->service->findBySlug('tallcms/pro');

        $this->assertNotNull($item);
        $this->assertSame('TallCMS Pro', $item['name']);
    }

    public function test_find_by_slug_returns_null_for_unknown(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        $item = $this->service->findBySlug('nonexistent/plugin');

        $this->assertNull($item);
    }

    public function test_results_are_cached(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        // First call hits API
        $this->service->getAll();
        // Second call should use cache
        $this->service->getAll();

        Http::assertSentCount(1);
    }

    public function test_clear_cache_forces_fresh_fetch(): void
    {
        $this->fakeApiResponse($this->sampleItems());

        $this->service->getAll();
        $this->service->clearCache();
        $this->service->getAll();

        Http::assertSentCount(2);
    }

    public function test_returns_empty_array_when_api_unreachable(): void
    {
        Http::fake([
            '*/marketplace-api/v1/catalog*' => Http::response(null, 500),
        ]);

        $result = $this->service->getPlugins();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_returns_empty_array_when_api_times_out(): void
    {
        Http::fake([
            '*/marketplace-api/v1/catalog*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out'),
        ]);

        $result = $this->service->getPlugins();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_returns_empty_array_when_catalog_url_is_empty(): void
    {
        config(['tallcms.plugins.catalog_url' => '']);

        $result = $this->service->getAll();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        Http::assertNothingSent();
    }

    public function test_get_purchase_url_prefers_config_fallback(): void
    {
        config(['tallcms.plugins.license.purchase_urls.tallcms/pro' => 'https://config-fallback.com/purchase']);
        $this->fakeApiResponse($this->sampleItems());

        $url = $this->service->getPurchaseUrl('tallcms/pro');

        $this->assertSame('https://config-fallback.com/purchase', $url);
    }

    public function test_get_purchase_url_falls_back_to_catalog(): void
    {
        config(['tallcms.plugins.license.purchase_urls' => []]);
        $this->fakeApiResponse($this->sampleItems());

        $url = $this->service->getPurchaseUrl('tallcms/pro');

        $this->assertSame('https://example.com/purchase/pro', $url);
    }

    public function test_get_download_url_prefers_config_fallback(): void
    {
        config(['tallcms.plugins.license.download_urls.tallcms/pro' => 'https://config-fallback.com/download']);
        $this->fakeApiResponse($this->sampleItems());

        $url = $this->service->getDownloadUrl('tallcms/pro');

        $this->assertSame('https://config-fallback.com/download', $url);
    }

    public function test_get_download_url_falls_back_to_catalog(): void
    {
        config(['tallcms.plugins.license.download_urls' => []]);
        $this->fakeApiResponse($this->sampleItems());

        $url = $this->service->getDownloadUrl('tallcms/pro');

        $this->assertSame('https://example.com/download/pro', $url);
    }

    public function test_get_purchase_url_returns_null_when_not_found(): void
    {
        config(['tallcms.plugins.license.purchase_urls' => []]);
        $this->fakeApiResponse([]);

        $url = $this->service->getPurchaseUrl('nonexistent/plugin');

        $this->assertNull($url);
    }
}
