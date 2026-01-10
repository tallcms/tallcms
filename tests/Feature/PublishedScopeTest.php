<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishedScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for author relationships
        User::factory()->create();
    }

    /**
     * Test matrix for published() scope:
     * | Status    | published_at | Expected in published() |
     * |-----------|--------------|-------------------------|
     * | draft     | null         | NO                      |
     * | draft     | past         | NO                      |
     * | draft     | future       | NO                      |
     * | pending   | null         | NO                      |
     * | pending   | past         | NO                      |
     * | pending   | future       | NO                      |
     * | published | null         | YES (immediate publish) |
     * | published | past         | YES                     |
     * | published | future       | NO (scheduled)          |
     */

    // CmsPost Tests

    public function test_post_draft_with_null_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
    }

    public function test_post_draft_with_past_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
    }

    public function test_post_draft_with_future_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
    }

    public function test_post_pending_with_null_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Pending->value,
            'published_at' => null,
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
    }

    public function test_post_pending_with_past_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Pending->value,
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
    }

    public function test_post_pending_with_future_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Pending->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
    }

    public function test_post_published_with_null_published_at_in_published_scope(): void
    {
        // Null published_at means "publish immediately"
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => null,
        ]);

        $this->assertTrue(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertTrue($post->isPublished());
    }

    public function test_post_published_with_past_published_at_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertTrue($post->isPublished());
    }

    public function test_post_published_with_future_published_at_not_in_published_scope(): void
    {
        $post = CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(CmsPost::published()->where('id', $post->id)->exists());
        $this->assertFalse($post->isPublished());
        $this->assertTrue($post->isScheduled());
    }

    // CmsPage Tests

    public function test_page_draft_with_null_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
    }

    public function test_page_draft_with_past_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
    }

    public function test_page_draft_with_future_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
    }

    public function test_page_pending_with_null_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Pending->value,
            'published_at' => null,
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
    }

    public function test_page_pending_with_past_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Pending->value,
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
    }

    public function test_page_pending_with_future_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Pending->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
    }

    public function test_page_published_with_null_published_at_in_published_scope(): void
    {
        // Null published_at means "publish immediately"
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => null,
        ]);

        $this->assertTrue(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertTrue($page->isPublished());
    }

    public function test_page_published_with_past_published_at_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertTrue($page->isPublished());
    }

    public function test_page_published_with_future_published_at_not_in_published_scope(): void
    {
        $page = CmsPage::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(CmsPage::published()->where('id', $page->id)->exists());
        $this->assertFalse($page->isPublished());
        $this->assertTrue($page->isScheduled());
    }

    // Additional helper method tests

    public function test_post_is_draft_method(): void
    {
        $post = CmsPost::factory()->create(['status' => ContentStatus::Draft->value]);
        $this->assertTrue($post->isDraft());

        $post->status = ContentStatus::Pending->value;
        $this->assertFalse($post->isDraft());
    }

    public function test_post_is_pending_method(): void
    {
        $post = CmsPost::factory()->create(['status' => ContentStatus::Pending->value]);
        $this->assertTrue($post->isPending());

        $post->status = ContentStatus::Draft->value;
        $this->assertFalse($post->isPending());
    }

    public function test_pending_scope(): void
    {
        CmsPost::factory()->create(['status' => ContentStatus::Draft->value]);
        CmsPost::factory()->create(['status' => ContentStatus::Pending->value]);
        CmsPost::factory()->create(['status' => ContentStatus::Published->value]);

        $this->assertEquals(1, CmsPost::pending()->count());
    }

    public function test_draft_scope(): void
    {
        CmsPost::factory()->create(['status' => ContentStatus::Draft->value]);
        CmsPost::factory()->create(['status' => ContentStatus::Pending->value]);
        CmsPost::factory()->create(['status' => ContentStatus::Published->value]);

        $this->assertEquals(1, CmsPost::draft()->count());
    }

    public function test_scheduled_scope(): void
    {
        // Published with past date - not scheduled
        CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        // Published with future date - scheduled
        CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);

        // Draft with future date - not scheduled
        CmsPost::factory()->create([
            'status' => ContentStatus::Draft->value,
            'published_at' => now()->addDay(),
        ]);

        $this->assertEquals(1, CmsPost::scheduled()->count());
    }
}
