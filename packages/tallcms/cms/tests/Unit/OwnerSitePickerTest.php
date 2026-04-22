<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Filament\Forms\OwnerSitePicker;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Tests\TestCase;

/**
 * Pass 4 helper: after the ambient admin scope was removed in Pass 1,
 * raw page-picker queries could leak cross-site options. OwnerSitePicker
 * scopes pickers to the site of the page being edited.
 */
class OwnerSitePickerTest extends TestCase
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

    public function test_returns_only_pages_from_the_owner_site(): void
    {
        $pageA = CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'Page A1'],
            'slug' => ['en' => 'a1'],
            'status' => 'published',
        ]);
        $pageB = CmsPage::create([
            'site_id' => 2,
            'title' => ['en' => 'Page B1'],
            'slug' => ['en' => 'b1'],
            'status' => 'published',
        ]);

        $livewire = new \stdClass;
        $livewire->ownerSiteId = 1;

        $options = OwnerSitePicker::publishedPages($livewire);

        $this->assertArrayHasKey($pageA->id, $options);
        $this->assertArrayNotHasKey($pageB->id, $options);
    }

    public function test_returns_empty_when_no_owner_context(): void
    {
        CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'A published page'],
            'slug' => ['en' => 'published'],
            'status' => 'published',
        ]);

        $livewire = new \stdClass; // no ownerSiteId, no record

        $this->assertSame([], OwnerSitePicker::publishedPages($livewire));
    }

    public function test_excludes_drafts(): void
    {
        CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'Draft'],
            'slug' => ['en' => 'draft'],
            'status' => 'draft',
        ]);

        $livewire = new \stdClass;
        $livewire->ownerSiteId = 1;

        $this->assertSame([], OwnerSitePicker::publishedPages($livewire));
    }

    public function test_uses_record_site_id_when_owner_site_id_missing(): void
    {
        $onPage = CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'On site 1'],
            'slug' => ['en' => 'on-site-1'],
            'status' => 'published',
        ]);
        CmsPage::create([
            'site_id' => 2,
            'title' => ['en' => 'On site 2'],
            'slug' => ['en' => 'on-site-2'],
            'status' => 'published',
        ]);

        // Edit context: $livewire->record is a page on site 1.
        $livewire = new \stdClass;
        $livewire->record = $onPage;

        $options = OwnerSitePicker::publishedPages($livewire);

        $this->assertCount(1, $options);
        $this->assertArrayHasKey($onPage->id, $options);
    }

    public function test_parent_picker_excludes_self(): void
    {
        $self = CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'Self'],
            'slug' => ['en' => 'self'],
            'status' => 'draft',
        ]);
        $other = CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'Other Top-Level'],
            'slug' => ['en' => 'other'],
            'status' => 'draft',
        ]);

        $livewire = new \stdClass;
        $livewire->record = $self;

        $options = OwnerSitePicker::parentPageOptions($livewire);

        $this->assertArrayHasKey($other->id, $options);
        $this->assertArrayNotHasKey($self->id, $options);
    }

    public function test_parent_picker_limited_to_top_level_pages(): void
    {
        $parent = CmsPage::create([
            'site_id' => 1,
            'title' => ['en' => 'Top level'],
            'slug' => ['en' => 'top'],
            'status' => 'draft',
        ]);
        $child = CmsPage::create([
            'site_id' => 1,
            'parent_id' => $parent->id,
            'title' => ['en' => 'Nested'],
            'slug' => ['en' => 'nested'],
            'status' => 'draft',
        ]);

        $livewire = new \stdClass;
        $livewire->ownerSiteId = 1;

        $options = OwnerSitePicker::parentPageOptions($livewire);

        $this->assertArrayHasKey($parent->id, $options);
        $this->assertArrayNotHasKey($child->id, $options);
    }
}
