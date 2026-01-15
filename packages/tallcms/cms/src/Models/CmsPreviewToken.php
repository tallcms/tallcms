<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CmsPreviewToken extends Model
{
    protected $table = 'tallcms_preview_tokens';

    protected $fillable = [
        'token',
        'tokenable_type',
        'tokenable_id',
        'created_by',
        'expires_at',
        'view_count',
        'max_views',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'view_count' => 'integer',
        'max_views' => 'integer',
    ];

    /**
     * Get the parent tokenable model (page or post)
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this token
     */
    public function creator(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'created_by');
    }

    /**
     * Check if the token is still valid
     *
     * @param  bool  $fresh  Whether to refresh from database first
     */
    public function isValid(bool $fresh = false): bool
    {
        if ($fresh) {
            $this->refresh();
        }

        // Check if expires_at is set and is a valid date
        if ($this->expires_at === null) {
            return false;
        }

        // Check expiry
        if ($this->isExpired()) {
            return false;
        }

        // Check max views
        if ($this->isOverViewLimit()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the token has expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the token has exceeded max views
     */
    public function isOverViewLimit(): bool
    {
        if ($this->max_views === null) {
            return false;
        }

        return $this->view_count >= $this->max_views;
    }

    /**
     * Increment the view count atomically and refresh model
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        // Refresh to get the actual value from database (handles race conditions)
        $this->refresh();
    }

    /**
     * Atomically check validity and consume a view in one operation.
     * Returns true if the view was successfully consumed, false if token is invalid.
     * This prevents race conditions where multiple requests could view past the limit.
     */
    public function consumeView(): bool
    {
        // Check expiry first (doesn't need atomic handling)
        if ($this->isExpired()) {
            return false;
        }

        // For tokens with no view limit, just increment
        if ($this->max_views === null) {
            $this->incrementViewCount();

            return true;
        }

        // Atomically increment only if under limit
        // This prevents race conditions
        $affected = static::where('id', $this->id)
            ->where(function ($query) {
                $query->whereNull('max_views')
                    ->orWhereColumn('view_count', '<', 'max_views');
            })
            ->update(['view_count' => DB::raw('view_count + 1')]);

        if ($affected > 0) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Generate a secure random token
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Get the preview URL for this token
     */
    public function getPreviewUrl(): string
    {
        return route('preview.token', ['token' => $this->token]);
    }

    /**
     * Get remaining views (null if unlimited)
     */
    public function getRemainingViews(): ?int
    {
        if ($this->max_views === null) {
            return null;
        }

        return max(0, $this->max_views - $this->view_count);
    }

    /**
     * Get time until expiry
     */
    public function getTimeUntilExpiry(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Scope for valid (non-expired, under view limit) tokens
     */
    public function scopeValid($query)
    {
        return $query
            ->where('expires_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('max_views')
                    ->orWhereColumn('view_count', '<', 'max_views');
            });
    }

    /**
     * Scope for expired or over-limit tokens
     */
    public function scopeInvalid($query)
    {
        return $query
            ->where(function ($q) {
                $q->where('expires_at', '<=', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('max_views')
                            ->whereColumn('view_count', '>=', 'max_views');
                    });
            });
    }

    /**
     * Find a token by its string value
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }
}
