<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\User;
use TallCms\Cms\Notifications\ContentApprovedNotification;
use TallCms\Cms\Notifications\ContentRejectedNotification;
use TallCms\Cms\Notifications\ContentSubmittedForReviewNotification;

class PublishingWorkflowService
{
    /**
     * Submit content for review
     */
    public function submitForReview(Model $content): void
    {
        DB::transaction(function () use ($content) {
            $content->submitForReview();

            if ($this->notificationsEnabled()) {
                $this->notifyApprovers($content);
            }
        });
    }

    /**
     * Approve content and publish
     */
    public function approve(Model $content): void
    {
        DB::transaction(function () use ($content) {
            $author = $content->author;
            $content->approve();

            if ($this->notificationsEnabled() && $author) {
                $author->notify(new ContentApprovedNotification($content));
            }
        });
    }

    /**
     * Reject content with reason
     */
    public function reject(Model $content, string $reason): void
    {
        DB::transaction(function () use ($content, $reason) {
            $author = $content->author;
            $content->reject($reason);

            if ($this->notificationsEnabled() && $author) {
                $author->notify(new ContentRejectedNotification($content, $reason));
            }
        });
    }

    /**
     * Check if workflow notifications are enabled
     */
    protected function notificationsEnabled(): bool
    {
        return config('tallcms.publishing.notifications_enabled', true);
    }

    /**
     * Notify users with approval permission
     */
    protected function notifyApprovers(Model $content): void
    {
        $permission = $this->getApprovePermission($content);

        $approvers = User::permission($permission)->get();

        foreach ($approvers as $approver) {
            // Don't notify the submitter
            if ($approver->id === $content->submitted_by) {
                continue;
            }

            $approver->notify(new ContentSubmittedForReviewNotification($content));
        }
    }

    /**
     * Get the approval permission name for this content type
     */
    protected function getApprovePermission(Model $content): string
    {
        if ($content instanceof CmsPost) {
            return 'Approve:CmsPost';
        }

        if ($content instanceof CmsPage) {
            return 'Approve:CmsPage';
        }

        return 'Approve:'.class_basename($content);
    }

    /**
     * Check if user can submit content for review
     */
    public function canSubmitForReview(Model $content, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        if (! $content->canSubmitForReview()) {
            return false;
        }

        $permission = $this->getSubmitPermission($content);

        return $user->can($permission);
    }

    /**
     * Check if user can approve content
     */
    public function canApprove(Model $content, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        if (! $content->canBeApproved()) {
            return false;
        }

        $permission = $this->getApprovePermission($content);

        return $user->can($permission);
    }

    /**
     * Check if user can reject content
     */
    public function canReject(Model $content, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        if (! $content->canBeRejected()) {
            return false;
        }

        $permission = $this->getApprovePermission($content);

        return $user->can($permission);
    }

    /**
     * Get the submit for review permission name
     */
    protected function getSubmitPermission(Model $content): string
    {
        if ($content instanceof CmsPost) {
            return 'SubmitForReview:CmsPost';
        }

        if ($content instanceof CmsPage) {
            return 'SubmitForReview:CmsPage';
        }

        return 'SubmitForReview:'.class_basename($content);
    }

    /**
     * Get pending content count for dashboard widgets
     */
    public function getPendingCount(string $type = 'all'): int
    {
        $count = 0;

        if ($type === 'all' || $type === 'posts') {
            $count += CmsPost::pending()->count();
        }

        if ($type === 'all' || $type === 'pages') {
            $count += CmsPage::pending()->count();
        }

        return $count;
    }

    /**
     * Get recent pending content for review
     */
    public function getRecentPendingContent(int $limit = 10): array
    {
        $posts = CmsPost::pending()
            ->with('author')
            ->latest('submitted_at')
            ->limit($limit)
            ->get()
            ->map(fn ($post) => [
                'type' => 'post',
                'model' => $post,
                'title' => $post->title,
                'author' => $post->author?->name ?? 'Unknown',
                'submitted_at' => $post->submitted_at,
            ]);

        $pages = CmsPage::pending()
            ->with('author')
            ->latest('submitted_at')
            ->limit($limit)
            ->get()
            ->map(fn ($page) => [
                'type' => 'page',
                'model' => $page,
                'title' => $page->title,
                'author' => $page->author?->name ?? 'Unknown',
                'submitted_at' => $page->submitted_at,
            ]);

        return $posts->concat($pages)
            ->sortByDesc('submitted_at')
            ->take($limit)
            ->values()
            ->all();
    }
}
