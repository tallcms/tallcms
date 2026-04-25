<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use TallCms\Cms\Livewire\CmsPageRenderer;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Tests\TestCase;

/**
 * Resolver-level tests for PR #59 hierarchical page URLs.
 *
 * These exercise CmsPageRenderer::resolveNestedSlug() directly via
 * reflection instead of through the full HTTP / Livewire / routes stack.
 * Reasons:
 *   - Orchestra Testbench (the package test runner) doesn't ship the
 *     standalone routes / Livewire bindings, so $this->get() doesn't
 *     reach the page renderer here.
 *   - The contract we care about is what the resolver returns and which
 *     properties it sets on the component — not the surrounding HTTP plumbing.
 *
 * The original PR shipped equivalent tests at tests/Feature/ (repo root),
 * which are not picked up by package CI (.github/workflows/package-tests.yml
 * only runs packages/tallcms/cms/**). This file is the CI-runnable version.
 */
class HierarchicalPageResolverTest extends TestCase
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
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_homepage')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('content_width')->default('standard');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tallcms_posts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->json('excerpt')->nullable();
            $table->json('content')->nullable();
            $table->text('search_content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // CmsPost eager-loads `categories` when resolved as the URL leaf,
        // so the categories + pivot tables must exist even when no rows.
        Schema::create('tallcms_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tallcms_post_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('category_id');
        });

        // CmsPage / CmsPost both use HasRevisions; mirrors HasRevisionsRestoreTest.
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

    // ----------------------------------------------------------------------
    // Hierarchical resolution (flag on)
    // ----------------------------------------------------------------------

    public function test_child_page_resolves_at_full_hierarchical_path_when_flag_on(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', true);

        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $child = CmsPage::create([
            'title' => ['en' => 'Team'],
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $component = new CmsPageRenderer;

        $this->assertTrue($this->invokeResolver($component, 'services/team'));
        $this->assertSame($child->id, $component->page->id);
    }

    public function test_three_level_hierarchical_path_resolves_when_flag_on(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', true);

        $root = CmsPage::create([
            'title' => ['en' => 'Company'],
            'slug' => ['en' => 'company'],
            'status' => 'published',
        ]);

        $middle = CmsPage::create([
            'title' => ['en' => 'Culture'],
            'slug' => ['en' => 'culture'],
            'status' => 'published',
            'parent_id' => $root->id,
        ]);

        $leaf = CmsPage::create([
            'title' => ['en' => 'Values'],
            'slug' => ['en' => 'values'],
            'status' => 'published',
            'parent_id' => $middle->id,
        ]);

        $component = new CmsPageRenderer;

        $this->assertTrue($this->invokeResolver($component, 'company/culture/values'));
        $this->assertSame($leaf->id, $component->page->id);
    }

    public function test_intermediate_unpublished_segment_blocks_hierarchical_resolution(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', true);

        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
            'status' => 'draft', // not published
        ]);

        CmsPage::create([
            'title' => ['en' => 'Team'],
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $component = new CmsPageRenderer;

        $this->assertFalse($this->invokeResolver($component, 'services/team'),
            'A draft intermediate page must make its subtree unreachable. '
            .'See CmsPageRenderer::walkHierarchicalSegments() docblock.');
    }

    // ----------------------------------------------------------------------
    // Legacy resolution (flag off — pre-PR-59 behavior)
    // ----------------------------------------------------------------------

    public function test_post_under_page_with_postsblock_resolves_when_flag_off(): void
    {
        // The regression this PR's earlier gate introduced: with hierarchical_urls
        // off (the default), the resolver was skipped entirely, which broke the
        // pre-existing /parent_page/post_slug pattern. Locking it in with a test.
        Config::set('tallcms.pages.hierarchical_urls', false);

        $blog = CmsPage::create([
            'title' => ['en' => 'Blog'],
            'slug' => ['en' => 'blog'],
            'status' => 'published',
            'content' => '{"blocks":[{"data":{"id":"posts"}}]}',
        ]);

        $post = CmsPost::create([
            'title' => ['en' => 'Hello world'],
            'slug' => ['en' => 'hello-world'],
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $component = new CmsPageRenderer;

        $this->assertTrue($this->invokeResolver($component, 'blog/hello-world'),
            'A post under a page that has a PostsBlock must still resolve when '
            .'hierarchical_urls is off — pre-PR behavior, load-bearing for '
            .'existing installs upgrading without flipping the flag.');
        $this->assertSame($blog->id, $component->page->id);
        $this->assertSame($post->id, $component->post->id);
        $this->assertSame('POST_DETAIL', $component->renderedContent);
    }

    public function test_hierarchical_path_does_not_resolve_when_flag_off(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $parent = CmsPage::create([
            'title' => ['en' => 'Services'],
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        CmsPage::create([
            'title' => ['en' => 'Team'],
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $component = new CmsPageRenderer;

        // Without the flag, the multi-segment chain walk doesn't run.
        // /services/team should not resolve as a hierarchical page.
        $this->assertFalse($this->invokeResolver($component, 'services/team'));
    }

    public function test_literal_nested_slug_still_resolves_when_flag_off(): void
    {
        // Pre-PR behavior also had a fallback: a page whose stored slug
        // literally contained "/" (e.g. an admin who set slug to "old/legacy").
        // Preserved in the legacy resolver path.
        Config::set('tallcms.pages.hierarchical_urls', false);

        $page = CmsPage::create([
            'title' => ['en' => 'Old Legacy'],
            'slug' => ['en' => 'old/legacy'],
            'status' => 'published',
        ]);

        $component = new CmsPageRenderer;

        $this->assertTrue($this->invokeResolver($component, 'old/legacy'));
        $this->assertSame($page->id, $component->page->id);
    }

    // ----------------------------------------------------------------------
    // Helper: invoke the protected resolver via reflection
    // ----------------------------------------------------------------------

    protected function invokeResolver(CmsPageRenderer $component, string $slug): bool
    {
        $method = new ReflectionMethod($component, 'resolveNestedSlug');
        $method->setAccessible(true);

        return (bool) $method->invoke($component, $slug);
    }
}
