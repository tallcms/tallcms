<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use ReflectionMethod;
use TallCms\Cms\Livewire\CmsPageRenderer;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\MergeTagService;
use TallCms\Cms\Services\SeoService;
use TallCms\Cms\Tests\TestCase;

/**
 * Coverage for the URL-building surfaces that bypass getFullSlug() unless
 * explicitly fixed. Without these tests, with hierarchical_urls flipped on:
 *   - SEO post canonicals/sitemap URLs would still use the leaf blog slug
 *   - Search result page links would still use the leaf slug
 *   - Merge tag {{ page_url }} would still expand to the leaf URL
 *   - Posts block / link blocks rendered inside a page would build URLs
 *     against the leaf slug shared into View as `cmsPageSlug`
 *
 * Each surface needs to honor the flag for the rollout to be coherent.
 *
 * Note on caching: SeoService::getBlogParentSlug() memoizes its return
 * inside a closure-static keyed by site_id. Tests use a unique site_id
 * per scenario to avoid cross-test cache pollution.
 */
class HierarchicalUrlSurfacesTest extends TestCase
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
            $table->unsignedBigInteger('site_id')->nullable();
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
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

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
    // SeoService — post canonical / sitemap URL
    // ----------------------------------------------------------------------

    public function test_post_url_includes_full_hierarchical_blog_path_when_flag_on(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', true);

        $services = $this->makePage([
            'site_id' => 1001,
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $blog = $this->makePage([
            'site_id' => 1001,
            'slug' => ['en' => 'blog'],
            'status' => 'published',
            'parent_id' => $services->id,
            'content' => '{"blocks":[{"data":{"id":"posts"}}]}',
        ]);

        $post = $this->makePost([
            'site_id' => 1001,
            'slug' => ['en' => 'hello-world'],
            'status' => 'published',
        ]);

        $url = SeoService::getPostUrl($post, $services->site_id);

        $this->assertStringContainsString('/services/blog/hello-world', $url,
            'With hierarchical_urls on, post canonical/sitemap URLs must be built '
            .'against the full ancestor path of the page that owns the PostsBlock.');
        $this->assertStringNotContainsString('//blog/hello-world', $url);
    }

    public function test_post_url_falls_back_to_leaf_blog_slug_when_flag_off(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $services = $this->makePage([
            'site_id' => 1002,
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $this->makePage([
            'site_id' => 1002,
            'slug' => ['en' => 'blog'],
            'status' => 'published',
            'parent_id' => $services->id,
            'content' => '{"blocks":[{"data":{"id":"posts"}}]}',
        ]);

        $post = $this->makePost([
            'site_id' => 1002,
            'slug' => ['en' => 'hello-world'],
            'status' => 'published',
        ]);

        $url = SeoService::getPostUrl($post, 1002);

        $this->assertStringContainsString('/blog/hello-world', $url,
            'With hierarchical_urls off, post URLs preserve pre-PR behavior — '
            .'leaf blog slug, no ancestor prefix.');
        $this->assertStringNotContainsString('/services/blog/', $url);
    }

    // ----------------------------------------------------------------------
    // MergeTagService — {{ page_url }} merge tag
    // ----------------------------------------------------------------------

    public function test_merge_tag_page_url_uses_full_path_when_flag_on(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', true);

        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $child = $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $tags = $this->invokeProtected(MergeTagService::class, 'getPageTags', [$child]);

        $this->assertStringEndsWith('/services/team', $tags['page_url'],
            'Merge tag {{ page_url }} must reflect the full hierarchical path '
            .'so emails/templates link to the canonical URL.');
    }

    public function test_merge_tag_page_url_uses_leaf_when_flag_off(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $child = $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $tags = $this->invokeProtected(MergeTagService::class, 'getPageTags', [$child]);

        $this->assertStringEndsWith('/team', $tags['page_url']);
        $this->assertStringNotContainsString('/services/team', $tags['page_url']);
    }

    // ----------------------------------------------------------------------
    // CmsPageRenderer — cmsPageSlug shared with views
    // ----------------------------------------------------------------------

    public function test_renderPageContent_shares_full_hierarchical_slug_when_flag_on(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', true);

        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $child = $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
            // Leave content null so renderRichContentUnsafe short-circuits.
        ]);

        $component = new CmsPageRenderer;
        $component->page = $child;

        $method = new ReflectionMethod($component, 'renderPageContent');
        $method->setAccessible(true);
        $method->invoke($component);

        $this->assertSame('services/team', View::shared('cmsPageSlug'),
            'Posts and link blocks rendered inside a page build URLs against '
            .'cmsPageSlug; with the flag on it must be the full hierarchical '
            .'path so child-page rendering produces /parent/child/post URLs.');
    }

    public function test_renderPageContent_shares_leaf_slug_when_flag_off(): void
    {
        Config::set('tallcms.pages.hierarchical_urls', false);

        $parent = $this->makePage([
            'slug' => ['en' => 'services'],
            'status' => 'published',
        ]);

        $child = $this->makePage([
            'slug' => ['en' => 'team'],
            'status' => 'published',
            'parent_id' => $parent->id,
        ]);

        $component = new CmsPageRenderer;
        $component->page = $child;

        $method = new ReflectionMethod($component, 'renderPageContent');
        $method->setAccessible(true);
        $method->invoke($component);

        $this->assertSame('team', View::shared('cmsPageSlug'));
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    protected function makePage(array $attrs = []): CmsPage
    {
        return CmsPage::create(array_merge([
            'title' => ['en' => 'Untitled'],
            'slug' => ['en' => 'untitled'],
            'status' => 'draft',
        ], $attrs));
    }

    protected function makePost(array $attrs = []): CmsPost
    {
        return CmsPost::create(array_merge([
            'title' => ['en' => 'Untitled'],
            'slug' => ['en' => 'untitled'],
            'status' => 'draft',
            'published_at' => now()->subDay(),
        ], $attrs));
    }

    protected function invokeProtected(string $class, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($class, $method);
        $ref->setAccessible(true);

        return $ref->invoke(null, ...$args);
    }
}
