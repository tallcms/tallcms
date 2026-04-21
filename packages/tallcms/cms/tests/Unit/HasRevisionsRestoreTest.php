<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Tests\TestCase;

/**
 * Regression test for tallcms/cms#2:
 * Restoring a revision on tallcms_pages previously threw
 * "Unknown column 'excerpt' in 'field list'" because the
 * HasRevisions trait unconditionally forceFilled `excerpt`,
 * which doesn't exist on the pages table.
 */
class HasRevisionsRestoreTest extends TestCase
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
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('views')->default(0);
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

    public function test_restoring_revision_on_page_does_not_touch_missing_excerpt_column(): void
    {
        $page = CmsPage::create([
            'title' => ['en' => 'Original Title'],
            'slug' => ['en' => 'original-title'],
            'content' => ['en' => 'Original content'],
        ]);

        $page->update([
            'title' => ['en' => 'Updated Title'],
            'content' => ['en' => 'Updated content'],
        ]);

        $firstRevision = $page->revisions()->where('revision_number', 1)->firstOrFail();

        $page->restoreRevision($firstRevision);

        $page->refresh();
        $this->assertSame('Original Title', $page->getTranslation('title', 'en'));
        $this->assertSame('Original content', $page->getTranslation('content', 'en'));
    }

    public function test_restoring_revision_on_post_still_restores_excerpt(): void
    {
        $post = CmsPost::create([
            'title' => ['en' => 'Original Post'],
            'slug' => ['en' => 'original-post'],
            'content' => ['en' => 'Original body'],
            'excerpt' => ['en' => 'Original excerpt'],
        ]);

        $post->update([
            'title' => ['en' => 'Updated Post'],
            'excerpt' => ['en' => 'Updated excerpt'],
        ]);

        $firstRevision = $post->revisions()->where('revision_number', 1)->firstOrFail();

        $post->restoreRevision($firstRevision);

        $post->refresh();
        $this->assertSame('Original Post', $post->getTranslation('title', 'en'));
        $this->assertSame('Original excerpt', $post->getTranslation('excerpt', 'en'));
    }
}
