<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Tests\TestCase;

class HasRevisionsPruningTest extends TestCase
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

    public function test_pruning_automatic_revisions_keeps_newest_revisions(): void
    {
        Config::set('tallcms.publishing.revision_limit', 2);
        Config::set('tallcms.publishing.revision_manual_limit', null);

        $page = $this->createPageWithoutRevisions();

        $this->createRevision($page, 1, false);
        $this->createRevision($page, 2, false);
        $this->createRevision($page, 3, false);
        $this->createRevision($page, 4, false);

        $this->pruneOldRevisions($page);

        $this->assertSame([3, 4], $this->revisionNumbers($page, false));
    }

    public function test_pruning_manual_revisions_keeps_newest_revisions(): void
    {
        Config::set('tallcms.publishing.revision_limit', null);
        Config::set('tallcms.publishing.revision_manual_limit', 2);

        $page = $this->createPageWithoutRevisions();

        $this->createRevision($page, 1, true);
        $this->createRevision($page, 2, true);
        $this->createRevision($page, 3, true);
        $this->createRevision($page, 4, true);

        $this->pruneOldRevisions($page);

        $this->assertSame([3, 4], $this->revisionNumbers($page, true));
    }

    public function test_pruning_automatic_and_manual_revisions_uses_independent_limits(): void
    {
        Config::set('tallcms.publishing.revision_limit', 2);
        Config::set('tallcms.publishing.revision_manual_limit', 1);

        $page = $this->createPageWithoutRevisions();

        $this->createRevision($page, 1, false);
        $this->createRevision($page, 2, false);
        $this->createRevision($page, 3, false);
        $this->createRevision($page, 4, true);
        $this->createRevision($page, 5, true);
        $this->createRevision($page, 6, true);

        $this->pruneOldRevisions($page);

        $this->assertSame([2, 3], $this->revisionNumbers($page, false));
        $this->assertSame([6], $this->revisionNumbers($page, true));
    }

    private function createPageWithoutRevisions(): CmsPage
    {
        return CmsPage::withoutEvents(fn (): CmsPage => CmsPage::create([
            'title' => ['en' => 'Revision pruning test'],
            'slug' => ['en' => 'revision-pruning-test'],
        ]));
    }

    private function createRevision(CmsPage $page, int $revisionNumber, bool $isManual): void
    {
        $page->revisions()->create([
            'title' => json_encode(['en' => "Revision {$revisionNumber}"]),
            'revision_number' => $revisionNumber,
            'is_manual' => $isManual,
        ]);
    }

    /**
     * @return array<int>
     */
    private function revisionNumbers(CmsPage $page, bool $isManual): array
    {
        return $page->revisions()
            ->where('is_manual', $isManual)
            ->reorder('revision_number')
            ->pluck('revision_number')
            ->all();
    }

    private function pruneOldRevisions(CmsPage $page): void
    {
        $pruneOldRevisions = function (): void {
            $this->pruneOldRevisions();
        };

        $pruneOldRevisions->call($page);
    }
}
