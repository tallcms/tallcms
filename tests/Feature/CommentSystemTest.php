<?php

namespace Tests\Feature;

use App\Models\CmsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Models\CmsComment;
use TallCms\Cms\Notifications\CommentApprovedNotification;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected CmsPost $publishedPost;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('comments:127.0.0.1');
        Notification::fake();

        $this->user = User::factory()->create();
        $this->publishedPost = CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }

    // ---------------------------------------------------------------
    // Model tests
    // ---------------------------------------------------------------

    public function test_comment_scopes(): void
    {
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Pending',
            'status' => 'pending',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Approved',
            'status' => 'approved',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Rejected',
            'status' => 'rejected',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Spam',
            'status' => 'spam',
        ]);

        $this->assertEquals(1, CmsComment::pending()->count());
        $this->assertEquals(1, CmsComment::approved()->count());
        $this->assertEquals(1, CmsComment::rejected()->count());
        $this->assertEquals(1, CmsComment::spam()->count());
    }

    public function test_top_level_scope_excludes_replies(): void
    {
        $parent = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Top level',
            'status' => 'approved',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parent->id,
            'content' => 'Reply',
            'status' => 'approved',
        ]);

        $this->assertEquals(1, CmsComment::topLevel()->count());
        $this->assertEquals(2, CmsComment::count());
    }

    public function test_approve_sets_status_and_approver(): void
    {
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Pending comment',
            'status' => 'pending',
            'author_name' => 'Guest',
            'author_email' => 'guest@example.com',
        ]);

        $comment->approve($this->user);
        $comment->refresh();

        $this->assertEquals('approved', $comment->status);
        $this->assertEquals($this->user->id, $comment->approved_by);
        $this->assertNotNull($comment->approved_at);
        $this->assertTrue($comment->isApproved());
        $this->assertFalse($comment->isPending());
    }

    public function test_reject_sets_status(): void
    {
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'To reject',
            'status' => 'pending',
        ]);

        $comment->reject();
        $this->assertEquals('rejected', $comment->fresh()->status);
    }

    public function test_mark_as_spam_sets_status(): void
    {
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Spam content',
            'status' => 'pending',
        ]);

        $comment->markAsSpam();
        $this->assertEquals('spam', $comment->fresh()->status);
    }

    public function test_guest_author_helpers(): void
    {
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Guest comment',
            'status' => 'pending',
            'author_name' => 'Jane Guest',
            'author_email' => 'jane@guest.com',
        ]);

        $this->assertTrue($comment->isGuest());
        $this->assertEquals('Jane Guest', $comment->getAuthorName());
        $this->assertEquals('jane@guest.com', $comment->getAuthorEmail());
    }

    public function test_authenticated_author_helpers(): void
    {
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Auth comment',
            'status' => 'pending',
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($comment->isGuest());
        $this->assertEquals($this->user->name, $comment->getAuthorName());
        $this->assertEquals($this->user->email, $comment->getAuthorEmail());
    }

    public function test_post_relationships(): void
    {
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Pending',
            'status' => 'pending',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Approved',
            'status' => 'approved',
        ]);

        $this->assertEquals(2, $this->publishedPost->comments()->count());
        $this->assertEquals(1, $this->publishedPost->approvedComments()->count());
    }

    public function test_approved_replies_relationship_excludes_non_approved(): void
    {
        $parent = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Parent',
            'status' => 'approved',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parent->id,
            'content' => 'Approved reply',
            'status' => 'approved',
        ]);
        CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parent->id,
            'content' => 'Pending reply',
            'status' => 'pending',
        ]);

        $this->assertEquals(1, $parent->approvedReplies()->count());
        $this->assertEquals(2, $parent->replies()->count());
    }

    public function test_soft_delete_and_restore(): void
    {
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Will be deleted',
            'status' => 'approved',
        ]);

        $comment->delete();
        $this->assertSoftDeleted($comment);
        $this->assertEquals(0, CmsComment::count());
        $this->assertEquals(1, CmsComment::withTrashed()->count());

        $comment->restore();
        $this->assertEquals(1, CmsComment::count());
    }

    // ---------------------------------------------------------------
    // Controller tests — guest submission
    // ---------------------------------------------------------------

    public function test_guest_can_submit_comment(): void
    {
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Great article!',
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('tallcms_comments', [
            'post_id' => $this->publishedPost->id,
            'content' => 'Great article!',
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_authenticated_user_can_submit_comment(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Nice post!',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('tallcms_comments', [
            'post_id' => $this->publishedPost->id,
            'user_id' => $this->user->id,
            'content' => 'Nice post!',
            'author_name' => null,
            'author_email' => null,
        ]);
    }

    public function test_guest_comment_requires_name_and_email(): void
    {
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Missing author info',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['author_name', 'author_email']);
    }

    public function test_content_is_required(): void
    {
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => '',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_post_must_be_published(): void
    {
        $draftPost = CmsPost::factory()->create([
            'status' => ContentStatus::Draft->value,
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $draftPost->id,
            'content' => 'Comment on draft',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.post_id.0', 'The selected post is not available for comments.');
    }

    public function test_scheduled_post_rejects_comments(): void
    {
        $scheduledPost = CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $scheduledPost->id,
            'content' => 'Comment on scheduled',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.post_id.0', 'The selected post is not available for comments.');
    }

    // ---------------------------------------------------------------
    // Nesting and cross-post validation
    // ---------------------------------------------------------------

    public function test_reply_to_approved_comment(): void
    {
        $parent = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Top level',
            'status' => 'approved',
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parent->id,
            'content' => 'Reply',
            'author_name' => 'Replier',
            'author_email' => 'reply@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tallcms_comments', [
            'parent_id' => $parent->id,
            'content' => 'Reply',
        ]);
    }

    public function test_reply_to_pending_comment_is_rejected(): void
    {
        $pending = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Pending parent',
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $pending->id,
            'content' => 'Reply to pending',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.parent_id.0', 'The parent comment is not valid.');
    }

    public function test_cross_post_parent_is_rejected(): void
    {
        $otherPost = CmsPost::factory()->create([
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
        $otherComment = CmsComment::create([
            'post_id' => $otherPost->id,
            'content' => 'On other post',
            'status' => 'approved',
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $otherComment->id,
            'content' => 'Cross-post reply',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.parent_id.0', 'The parent comment is not valid.');
    }

    public function test_soft_deleted_parent_is_rejected(): void
    {
        $parent = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Will be deleted',
            'status' => 'approved',
        ]);
        $parent->delete();

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parent->id,
            'content' => 'Reply to deleted',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.parent_id.0', 'The parent comment is not valid.');
    }

    public function test_max_depth_enforced_default(): void
    {
        // Default max_depth=2: top-level + 1 reply. Reply to reply should fail.
        $topLevel = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Top level',
            'status' => 'approved',
        ]);
        $reply = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $topLevel->id,
            'content' => 'First reply',
            'status' => 'approved',
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $reply->id,
            'content' => 'Too deep',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.parent_id.0', 'Maximum reply depth reached.');
    }

    public function test_max_depth_3_allows_second_level_reply(): void
    {
        config(['tallcms.comments.max_depth' => 3]);

        $topLevel = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Top level',
            'status' => 'approved',
        ]);
        $reply = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $topLevel->id,
            'content' => 'First reply',
            'status' => 'approved',
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $reply->id,
            'content' => 'Second-level reply',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertOk();
    }

    public function test_max_depth_1_rejects_all_replies(): void
    {
        config(['tallcms.comments.max_depth' => 1]);

        $topLevel = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Top level',
            'status' => 'approved',
        ]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $topLevel->id,
            'content' => 'Should be rejected',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.parent_id.0', 'Maximum reply depth reached.');
    }

    // ---------------------------------------------------------------
    // Depth calculation with soft-deleted ancestors
    // ---------------------------------------------------------------

    public function test_depth_calculation_survives_soft_deleted_ancestor(): void
    {
        config(['tallcms.comments.max_depth' => 4]);

        $grandparent = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Grandparent',
            'status' => 'approved',
        ]);
        $parent = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $grandparent->id,
            'content' => 'Parent',
            'status' => 'approved',
        ]);
        $child = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'parent_id' => $parent->id,
            'content' => 'Child',
            'status' => 'approved',
        ]);

        // Soft-delete the grandparent
        $grandparent->delete();

        // Reply to child — depth=3, max_depth=4, so should be allowed
        // This must not crash even though grandparent is soft-deleted
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'parent_id' => $child->id,
            'content' => 'Deep reply with deleted ancestor',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertOk();
    }

    // ---------------------------------------------------------------
    // Spam protection
    // ---------------------------------------------------------------

    public function test_honeypot_silently_rejects(): void
    {
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Spam',
            'author_name' => 'Bot',
            'author_email' => 'bot@spam.com',
            '_honeypot' => 'http://spam.com',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('tallcms_comments', 0);
    }

    public function test_rate_limiting(): void
    {
        $maxAttempts = config('tallcms.comments.rate_limit', 5);

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $response = $this->postJson(route('tallcms.comments.submit'), [
                'post_id' => $this->publishedPost->id,
                'content' => "Comment {$i}",
                'author_name' => 'Test',
                'author_email' => 'test@example.com',
            ]);
            $response->assertOk();
        }

        // Next attempt should be rate-limited
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Too many',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
        $this->assertDatabaseCount('tallcms_comments', $maxAttempts);
    }

    public function test_content_stripped_of_html_tags(): void
    {
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => '<b>Bold</b> <script>alert("xss")</script> text',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tallcms_comments', [
            'content' => 'Bold alert("xss") text',
        ]);
    }

    public function test_empty_after_sanitize_rejected(): void
    {
        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => '<b></b><i></i>',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.content.0', 'The comment content cannot be empty.');
    }

    // ---------------------------------------------------------------
    // Config gates
    // ---------------------------------------------------------------

    public function test_guest_comments_disabled_rejects_unauthenticated(): void
    {
        config(['tallcms.comments.guest_comments' => false]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Guest blocked',
            'author_name' => 'Guest',
            'author_email' => 'guest@example.com',
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_comments_disabled_allows_authenticated(): void
    {
        config(['tallcms.comments.guest_comments' => false]);

        $response = $this->actingAs($this->user)->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Auth user comment',
        ]);

        $response->assertOk();
    }

    public function test_comments_disabled_returns_404(): void
    {
        config(['tallcms.comments.enabled' => false]);

        $response = $this->postJson(route('tallcms.comments.submit'), [
            'post_id' => $this->publishedPost->id,
            'content' => 'Should fail',
            'author_name' => 'Test',
            'author_email' => 'test@example.com',
        ]);

        $response->assertStatus(404);
    }

    // ---------------------------------------------------------------
    // Notification tests
    // ---------------------------------------------------------------

    public function test_approve_sends_notification_to_guest_commenter(): void
    {
        config(['tallcms.comments.notify_on_approval' => true]);

        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'Guest comment',
            'status' => 'pending',
            'author_name' => 'Guest',
            'author_email' => 'guest@example.com',
        ]);

        $comment->approve($this->user);

        Notification::assertSentOnDemand(CommentApprovedNotification::class);
    }

    public function test_approve_sends_notification_to_auth_commenter(): void
    {
        config(['tallcms.comments.notify_on_approval' => true]);

        $commenter = User::factory()->create();
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'user_id' => $commenter->id,
            'content' => 'Auth comment',
            'status' => 'pending',
        ]);

        $comment->approve($this->user);

        // NotificationDispatcher resolves to App wrapper class when it exists
        Notification::assertSentTo($commenter, \App\Notifications\CommentApprovedNotification::class);
    }

    public function test_approve_skips_notification_when_disabled(): void
    {
        config(['tallcms.comments.notify_on_approval' => false]);

        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'content' => 'No notification',
            'status' => 'pending',
            'author_name' => 'Guest',
            'author_email' => 'guest@example.com',
        ]);

        $comment->approve($this->user);

        Notification::assertNothingSent();
    }

    public function test_approve_survives_deleted_user(): void
    {
        config(['tallcms.comments.notify_on_approval' => true]);

        $commenter = User::factory()->create();
        $comment = CmsComment::create([
            'post_id' => $this->publishedPost->id,
            'user_id' => $commenter->id,
            'content' => 'Auth comment',
            'status' => 'pending',
        ]);

        // Delete the commenter (FK nullOnDelete sets user_id to null)
        $commenter->delete();
        $comment->refresh();

        // Approval must not throw
        $comment->approve($this->user);

        $this->assertEquals('approved', $comment->fresh()->status);
    }
}
