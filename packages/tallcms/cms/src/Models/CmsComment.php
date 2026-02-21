<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;
use TallCms\Cms\Notifications\CommentApprovedNotification;
use TallCms\Cms\Support\NotificationDispatcher;

class CmsComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tallcms_comments';

    protected $fillable = [
        'post_id',
        'parent_id',
        'user_id',
        'author_name',
        'author_email',
        'content',
        'status',
        'approved_by',
        'approved_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            if (empty($comment->user_id) && auth()->check()) {
                $comment->user_id = auth()->id();
            }
        });
    }

    // Relationships

    public function post(): BelongsTo
    {
        return $this->belongsTo(CmsPost::class, 'post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function approvedReplies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('status', 'approved');
    }

    public function user(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'user_id');
    }

    public function approvedBy(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'approved_by');
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeSpam(Builder $query): Builder
    {
        return $query->where('status', 'spam');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Moderation methods

    public function approve($approver): bool
    {
        $result = $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        if ($result && config('tallcms.comments.notify_on_approval', true)) {
            if ($this->user_id && $this->user) {
                NotificationDispatcher::send($this->user, new CommentApprovedNotification($this));
            } elseif (! $this->user_id && $this->getAuthorEmail()) {
                Notification::route('mail', $this->getAuthorEmail())
                    ->notify(new CommentApprovedNotification($this));
            }
        }

        return $result;
    }

    public function reject(): bool
    {
        return $this->update(['status' => 'rejected']);
    }

    public function unreject(): bool
    {
        return $this->update(['status' => 'pending']);
    }

    public function markAsSpam(): bool
    {
        return $this->update(['status' => 'spam']);
    }

    public function unmarkSpam(): bool
    {
        return $this->update(['status' => 'pending']);
    }

    // Status checks

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSpam(): bool
    {
        return $this->status === 'spam';
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    // Author helpers

    public function getAuthorName(): ?string
    {
        return $this->user?->name ?? $this->author_name;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->user?->email ?? $this->author_email;
    }
}
