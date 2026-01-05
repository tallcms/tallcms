<?php

namespace App\Models\Concerns;

use App\Models\CmsRevision;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasRevisions
{
    /**
     * Boot the trait - register model event listeners
     */
    public static function bootHasRevisions(): void
    {
        // Only create revision on updating, not creating
        static::updating(function ($model) {
            if ($model->shouldCreateRevision()) {
                $model->createRevision();
            }
        });

        // Prune old revisions after saving
        static::saved(function ($model) {
            $model->pruneOldRevisions();
        });
    }

    /**
     * Get all revisions for this model
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(CmsRevision::class, 'revisionable')
            ->orderByDesc('revision_number');
    }

    /**
     * Get the fields that should be tracked for revisions
     */
    protected function getRevisionableFields(): array
    {
        return [
            'title',
            'content',
            'excerpt',
            'meta_title',
            'meta_description',
            'featured_image',
        ];
    }

    /**
     * Check if a revision should be created
     */
    protected function shouldCreateRevision(): bool
    {
        foreach ($this->getRevisionableFields() as $field) {
            if ($this->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new revision from the current (original) state
     */
    public function createRevision(?string $notes = null): CmsRevision
    {
        $nextNumber = ($this->revisions()->max('revision_number') ?? 0) + 1;

        return $this->revisions()->create([
            'user_id' => auth()->id(),
            'title' => $this->getOriginal('title') ?? $this->title,
            'excerpt' => $this->getOriginal('excerpt'),
            // Use getRawOriginal to store exact database value (JSON string, not decoded array)
            'content' => $this->getRawOriginal('content'),
            'meta_title' => $this->getOriginal('meta_title'),
            'meta_description' => $this->getOriginal('meta_description'),
            'featured_image' => $this->getOriginal('featured_image'),
            'additional_data' => $this->getAdditionalRevisionData(),
            'revision_number' => $nextNumber,
            'notes' => $notes,
        ]);
    }

    /**
     * Get additional data to store with the revision
     * Override in model if needed
     */
    protected function getAdditionalRevisionData(): array
    {
        return [];
    }

    /**
     * Restore this model from a revision
     * Note: Only restores content/meta fields, NOT status
     */
    public function restoreRevision(CmsRevision $revision): void
    {
        // Content is stored as raw string in revision, decode for models with array cast
        $content = $revision->content;
        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content = $decoded;
            }
            // If not valid JSON, keep as string (legacy HTML content)
        }

        // Use forceFill to bypass guarded/cast issues
        $this->forceFill([
            'title' => $revision->title,
            'excerpt' => $revision->excerpt,
            'content' => $content,
            'meta_title' => $revision->meta_title,
            'meta_description' => $revision->meta_description,
            'featured_image' => $revision->featured_image,
        ])->save();
    }

    /**
     * Prune old revisions beyond the configured limit
     */
    protected function pruneOldRevisions(): void
    {
        $limit = config('tallcms.publishing.revision_limit');

        if ($limit === null) {
            return; // Unlimited revisions
        }

        $revisionCount = $this->revisions()->count();

        if ($revisionCount > $limit) {
            // Delete oldest revisions beyond limit
            $this->revisions()
                ->orderBy('revision_number')
                ->limit($revisionCount - $limit)
                ->delete();
        }
    }

    /**
     * Get the latest revision
     */
    public function getLatestRevision(): ?CmsRevision
    {
        return $this->revisions()->first();
    }

    /**
     * Get revision by number
     */
    public function getRevision(int $number): ?CmsRevision
    {
        return $this->revisions()->where('revision_number', $number)->first();
    }
}
