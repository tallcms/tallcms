<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\MediaCollection;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Rules\SiteAwareUnique;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\SiteCloneService;
use Tests\TestCase;

class SiteContentScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Site $siteA;

    protected Site $siteB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->siteA = $this->createSite('site-a.test');
        $this->siteB = $this->createSite('site-b.test');
    }

    protected function createSite(string $domain): Site
    {
        return Site::create([
            'name' => ucfirst(str_replace('.test', '', $domain)),
            'domain' => $domain,
            'is_default' => false,
            'is_active' => true,
            'user_id' => $this->superAdmin->id,
        ]);
    }

    protected function insertPost(int $siteId, string $title, int $authorId): int
    {
        return DB::table('tallcms_posts')->insertGetId([
            'site_id' => $siteId,
            'title' => json_encode(['en' => $title]),
            'slug' => json_encode(['en' => Str::slug($title)]),
            'status' => 'published',
            'author_id' => $authorId,
            'is_featured' => false,
            'views' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertCategory(int $siteId, string $name): int
    {
        return DB::table('tallcms_categories')->insertGetId([
            'site_id' => $siteId,
            'name' => json_encode(['en' => $name]),
            'slug' => json_encode(['en' => Str::slug($name)]),
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertMedia(int $siteId, string $name = 'photo.jpg'): int
    {
        return DB::table('tallcms_media')->insertGetId([
            'site_id' => $siteId,
            'name' => $name,
            'file_name' => $name,
            'mime_type' => 'image/jpeg',
            'path' => "media/{$name}",
            'disk' => 'public',
            'size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertCollection(int $siteId, string $name): int
    {
        return DB::table('tallcms_media_collections')->insertGetId([
            'site_id' => $siteId,
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => '#6366f1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------
    // User-owned content (posts, categories, media, collections)
    // These are NOT site-scoped — they're user libraries.
    // Filtering happens at the Filament resource level, not via global scopes.
    // -------------------------------------------------------

    public function test_posts_are_user_owned_not_site_scoped(): void
    {
        $postA = $this->insertPost($this->siteA->id, 'Post A', $this->superAdmin->id);
        $postB = $this->insertPost($this->siteB->id, 'Post B', $this->superAdmin->id);

        // Posts are visible regardless of site context (no SiteScope)
        $posts = CmsPost::withTrashed()->pluck('id')->all();
        $this->assertContains($postA, $posts);
        $this->assertContains($postB, $posts);
    }

    public function test_media_is_user_owned_not_site_scoped(): void
    {
        $mediaA = $this->insertMedia($this->siteA->id, 'a.jpg');
        $mediaB = $this->insertMedia($this->siteB->id, 'b.jpg');

        // Media is visible regardless of site context
        $media = TallcmsMedia::pluck('id')->all();
        $this->assertContains($mediaA, $media);
        $this->assertContains($mediaB, $media);
    }

    // -------------------------------------------------------
    // User-owned creation: auto-assigns user_id
    // -------------------------------------------------------

    public function test_post_auto_assigns_user_id(): void
    {
        $this->actingAs($this->superAdmin);

        $post = CmsPost::create([
            'title' => 'Owned Post',
            'slug' => 'owned-post',
            'status' => 'draft',
            'author_id' => $this->superAdmin->id,
        ]);

        $this->assertEquals($this->superAdmin->id, $post->user_id);
    }

    public function test_category_auto_assigns_user_id(): void
    {
        $this->actingAs($this->superAdmin);

        $category = CmsCategory::create([
            'name' => 'Owned Category',
            'slug' => 'owned-cat',
        ]);

        $this->assertEquals($this->superAdmin->id, $category->user_id);
    }

    public function test_media_auto_assigns_user_id(): void
    {
        $this->actingAs($this->superAdmin);

        $media = TallcmsMedia::create([
            'name' => 'owned.jpg',
            'file_name' => 'owned.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/owned.jpg',
            'disk' => 'public',
            'size' => 1024,
        ]);

        $this->assertEquals($this->superAdmin->id, $media->user_id);
    }

    // -------------------------------------------------------
    // Clone integrity
    // -------------------------------------------------------

    public function test_clone_copies_posts_with_category_pivot(): void
    {
        $catId = $this->insertCategory($this->siteA->id, 'Tech');
        $postId = $this->insertPost($this->siteA->id, 'Hello World', $this->superAdmin->id);

        DB::table('tallcms_post_category')->insert([
            'post_id' => $postId,
            'category_id' => $catId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone.test');

        // Posts were cloned
        $clonedPosts = DB::table('tallcms_posts')->where('site_id', $cloned->id)->get();
        $this->assertCount(1, $clonedPosts);
        $this->assertNotEquals($postId, $clonedPosts->first()->id);

        // Categories were cloned
        $clonedCats = DB::table('tallcms_categories')->where('site_id', $cloned->id)->get();
        $this->assertCount(1, $clonedCats);

        // Pivot was remapped
        $clonedPivot = DB::table('tallcms_post_category')
            ->where('post_id', $clonedPosts->first()->id)
            ->first();
        $this->assertNotNull($clonedPivot);
        $this->assertEquals($clonedCats->first()->id, $clonedPivot->category_id);
    }

    public function test_clone_copies_categories_with_hierarchy(): void
    {
        $parentId = $this->insertCategory($this->siteA->id, 'Parent');
        $childId = DB::table('tallcms_categories')->insertGetId([
            'site_id' => $this->siteA->id,
            'name' => json_encode(['en' => 'Child']),
            'slug' => json_encode(['en' => 'child']),
            'parent_id' => $parentId,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone-hierarchy.test');

        $clonedCats = DB::table('tallcms_categories')
            ->where('site_id', $cloned->id)
            ->get();

        $this->assertCount(2, $clonedCats);

        $clonedChild = $clonedCats->where('parent_id', '!=', null)->first();
        $clonedParent = $clonedCats->where('parent_id', null)->first();

        $this->assertNotNull($clonedChild);
        $this->assertEquals($clonedParent->id, $clonedChild->parent_id);
    }

    public function test_clone_duplicates_media_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/original.jpg', 'fake image content');

        $mediaId = DB::table('tallcms_media')->insertGetId([
            'site_id' => $this->siteA->id,
            'name' => 'original.jpg',
            'file_name' => 'original.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/original.jpg',
            'disk' => 'public',
            'size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone-media.test');

        $clonedMedia = DB::table('tallcms_media')->where('site_id', $cloned->id)->first();

        $this->assertNotNull($clonedMedia);
        $this->assertNotEquals('media/original.jpg', $clonedMedia->path, 'Cloned media should have a different path');
        Storage::disk('public')->assertExists('media/original.jpg');
        Storage::disk('public')->assertExists($clonedMedia->path);
    }

    public function test_clone_copies_media_collections_with_pivot(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/gallery-photo.jpg', 'gallery content');

        $mediaId = DB::table('tallcms_media')->insertGetId([
            'site_id' => $this->siteA->id,
            'name' => 'gallery-photo.jpg',
            'file_name' => 'gallery-photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/gallery-photo.jpg',
            'disk' => 'public',
            'size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $colId = $this->insertCollection($this->siteA->id, 'Gallery');

        DB::table('tallcms_media_collection_pivot')->insert([
            'media_id' => $mediaId,
            'collection_id' => $colId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone-collections.test');

        $clonedCol = DB::table('tallcms_media_collections')->where('site_id', $cloned->id)->first();
        $this->assertNotNull($clonedCol);
        $this->assertEquals('Gallery', $clonedCol->name);

        $clonedMedia = DB::table('tallcms_media')->where('site_id', $cloned->id)->first();
        $clonedPivot = DB::table('tallcms_media_collection_pivot')
            ->where('media_id', $clonedMedia->id)
            ->where('collection_id', $clonedCol->id)
            ->first();
        $this->assertNotNull($clonedPivot);
    }

    public function test_cloned_post_search_index_rebuilt(): void
    {
        $this->insertPost($this->siteA->id, 'Searchable Post', $this->superAdmin->id);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone-search.test');

        // Verify cloned posts exist (reindexing runs without error)
        $clonedPosts = DB::table('tallcms_posts')->where('site_id', $cloned->id)->count();
        $this->assertEquals(1, $clonedPosts);
    }

    // -------------------------------------------------------
    // Safe delete after clone
    // -------------------------------------------------------

    public function test_delete_cloned_media_does_not_affect_original(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/shared-test.jpg', 'content');

        $originalId = DB::table('tallcms_media')->insertGetId([
            'site_id' => $this->siteA->id,
            'name' => 'shared-test.jpg',
            'file_name' => 'shared-test.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/shared-test.jpg',
            'disk' => 'public',
            'size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone-delete.test');

        $clonedMedia = TallcmsMedia::withoutGlobalScopes()
            ->where('site_id', $cloned->id)
            ->first();

        // Delete the clone
        $clonedMedia->delete();

        // Original file still exists
        Storage::disk('public')->assertExists('media/shared-test.jpg');

        // Original record still exists
        $this->assertDatabaseHas('tallcms_media', ['id' => $originalId]);
    }

    public function test_delete_original_media_does_not_affect_clone(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/reverse-test.jpg', 'content');

        DB::table('tallcms_media')->insert([
            'site_id' => $this->siteA->id,
            'name' => 'reverse-test.jpg',
            'file_name' => 'reverse-test.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'media/reverse-test.jpg',
            'disk' => 'public',
            'size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);
        $cloned = app(SiteCloneService::class)->clone($this->siteA, 'Clone', 'clone-reverse.test');

        $clonedMedia = DB::table('tallcms_media')->where('site_id', $cloned->id)->first();

        // Delete the original
        $original = TallcmsMedia::withoutGlobalScopes()
            ->where('site_id', $this->siteA->id)
            ->where('name', 'reverse-test.jpg')
            ->first();
        $original->delete();

        // Cloned file still exists
        Storage::disk('public')->assertExists($clonedMedia->path);

        // Cloned record still exists
        $this->assertDatabaseHas('tallcms_media', ['id' => $clonedMedia->id]);
    }

    // -------------------------------------------------------
    // Site-aware uniqueness
    // -------------------------------------------------------

    public function test_two_sites_can_have_same_category_slug(): void
    {
        $this->insertCategory($this->siteA->id, 'News');

        // This should not conflict
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['slug' => json_encode(['en' => 'news'])],
            ['slug' => [SiteAwareUnique::rule('tallcms_categories', 'slug')]]
        );

        // When validating for site B, "news" should be allowed
        session(['multisite_admin_site_id' => $this->siteB->id]);
        $validator2 = \Illuminate\Support\Facades\Validator::make(
            ['slug' => json_encode(['en' => 'news'])],
            ['slug' => [SiteAwareUnique::rule('tallcms_categories', 'slug')]]
        );
        $this->assertFalse($validator2->fails());
    }

    public function test_two_sites_can_have_same_collection_name(): void
    {
        $this->insertCollection($this->siteA->id, 'Gallery');

        session(['multisite_admin_site_id' => $this->siteB->id]);
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['name' => 'Gallery'],
            ['name' => [SiteAwareUnique::rule('tallcms_media_collections', 'name')]]
        );
        $this->assertFalse($validator->fails());
    }

    public function test_same_site_duplicate_slug_rejected(): void
    {
        $this->insertCollection($this->siteA->id, 'Gallery');

        session(['multisite_admin_site_id' => $this->siteA->id]);
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['name' => 'Gallery'],
            ['name' => [SiteAwareUnique::rule('tallcms_media_collections', 'name')]]
        );
        $this->assertTrue($validator->fails());
    }

    public function test_inline_category_creation_is_site_scoped(): void
    {
        // Category with slug "news" on site A
        $this->insertCategory($this->siteA->id, 'News');

        // Creating "news" on site B should succeed
        session(['multisite_admin_site_id' => $this->siteB->id]);
        $rule = SiteAwareUnique::rule('tallcms_categories', 'slug');
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['slug' => json_encode(['en' => 'news'])],
            ['slug' => [$rule]]
        );
        $this->assertFalse($validator->fails());
    }

    public function test_inline_collection_creation_is_site_scoped(): void
    {
        $this->insertCollection($this->siteA->id, 'Portfolio');

        session(['multisite_admin_site_id' => $this->siteB->id]);
        $rule = SiteAwareUnique::rule('tallcms_media_collections', 'name');
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['name' => 'Portfolio'],
            ['name' => [$rule]]
        );
        $this->assertFalse($validator->fails());
    }

    // -------------------------------------------------------
    // Comments scoped by site
    // -------------------------------------------------------

    // -------------------------------------------------------
    // Admin queries are unfiltered
    //
    // In the new model, admin uses explicit ownership via the Site resource
    // and its RelationManagers. SiteScope is frontend-only — it never filters
    // admin queries. Previously, ambient session-based scoping limited what
    // admin queries returned; those tests have been removed because the
    // behavior they covered no longer exists.
    //
    // Frontend scoping (domain → site) continues to be enforced by the
    // resolver + scope and is covered by higher-level HTTP tests that hit
    // a real domain (see frontend route tests).
    // -------------------------------------------------------

    public function test_admin_queries_return_rows_from_all_sites(): void
    {
        $this->actingAs($this->superAdmin);

        $postA = $this->insertPost($this->siteA->id, 'Post A', $this->superAdmin->id);
        $postB = $this->insertPost($this->siteB->id, 'Post B', $this->superAdmin->id);

        $commentA = DB::table('tallcms_comments')->insertGetId([
            'site_id' => $this->siteA->id,
            'post_id' => $postA,
            'content' => 'Comment on A',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $commentB = DB::table('tallcms_comments')->insertGetId([
            'site_id' => $this->siteB->id,
            'post_id' => $postB,
            'content' => 'Comment on B',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $comments = \TallCms\Cms\Models\CmsComment::pluck('id')->all();

        $this->assertContains($commentA, $comments);
        $this->assertContains($commentB, $comments);
    }

    public function test_preview_token_derives_site_id_from_parent(): void
    {
        $this->actingAs($this->superAdmin);

        // Create a page on site A
        $pageId = DB::table('tallcms_pages')->insertGetId([
            'site_id' => $this->siteA->id,
            'title' => json_encode(['en' => 'Token Test Page']),
            'slug' => json_encode(['en' => 'token-test']),
            'status' => 'draft',
            'is_homepage' => false,
            'sort_order' => 0,
            'author_id' => $this->superAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = \TallCms\Cms\Models\CmsPreviewToken::create([
            'token' => Str::random(64),
            'tokenable_type' => 'TallCms\Cms\Models\CmsPage',
            'tokenable_id' => $pageId,
            'created_by' => $this->superAdmin->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertEquals($this->siteA->id, $token->site_id);
    }

    // -------------------------------------------------------
    // Comment site_id derived from post
    // -------------------------------------------------------

    public function test_comment_derives_site_id_from_post(): void
    {
        $this->actingAs($this->superAdmin);

        $postId = $this->insertPost($this->siteA->id, 'Parent Post', $this->superAdmin->id);

        $comment = \TallCms\Cms\Models\CmsComment::create([
            'post_id' => $postId,
            'content' => 'Test comment',
            'author_name' => 'Test',
            'author_email' => 'test@test.com',
            'status' => 'pending',
        ]);

        $this->assertEquals($this->siteA->id, $comment->site_id);
    }

    // -------------------------------------------------------
    // RequireSiteContext middleware
    // -------------------------------------------------------

    public function test_user_with_no_sites_redirected_from_site_dependent_pages(): void
    {
        $noSiteUser = User::factory()->create();
        $authorRole = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        $noSiteUser->assignRole('author');

        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $this->actingAs($noSiteUser);

        // Site-dependent pages should redirect to template gallery
        $this->get("/{$panelPath}/cms-pages")->assertRedirect("/{$panelPath}/template-gallery");
        $this->get("/{$panelPath}/site-settings")->assertRedirect("/{$panelPath}/template-gallery");
    }

    public function test_user_with_no_sites_can_access_non_site_pages(): void
    {
        $noSiteUser = User::factory()->create();
        $authorRole = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        $noSiteUser->assignRole('author');

        // Grant required permissions
        foreach (['View:TemplateGallery', 'ViewAny:CmsPost', 'ViewAny:TallcmsMedia'] as $perm) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web']
            );
            $authorRole->givePermissionTo($permission);
        }

        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $this->actingAs($noSiteUser);

        // Dashboard, gallery, posts, media, sites — all accessible without site context
        $this->get("/{$panelPath}")->assertOk();
        $this->get("/{$panelPath}/template-gallery")->assertOk();
        $this->get("/{$panelPath}/cms-posts")->assertOk();
        $this->get("/{$panelPath}/tallcms-media")->assertOk();
    }

    public function test_super_admin_not_redirected_by_middleware(): void
    {
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $this->actingAs($this->superAdmin);

        // Super-admin should access everything without redirect
        $this->get("/{$panelPath}/cms-pages")->assertOk();
        $this->get("/{$panelPath}/site-settings")->assertOk();
        $this->get("/{$panelPath}/cms-posts")->assertOk();
    }

    // -------------------------------------------------------
    // Regression: editing a page without ambient site session
    //
    // Pre-refactor, the CmsPage edit route relied on session-based SiteScope
    // filtering to resolve the record. Navigating from the Site → Pages
    // relation manager without first mutating session produced a 404 because
    // the scope filtered the route-model-binding lookup. In the new model,
    // route-binding goes straight to the DB (no ambient scope), so editing
    // any page succeeds as long as the user has permission.
    // -------------------------------------------------------

    public function test_create_page_via_site_query_param_assigns_site_id(): void
    {
        $this->actingAs($this->superAdmin);
        session()->forget('multisite_admin_site_id');

        // Use Livewire to simulate the full create flow: mount on the
        // create page (URL includes ?site=<id>), fill the form, submit.
        \Livewire\Livewire::withQueryParams(['site' => $this->siteB->id])
            ->test(\TallCms\Cms\Filament\Resources\CmsPages\Pages\CreateCmsPage::class)
            ->fillForm([
                'title' => 'New Page On Site B',
                'slug' => 'new-page-site-b',
                'status' => 'draft',
            ])
            ->call('create');

        $created = DB::table('tallcms_pages')
            ->whereRaw("JSON_EXTRACT(slug, '$.en') = ?", ['new-page-site-b'])
            ->first();

        $this->assertNotNull($created, 'Page should have been created');
        $this->assertEquals($this->siteB->id, $created->site_id);
    }

    // -------------------------------------------------------
    // Regression: Site::pages() / Site::menus() relation must ignore SiteScope
    //
    // SiteScope's admin-context detection relies on a Referer header that
    // browsers may strip on Livewire /livewire/update requests. When that
    // happens, the resolver classifies the request as frontend, resolves
    // the Host to a site, and the scope filters every CmsPage query to that
    // site's id. A user editing Site B would then get an empty pages
    // relation manager because `WHERE site_id = B AND site_id = A` matches
    // nothing. The relation explicitly bypasses the scope to stay correct
    // regardless of how the resolver classifies the request.
    // -------------------------------------------------------

    public function test_site_pages_relation_returns_rows_even_when_resolver_picks_a_different_site(): void
    {
        // Put a page in site B.
        $pageInB = DB::table('tallcms_pages')->insertGetId([
            'site_id' => $this->siteB->id,
            'title' => json_encode(['en' => 'Belongs to B']),
            'slug' => json_encode(['en' => 'belongs-b']),
            'status' => 'published',
            'is_homepage' => false,
            'sort_order' => 0,
            'author_id' => $this->superAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Force the resolver into a frontend-ish state pointing at site A —
        // this simulates the Livewire-without-Referer case where the scope
        // would otherwise add `WHERE site_id = siteA` to every CmsPage query.
        app(\Tallcms\Multisite\Services\CurrentSiteResolver::class)->reset();
        app(\Tallcms\Multisite\Services\CurrentSiteResolver::class)->overrideForRequest($this->siteA);

        $pagesOnB = $this->siteB->pages()->pluck('id')->all();

        $this->assertContains($pageInB, $pagesOnB, 'Relation must return its own site\'s pages regardless of resolver state.');
    }

    public function test_site_menus_relation_returns_rows_even_when_resolver_picks_a_different_site(): void
    {
        $menuInB = DB::table('tallcms_menus')->insertGetId([
            'site_id' => $this->siteB->id,
            'name' => 'B Menu',
            'location' => 'footer',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(\Tallcms\Multisite\Services\CurrentSiteResolver::class)->reset();
        app(\Tallcms\Multisite\Services\CurrentSiteResolver::class)->overrideForRequest($this->siteA);

        $menusOnB = $this->siteB->menus()->pluck('id')->all();

        $this->assertContains($menuInB, $menusOnB);
    }

    public function test_edit_page_without_session_site_does_not_404(): void
    {
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $pageId = DB::table('tallcms_pages')->insertGetId([
            'site_id' => $this->siteB->id,
            'title' => json_encode(['en' => 'Belongs to Site B']),
            'slug' => json_encode(['en' => 'site-b-page']),
            'status' => 'published',
            'is_homepage' => false,
            'sort_order' => 0,
            'author_id' => $this->superAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // No session mutation — simulate arriving via the Pages relation
        // manager's plain-URL edit link.
        session()->forget('multisite_admin_site_id');

        $this->actingAs($this->superAdmin);
        $this->get("/{$panelPath}/cms-pages/{$pageId}/edit")->assertOk();
    }
}
