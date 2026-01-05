<?php

namespace App\Models;

use App\Services\ContentDiffService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CmsRevision extends Model
{
    protected $table = 'tallcms_revisions';

    protected $fillable = [
        'revisionable_type',
        'revisionable_id',
        'user_id',
        'title',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'featured_image',
        'additional_data',
        'revision_number',
        'notes',
    ];

    protected $casts = [
        // Note: 'content' is NOT cast - it stores raw value from parent model
        // (could be JSON string for tiptap or HTML string for legacy content)
        'additional_data' => 'array',
    ];

    /**
     * Get the parent revisionable model (page or post)
     */
    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this revision
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Compare this revision with another and return differences
     */
    public function diffWith(CmsRevision $other): array
    {
        $diffService = app(ContentDiffService::class);

        return [
            'title' => $this->diffField('title', $other),
            'excerpt' => $this->diffField('excerpt', $other),
            'meta_title' => $this->diffField('meta_title', $other),
            'meta_description' => $this->diffField('meta_description', $other),
            'featured_image' => $this->diffField('featured_image', $other),
            'content' => $diffService->diff(
                $other->content ?? [],
                $this->content ?? []
            ),
        ];
    }

    /**
     * Compare a single field between revisions
     */
    protected function diffField(string $field, CmsRevision $other): ?array
    {
        if ($this->{$field} === $other->{$field}) {
            return null;
        }

        return [
            'old' => $other->{$field},
            'new' => $this->{$field},
        ];
    }

    /**
     * Get a summary of changes for display
     */
    public function getChangeSummary(): string
    {
        $changes = [];

        // Compare with previous revision if exists
        $previous = static::where('revisionable_type', $this->revisionable_type)
            ->where('revisionable_id', $this->revisionable_id)
            ->where('revision_number', '<', $this->revision_number)
            ->orderByDesc('revision_number')
            ->first();

        if (! $previous) {
            return 'Initial version';
        }

        if ($this->title !== $previous->title) {
            $changes[] = 'title';
        }
        if ($this->excerpt !== $previous->excerpt) {
            $changes[] = 'excerpt';
        }
        if ($this->content !== $previous->content) {
            $changes[] = 'content';
        }
        if ($this->meta_title !== $previous->meta_title) {
            $changes[] = 'meta title';
        }
        if ($this->meta_description !== $previous->meta_description) {
            $changes[] = 'meta description';
        }
        if ($this->featured_image !== $previous->featured_image) {
            $changes[] = 'featured image';
        }

        return empty($changes) ? 'No changes' : 'Changed: '.implode(', ', $changes);
    }
}
