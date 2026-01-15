<?php

declare(strict_types=1);

namespace TallCms\Cms\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TallCms\Cms\Enums\ContentStatus;

trait HasPublishingWorkflow
{
    /**
     * Get the user who approved this content
     */
    public function approver(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'approved_by');
    }

    /**
     * Get the user who submitted this content for review
     */
    public function submitter(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'submitted_by');
    }

    /**
     * Check if content is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === ContentStatus::Draft->value;
    }

    /**
     * Check if content is pending review
     */
    public function isPending(): bool
    {
        return $this->status === ContentStatus::Pending->value;
    }

    /**
     * Check if content is published
     * A null published_at means "publish immediately"
     */
    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published->value
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Check if content is scheduled (published status but future date)
     */
    public function isScheduled(): bool
    {
        return $this->status === ContentStatus::Published->value
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    /**
     * Submit content for review
     */
    public function submitForReview(): void
    {
        $this->update([
            'status' => ContentStatus::Pending->value,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Approve content and publish
     */
    public function approve(): void
    {
        $this->update([
            'status' => ContentStatus::Published->value,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
            // Only set published_at if not already set (allows scheduling)
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Reject content with reason
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => ContentStatus::Draft->value,
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Get the content status as enum
     */
    public function getStatusEnum(): ContentStatus
    {
        return ContentStatus::from($this->status);
    }

    /**
     * Scope for pending content
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Pending->value);
    }

    /**
     * Scope for draft content
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Draft->value);
    }

    /**
     * Scope for scheduled content (published status, future date)
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published->value)
            ->where('published_at', '>', now());
    }

    /**
     * Scope for actually visible/published content
     * Status must be published AND either:
     * - published_at is null (publish immediately), OR
     * - published_at is in the past
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published->value)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Check if content can be submitted for review
     */
    public function canSubmitForReview(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if content can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    /**
     * Check if content can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    /**
     * Get the rejection reason if rejected
     */
    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
    }

    /**
     * Check if content was recently rejected
     */
    public function wasRejected(): bool
    {
        return $this->isDraft() && ! empty($this->rejection_reason);
    }
}
