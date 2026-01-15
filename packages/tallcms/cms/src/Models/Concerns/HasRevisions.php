<?php

declare(strict_types=1);

namespace TallCms\Cms\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use TallCms\Cms\Models\CmsRevision;

trait HasRevisions
{
    /**
     * Flag to skip pre-update revision (used during restore to avoid redundant audit entry)
     */
    protected bool $skipPreUpdateRevision = false;

    /**
     * Flag to skip post-update revision (used during manual snapshot to avoid double writes)
     */
    protected bool $skipPostUpdateRevision = false;

    /**
     * Flag to force post-update revision (used during restore to bypass throttle)
     */
    protected bool $forcePostUpdateRevision = false;

    /**
     * Per-request cache of latest revision
     */
    protected ?CmsRevision $latestRevisionCache = null;

    /**
     * Whether the latest revision cache has been loaded
     */
    protected bool $latestRevisionCacheLoaded = false;

    /**
     * Boot the trait - register model event listeners
     */
    public static function bootHasRevisions(): void
    {
        // Create initial revision when record is first created
        static::created(function ($model) {
            $model->createRevisionFromCurrent('Initial version');
            $model->clearRevisionCache();
        });

        // Pre-update: capture state BEFORE the change (audit trail)
        // Only if hash differs from latest revision
        static::updating(function ($model) {
            if (! $model->skipPreUpdateRevision && $model->shouldCreateRevision()) {
                $latestHash = $model->getLatestRevisionHash();
                $currentHash = $model->computeContentHashFromOriginal();

                // Only create pre-update if current state differs from latest revision
                if ($latestHash === null || $latestHash !== $currentHash) {
                    $model->createRevisionFromOriginal();
                    $model->clearRevisionCache();
                }
            }
        });

        // Post-update: capture the NEW state (current snapshot)
        // Throttled by interval + hash, unless forced (manual/restore)
        static::updated(function ($model) {
            if ($model->forcePostUpdateRevision) {
                // Restore operation - always create, bypass throttle
                $model->createRevisionFromCurrent();
                $model->clearRevisionCache();
            } elseif (! $model->skipPostUpdateRevision && $model->hasRevisableChanges()) {
                if ($model->shouldCreatePostSaveSnapshot()) {
                    $model->createRevisionFromCurrent();
                    $model->clearRevisionCache();
                }
            }

            // Reset flags
            $model->skipPreUpdateRevision = false;
            $model->skipPostUpdateRevision = false;
            $model->forcePostUpdateRevision = false;
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
     * Check if a revision should be created (before save - checks dirty)
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
     * Check if any revisable fields were changed (after save - checks wasChanged)
     */
    protected function hasRevisableChanges(): bool
    {
        foreach ($this->getRevisionableFields() as $field) {
            if ($this->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute SHA-256 hash of current revisionable fields
     */
    protected function computeContentHash(): string
    {
        $data = [];
        foreach ($this->getRevisionableFields() as $field) {
            $value = $this->getAttribute($field);
            // Normalize arrays to JSON for consistent hashing
            $data[$field] = is_array($value) ? json_encode($value) : $value;
        }

        // Sort by key for consistent ordering
        ksort($data);

        return hash('sha256', serialize($data));
    }

    /**
     * Compute SHA-256 hash from original (pre-update) values
     */
    protected function computeContentHashFromOriginal(): string
    {
        $data = [];
        foreach ($this->getRevisionableFields() as $field) {
            $value = $this->getOriginal($field);
            // Normalize arrays to JSON for consistent hashing
            $data[$field] = is_array($value) ? json_encode($value) : $value;
        }

        // Sort by key for consistent ordering
        ksort($data);

        return hash('sha256', serialize($data));
    }

    /**
     * Get cached latest revision (per-request cache)
     */
    protected function getCachedLatestRevision(): ?CmsRevision
    {
        if (! $this->latestRevisionCacheLoaded) {
            $this->latestRevisionCache = $this->revisions()->first();
            $this->latestRevisionCacheLoaded = true;
        }

        return $this->latestRevisionCache;
    }

    /**
     * Get hash from cached latest revision
     */
    protected function getLatestRevisionHash(): ?string
    {
        return $this->getCachedLatestRevision()?->content_hash;
    }

    /**
     * Clear the per-request revision cache
     */
    protected function clearRevisionCache(): void
    {
        $this->latestRevisionCache = null;
        $this->latestRevisionCacheLoaded = false;
    }

    /**
     * Check if a post-save snapshot should be created.
     *
     * Always creates when content hash differs from latest revision to maintain
     * the invariant that "latest revision = current content". This ensures the
     * "Current" label in the timeline is always accurate.
     *
     * Pruning (not throttling) controls revision bloat.
     */
    protected function shouldCreatePostSaveSnapshot(): bool
    {
        // Always create if hash differs from latest revision
        // This maintains "latest revision = current content" invariant
        $latestHash = $this->getLatestRevisionHash();
        $currentHash = $this->computeContentHash();

        return $latestHash !== $currentHash;
    }

    /**
     * Create a revision from the ORIGINAL state (before update - audit trail)
     */
    public function createRevisionFromOriginal(?string $notes = null): CmsRevision
    {
        $nextNumber = ($this->revisions()->max('revision_number') ?? 0) + 1;

        // Compute hash from original values
        $hash = $this->computeContentHashFromOriginal();

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
            'is_manual' => false,
            'content_hash' => $hash,
        ]);
    }

    /**
     * Create a revision from the CURRENT state (after save - current snapshot)
     */
    public function createRevisionFromCurrent(?string $notes = null, bool $isManual = false): CmsRevision
    {
        $nextNumber = ($this->revisions()->max('revision_number') ?? 0) + 1;

        // Get the raw content value - encode if array
        $content = $this->getAttributes()['content'] ?? null;
        if (is_array($content)) {
            $content = json_encode($content);
        }

        return $this->revisions()->create([
            'user_id' => auth()->id(),
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $content,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'featured_image' => $this->featured_image,
            'additional_data' => $this->getAdditionalRevisionData(),
            'revision_number' => $nextNumber,
            'notes' => $notes,
            'is_manual' => $isManual,
            'content_hash' => $this->computeContentHash(),
        ]);
    }

    /**
     * Create a manual snapshot (bypasses throttling)
     */
    public function createManualSnapshot(?string $notes = null): CmsRevision
    {
        $revision = $this->createRevisionFromCurrent($notes ?? 'Manual snapshot', true);
        $this->clearRevisionCache();

        return $revision;
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
     *
     * Revision behavior on restore:
     * - Skip pre-update revision (the "before restore" state is already the latest post-update revision)
     * - Force post-update revision (bypass throttle to capture restored content as "current")
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

        // Skip pre-update revision (avoid redundant audit entry - current state is already latest revision)
        // Force post-update revision (bypass throttle to record the restored state)
        $this->skipPreUpdateRevision = true;
        $this->forcePostUpdateRevision = true;

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
     * Prune old revisions beyond the configured limits.
     *
     * Automatic and manual revisions have separate limits to prevent unbounded growth.
     */
    protected function pruneOldRevisions(): void
    {
        // Prune automatic revisions
        $autoLimit = config('tallcms.publishing.revision_limit');
        if ($autoLimit !== null) {
            $autoRevisionCount = $this->revisions()->where('is_manual', false)->count();

            if ($autoRevisionCount > $autoLimit) {
                $this->revisions()
                    ->where('is_manual', false)
                    ->orderBy('revision_number')
                    ->limit($autoRevisionCount - $autoLimit)
                    ->delete();
            }
        }

        // Prune manual snapshots (separate limit to prevent unbounded growth)
        $manualLimit = config('tallcms.publishing.revision_manual_limit');
        if ($manualLimit !== null) {
            $manualRevisionCount = $this->revisions()->where('is_manual', true)->count();

            if ($manualRevisionCount > $manualLimit) {
                $this->revisions()
                    ->where('is_manual', true)
                    ->orderBy('revision_number')
                    ->limit($manualRevisionCount - $manualLimit)
                    ->delete();
            }
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
