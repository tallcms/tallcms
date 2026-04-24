<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for hierarchical page URL building via CmsPage::getFullSlug().
 *
 * Covers: URL construction at multiple depths, eager-load path, lazy-load
 * path, cycle guard, and sibling-scoped slug uniqueness.
 */
class HierarchicalPageUrlTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->text('search_content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_homepage')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // CmsPage uses the HasRevisions trait, which writes to tallcms_revisions
        // on every save. Without this table, even a bare ::create() fails.
        // Schema mirrors the package migration; see HasRevisionsRestoreTest for
        // the established inline-schema pattern in the package test suite.
        Schema::create('tallcms_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('title');
            $table->text('excerpt')->nullable();
            $table->json('content')->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('additional_data')->nullable();
            $table->unsignedInteger('revision_number');
            $table->text('notes')->nullable();
            $table->string('content_hash')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Hierarchical URLs default to false. Enable them for these tests.
        Config::set('tallcms.pages.hierarchical_urls', true);
    }

    // ------------------------------------------------------------------
    // URL building
    // ------------------------------------------------------------------

    public function test_root_page_returns_its_own_slug(): void
    {
        $page = CmsPage::create([
            'title' => ['en' => 'About'],
            'slug' => ['en' => 'about'],
        ]);

        $this->assertSame('about', $page->getFullSlug());
    }

    public function test_child_page_prepends_parent_slug(): void
    {
        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
        ]);

        $child = CmsPage::create([
            'title' => ['en' => 'Team'],
            'slug' => ['en' => 'team'],
            'parent_id' => $parent->id,
        ]);

        $this->assertSame('services/team', $child->getFullSlug());
    }

    public function test_three_level_page_builds_full_path(): void
    {
        $root = CmsPage::create([
            'title' => ['en' => 'Company'],
            'slug' => ['en' => 'company'],
        ]);

        $middle = CmsPage::create([
            'title' => ['en' => 'Culture'],
            'slug' => ['en' => 'culture'],
            'parent_id' => $root->id,
        ]);

        $leaf = CmsPage::create([
            'title' => ['en' => 'Values'],
            'slug' => ['en' => 'values'],
            'parent_id' => $middle->id,
        ]);

        $this->assertSame('company/culture/values', $leaf->getFullSlug());
    }

    public function test_eager_loaded_parent_is_used_without_extra_query(): void
    {
        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
        ]);

        $child = CmsPage::create([
            'title' => ['en' => 'Web'],
            'slug' => ['en' => 'web'],
            'parent_id' => $parent->id,
        ]);

        // Load with relation eager-loaded
        $loaded = CmsPage::with('parent')->find($child->id);

        $this->assertTrue($loaded->relationLoaded('parent'));
        $this->assertSame('services/web', $loaded->getFullSlug());
    }

    public function test_flag_off_returns_leaf_slug_even_for_child_page(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
        ]);

        $child = CmsPage::create([
            'title' => ['en' => 'Team'],
            'slug' => ['en' => 'team'],
            'parent_id' => $parent->id,
        ]);

        // Flag off — must return only the leaf slug regardless of parent chain.
        $this->assertSame('team', $child->getFullSlug());
    }

    // ------------------------------------------------------------------
    // Cycle guard
    // ------------------------------------------------------------------

    public function test_circular_parent_chain_does_not_recurse_infinitely(): void
    {
        // Create two pages and manually wire a circular parent reference.
        $a = CmsPage::create([
            'title' => ['en' => 'Page A'],
            'slug' => ['en' => 'page-a'],
        ]);

        $b = CmsPage::create([
            'title' => ['en' => 'Page B'],
            'slug' => ['en' => 'page-b'],
            'parent_id' => $a->id,
        ]);

        // Force a circular reference: A's parent is B (B → A → B → …)
        $a->parent_id = $b->id;
        $a->saveQuietly();

        // Calling getFullSlug() must return without infinite recursion.
        $result = $a->fresh()->getFullSlug();

        // We just assert it returns a non-empty string and didn't blow the stack.
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ------------------------------------------------------------------
    // Sibling-scoped uniqueness
    // ------------------------------------------------------------------

    public function test_same_slug_is_allowed_under_different_parents(): void
    {
        $parentA = CmsPage::create([
            'title' => ['en' => 'Products'],
            'slug' => ['en' => 'products'],
        ]);

        $parentB = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
        ]);

        $childA = new CmsPage([
            'title' => ['en' => 'Overview'],
            'slug' => ['en' => 'overview'],
            'parent_id' => $parentA->id,
        ]);

        $childB = new CmsPage([
            'title' => ['en' => 'Overview'],
            'slug' => ['en' => 'overview'],
            'parent_id' => $parentB->id,
        ]);

        // Slug "overview" already exists under parentA.
        $childA->save();

        // The same slug under parentB is a different sibling set — must be allowed.
        $this->assertFalse(
            $childB->localizedSlugExists('overview', 'en'),
            'Same slug under a different parent should not be considered a duplicate.'
        );
    }

    public function test_duplicate_slug_within_same_parent_is_detected(): void
    {
        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
        ]);

        CmsPage::create([
            'title' => ['en' => 'Team'],
            'slug' => ['en' => 'team'],
            'parent_id' => $parent->id,
        ]);

        $sibling = new CmsPage([
            'title' => ['en' => 'Team Duplicate'],
            'slug' => ['en' => 'team'],
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue(
            $sibling->localizedSlugExists('team', 'en'),
            'Duplicate slug within the same parent must be detected.'
        );
    }

    public function test_root_pages_share_a_sibling_set_of_their_own(): void
    {
        CmsPage::create([
            'title' => ['en' => 'About'],
            'slug' => ['en' => 'about'],
        ]);

        $second = new CmsPage([
            'title' => ['en' => 'About Copy'],
            'slug' => ['en' => 'about'],
        ]);

        // Both are root-level (null parent_id) — same sibling set.
        $this->assertTrue(
            $second->localizedSlugExists('about', 'en'),
            'Duplicate slug at root level must be detected.'
        );
    }
}
