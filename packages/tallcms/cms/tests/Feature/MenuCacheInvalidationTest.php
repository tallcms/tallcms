<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\TallcmsMenu;
use TallCms\Cms\Models\TallcmsMenuItem;
use TallCms\Cms\Tests\TestCase;

class MenuCacheInvalidationTest extends TestCase
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
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
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

        Schema::create('tallcms_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tallcms_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('tallcms_menus')->cascadeOnDelete();
            $table->json('label');
            $table->enum('type', ['page', 'external', 'custom', 'separator', 'header']);
            $table->foreignId('page_id')->nullable()->constrained('tallcms_pages')->cascadeOnDelete();
            $table->text('url')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->nestedSet();
            $table->timestamps();
        });

        Schema::create('tallcms_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::setDefaultDriver('array');
        Cache::flush();
        Config::set('tallcms.pages.hierarchical_urls', true);
        app()->setLocale('en');
    }

    public function test_page_slug_changes_flush_cached_resolved_menu_urls_for_descendants(): void
    {
        $parent = $this->createPage('Services', 'services');
        $child = $this->createPage('Team', 'team', ['parent_id' => $parent->id]);
        $this->createMenuForPage($child);

        $this->assertSame('/services/team', $this->headerMenuUrl());

        $parent->update(['slug' => ['en' => 'company']]);
        $this->forgetResolvedMenusForRequest();

        $this->assertSame('/company/team', $this->headerMenuUrl());
    }

    public function test_page_parent_changes_flush_cached_resolved_menu_urls(): void
    {
        $firstParent = $this->createPage('Services', 'services');
        $secondParent = $this->createPage('Company', 'company');
        $child = $this->createPage('Team', 'team', ['parent_id' => $firstParent->id]);
        $this->createMenuForPage($child);

        $this->assertSame('/services/team', $this->headerMenuUrl());

        $child->update(['parent_id' => $secondParent->id]);
        $this->forgetResolvedMenusForRequest();

        $this->assertSame('/company/team', $this->headerMenuUrl());
    }

    public function test_menu_helper_reuses_cached_tree_without_requerying_database(): void
    {
        $this->createMenuForExternalUrl('/docs');

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $firstMenu = menu('header');
        $queryCountAfterFirstCall = count($queries);

        $this->forgetResolvedMenusForRequest();

        $secondMenu = menu('header');

        $this->assertNotNull($firstMenu);
        $this->assertSame($firstMenu, $secondMenu);
        $this->assertGreaterThan(0, $queryCountAfterFirstCall);
        $this->assertCount($queryCountAfterFirstCall, $queries);
        $this->assertSame(1, $this->countQueriesForTable($queries, 'tallcms_menus'));
    }

    public function test_page_homepage_state_changes_flush_cached_resolved_menu_urls(): void
    {
        $page = $this->createPage('About', 'about');
        $this->createMenuForPage($page);

        $this->assertSame('/about', $this->headerMenuUrl());

        $page->update(['is_homepage' => true]);
        $this->forgetResolvedMenusForRequest();

        $this->assertSame('/', $this->headerMenuUrl());
    }

    public function test_site_type_changes_flush_cached_resolved_menu_urls(): void
    {
        SiteSetting::setGlobal('site_type', 'multi-page');
        $page = $this->createPage('About', 'about');
        $this->createMenuForPage($page);

        $this->assertSame('/about', $this->headerMenuUrl());

        SiteSetting::setGlobal('site_type', 'single-page');
        $this->forgetResolvedMenusForRequest();

        $this->assertSame('#about-'.$page->id, $this->headerMenuUrl());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPage(string $title, string $slug, array $attributes = []): CmsPage
    {
        return CmsPage::create(array_merge([
            'title' => ['en' => $title],
            'slug' => ['en' => $slug],
            'status' => 'published',
        ], $attributes));
    }

    private function createMenuForExternalUrl(string $url): void
    {
        $menu = TallcmsMenu::create([
            'name' => 'Header',
            'location' => 'header',
            'is_active' => true,
        ]);

        TallcmsMenuItem::create([
            'menu_id' => $menu->id,
            'label' => ['en' => 'Docs'],
            'type' => 'external',
            'url' => $url,
            'is_active' => true,
        ]);
    }

    private function createMenuForPage(CmsPage $page): void
    {
        $menu = TallcmsMenu::create([
            'name' => 'Header',
            'location' => 'header',
            'is_active' => true,
        ]);

        TallcmsMenuItem::create([
            'menu_id' => $menu->id,
            'label' => ['en' => $page->title],
            'type' => 'page',
            'page_id' => $page->id,
            'is_active' => true,
        ]);
    }

    private function headerMenuUrl(): ?string
    {
        return menu('header')[0]['url'] ?? null;
    }

    private function forgetResolvedMenusForRequest(): void
    {
        request()->attributes->remove('tallcms.resolved_menus');
    }

    /**
     * @param  array<int, string>  $queries
     */
    private function countQueriesForTable(array $queries, string $table): int
    {
        return collect($queries)
            ->filter(fn (string $query): bool => str_contains($query, $table))
            ->count();
    }
}
