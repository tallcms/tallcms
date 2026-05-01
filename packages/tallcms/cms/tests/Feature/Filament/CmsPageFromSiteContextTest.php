<?php

namespace TallCms\Cms\Tests\Feature\Filament;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use TallCms\Cms\Filament\Concerns\HasFromSiteContext;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Tests\TestCase;

/**
 * Covers the trait that preserves Site → Page navigation context across
 * the Filament edit/create lifecycle.
 *
 * The failure mode the trait exists to prevent: reading from request()
 * inside save / delete / redirect lifecycle hooks would silently drop
 * the from_site value because Livewire action requests go through
 * /livewire/update, which has no access to the original page URL's
 * query string. The trait captures the param ONCE in mount() into a
 * Livewire public property; everything afterward reads the property.
 *
 * The first test pins the non-multisite safety guarantee — when
 * CmsPage::hasSiteIdColumn() returns false, the trait returns null
 * at every resolution point regardless of input. Single-site installs
 * see identical behaviour to before this feature shipped.
 */
class CmsPageFromSiteContextTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->text('search_content')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_homepage')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('uuid')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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

    /**
     * Returns a fresh anonymous instance that mixes in HasFromSiteContext.
     * The two public properties match the Livewire surface of EditCmsPage
     * (record only) or CreateCmsPage (record + ownerSiteId). Trait methods
     * are aliased to public so the assertion-level tests can call them
     * directly without going through a wrapper.
     */
    protected function makeHolder(?CmsPage $record = null, ?int $ownerSiteId = null): object
    {
        $holder = new class
        {
            use HasFromSiteContext {
                captureFromSite as public;
                pendingFromSiteId as public;
                fromSiteUrl as public;
                fromSiteName as public;
                getBackToSiteAction as public;
            }

            public mixed $record = null;

            public ?int $ownerSiteId = null;
        };

        $holder->record = $record;
        $holder->ownerSiteId = $ownerSiteId;

        return $holder;
    }

    /**
     * Resets the per-process schema cache between tests so a test that
     * forces hasSiteIdColumn=false doesn't leak that state into siblings.
     */
    protected function tearDown(): void
    {
        $this->resetHasSiteIdColumnCache();

        parent::tearDown();
    }

    protected function resetHasSiteIdColumnCache(?bool $value = null): void
    {
        $reflection = new ReflectionClass(CmsPage::class);
        $property = $reflection->getProperty('hasSiteIdColumn');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }

    public function test_single_site_install_keeps_trait_inert(): void
    {
        // Force the column-detection cache to the "single-site" answer
        // without dropping the actual schema (which other tests share).
        $this->resetHasSiteIdColumnCache(false);

        request()->query->set('from_site', '7');

        $holder = $this->makeHolder();
        $holder->captureFromSite();

        $this->assertNull(
            $holder->fromSiteId,
            'captureFromSite must short-circuit when site_id column is absent.'
        );
        $this->assertNull($holder->pendingFromSiteId());
        $this->assertNull($holder->fromSiteUrl());
        $this->assertNull($holder->fromSiteName());
        $this->assertNull($holder->getBackToSiteAction());
    }

    public function test_capture_from_site_records_query_param(): void
    {
        request()->query->set('from_site', '7');

        $holder = $this->makeHolder();
        $holder->captureFromSite();

        $this->assertSame(7, $holder->fromSiteId);
    }

    public function test_capture_from_site_ignores_non_numeric_input(): void
    {
        request()->query->set('from_site', 'haha');

        $holder = $this->makeHolder();
        $holder->captureFromSite();

        $this->assertNull($holder->fromSiteId);
    }

    public function test_capture_from_site_ignores_zero_or_negative(): void
    {
        request()->query->set('from_site', '0');
        $holder = $this->makeHolder();
        $holder->captureFromSite();
        $this->assertNull($holder->fromSiteId);

        request()->query->set('from_site', '-3');
        $holder = $this->makeHolder();
        $holder->captureFromSite();
        $this->assertNull($holder->fromSiteId);
    }

    public function test_edit_path_validates_against_record_site_id(): void
    {
        $page = CmsPage::create([
            'site_id' => 7,
            'title' => ['en' => 'p'],
            'slug' => ['en' => 'p'],
            'status' => 'draft',
        ]);

        $holder = $this->makeHolder(record: $page);
        $holder->fromSiteId = 7;

        $this->assertSame(7, $holder->pendingFromSiteId());
    }

    public function test_edit_path_rejects_mismatched_record_site_id(): void
    {
        $page = CmsPage::create([
            'site_id' => 7,
            'title' => ['en' => 'p'],
            'slug' => ['en' => 'p'],
            'status' => 'draft',
        ]);

        // Spoofed query: page belongs to site 7, attacker passes from_site=99.
        $holder = $this->makeHolder(record: $page);
        $holder->fromSiteId = 99;

        $this->assertNull($holder->pendingFromSiteId());
    }

    public function test_create_path_validates_against_owner_site_id_pre_save(): void
    {
        // CreateCmsPage before save: $record is unsaved (treated here as null
        // since Filament's CreateRecord may not initialise $record yet).
        // $ownerSiteId carries the captured ?site=N value.
        $holder = $this->makeHolder(record: null, ownerSiteId: 7);
        $holder->fromSiteId = 7;

        $this->assertSame(
            7,
            $holder->pendingFromSiteId(),
            'Pre-save resolution must validate against ownerSiteId so the back action renders before the user clicks Save.'
        );
    }

    public function test_create_path_rejects_mismatched_owner_site_id(): void
    {
        // Spoofed: ?site=7&from_site=99 — the user came in claiming site 99
        // but the page they're creating belongs to site 7. Reject.
        $holder = $this->makeHolder(record: null, ownerSiteId: 7);
        $holder->fromSiteId = 99;

        $this->assertNull($holder->pendingFromSiteId());
    }

    public function test_eloquent_string_site_id_does_not_break_strict_equality(): void
    {
        // If a host removes the int cast, Eloquent may surface site_id as a
        // string. The trait casts both sides explicitly so the validation
        // still passes. This test pins that defence.
        $page = new CmsPage([
            'site_id' => '7', // string on purpose
            'title' => ['en' => 'p'],
            'slug' => ['en' => 'p'],
            'status' => 'draft',
        ]);

        $holder = $this->makeHolder(record: $page);
        $holder->fromSiteId = 7;

        $this->assertSame(7, $holder->pendingFromSiteId());
    }

    public function test_from_site_name_resolves_via_site_model(): void
    {
        Site::create([
            'name' => 'Chuan Grove',
            'domain' => 'chuan-grove.example.com',
            'is_default' => false,
            'is_active' => true,
        ]);
        $siteId = (int) Site::where('name', 'Chuan Grove')->value('id');

        $page = CmsPage::create([
            'site_id' => $siteId,
            'title' => ['en' => 'p'],
            'slug' => ['en' => 'p'],
            'status' => 'draft',
        ]);

        $holder = $this->makeHolder(record: $page);
        $holder->fromSiteId = $siteId;

        $this->assertSame('Chuan Grove', $holder->fromSiteName());
    }

    public function test_from_site_url_returns_null_when_no_site_resource_registered(): void
    {
        // The cms-package test suite doesn't register any Filament panel,
        // so no SiteResource has routes wired. fromSiteUrl()'s try/catch
        // around getUrl() should swallow the failure and return null —
        // proving the graceful-degradation path. (When the multisite or
        // cms-core panel IS registered, getUrl produces a real URL; that
        // path is exercised by the standalone monorepo's Filament tests.)
        $page = CmsPage::create([
            'site_id' => 7,
            'title' => ['en' => 'p'],
            'slug' => ['en' => 'p'],
            'status' => 'draft',
        ]);

        $holder = $this->makeHolder(record: $page);
        $holder->fromSiteId = 7;

        $this->assertNull($holder->fromSiteUrl());
        $this->assertNull(
            $holder->getBackToSiteAction(),
            'Back-to-site action must not render when no SiteResource URL can be produced.'
        );
    }
}
