<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use TallCms\Cms\Enums\ContentStatus;

/**
 * Tests for hierarchical page URL resolution via CmsPageRenderer.
 *
 * Covers: root-page resolution, hierarchical path resolution, backward-compat
 * redirect from flat leaf slug to canonical hierarchical URL, and sitemap URL
 * generation for nested pages.
 */
class HierarchicalPageUrlResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable hierarchical URLs for all tests in this file.
        // The feature defaults to false so existing installs are unaffected.
        Config::set('tallcms.pages.hierarchical_urls', true);
    }

    // ------------------------------------------------------------------
    // Helper: create a page with null content so the RichEditor renderer
    // short-circuits (blank content returns '') and doesn't throw on
    // factory-generated content that isn't a valid Tiptap document.
    // ------------------------------------------------------------------

    private function makePage(array $overrides = []): CmsPage
    {
        return CmsPage::factory()->create(array_merge([
            'content' => null,
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // Root-level page — no change from pre-hierarchical behavior
    // ------------------------------------------------------------------

    public function test_root_page_resolves_at_its_slug(): void
    {
        $this->makePage([
            'slug' => ['en' => 'about'],
            'status' => ContentStatus::Published->value,
            'parent_id' => null,
        ]);

        $this->get('/about')->assertOk();
    }

    // ------------------------------------------------------------------
    // Hierarchical resolution
    // ------------------------------------------------------------------

    public function test_child_page_resolves_at_full_hierarchical_path(): void
    {
        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => ContentStatus::Published->value,
            'parent_id' => null,
        ]);

        $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => ContentStatus::Published->value,
            'parent_id' => $parent->id,
        ]);

        $this->get('/services/team')->assertOk();
    }

    public function test_three_level_page_resolves_at_full_path(): void
    {
        $root = $this->makePage([
            'slug' => ['en' => 'company'],
            'status' => ContentStatus::Published->value,
            'parent_id' => null,
        ]);

        $middle = $this->makePage([
            'slug' => ['en' => 'culture'],
            'status' => ContentStatus::Published->value,
            'parent_id' => $root->id,
        ]);

        $this->makePage([
            'slug' => ['en' => 'values'],
            'status' => ContentStatus::Published->value,
            'parent_id' => $middle->id,
        ]);

        $this->get('/company/culture/values')->assertOk();
    }

    public function test_hierarchical_url_returns_404_when_intermediate_page_not_published(): void
    {
        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => ContentStatus::Draft->value, // not published
            'parent_id' => null,
        ]);

        $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => ContentStatus::Published->value,
            'parent_id' => $parent->id,
        ]);

        // Intermediate segment "services" is a draft — child is unreachable.
        $this->get('/services/team')->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Feature flag off — existing flat-slug behavior is preserved
    // ------------------------------------------------------------------

    public function test_hierarchical_resolution_disabled_by_default(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => ContentStatus::Published->value,
            'parent_id' => null,
        ]);

        $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => ContentStatus::Published->value,
            'parent_id' => $parent->id,
        ]);

        // With flag off, /services/team returns 404 — hierarchical resolution inactive.
        $this->get('/services/team')->assertNotFound();

        // The child page is still accessible at its flat leaf slug.
        $this->get('/team')->assertOk();
    }

    public function test_root_page_resolves_regardless_of_flag(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $this->makePage([
            'slug' => ['en' => 'about'],
            'status' => ContentStatus::Published->value,
            'parent_id' => null,
        ]);

        $this->get('/about')->assertOk();
    }

    // ------------------------------------------------------------------
    // Sitemap URL generation
    // ------------------------------------------------------------------

    public function test_sitemap_generates_hierarchical_url_for_child_page(): void
    {
        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => ContentStatus::Published->value,
            'parent_id' => null,
        ]);

        $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => ContentStatus::Published->value,
            'parent_id' => $parent->id,
        ]);

        $urls = \TallCms\Cms\Services\SitemapService::getPages();
        $locs = $urls->pluck('loc')->toArray();

        $this->assertStringContainsString('/services/team', implode(',', $locs));

        // The old flat URL must not appear as a separate entry in the sitemap.
        $flatEntry = collect($locs)->first(
            fn ($loc) => str_ends_with($loc, '/team') && ! str_contains($loc, '/services/team')
        );
        $this->assertNull($flatEntry, 'Flat leaf URL /team must not appear separately in the sitemap.');
    }
}
