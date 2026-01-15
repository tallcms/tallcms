<?php

declare(strict_types=1);

namespace TallCms\Cms\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use TallCms\Cms\Models\CmsPreviewToken;

trait HasPreviewTokens
{
    /**
     * Get all preview tokens for this model
     */
    public function previewTokens(): MorphMany
    {
        return $this->morphMany(CmsPreviewToken::class, 'tokenable');
    }

    /**
     * Get active (valid) preview tokens
     */
    public function activePreviewTokens(): MorphMany
    {
        return $this->previewTokens()->valid();
    }

    /**
     * Create a new preview token
     */
    public function createPreviewToken(Carbon $expiresAt, ?int $maxViews = null): CmsPreviewToken
    {
        return $this->previewTokens()->create([
            'token' => CmsPreviewToken::generateToken(),
            'created_by' => auth()->id(),
            'expires_at' => $expiresAt,
            'max_views' => $maxViews,
        ]);
    }

    /**
     * Create a preview token with default expiry from config
     */
    public function createDefaultPreviewToken(?int $maxViews = null): CmsPreviewToken
    {
        $hours = config('tallcms.publishing.default_preview_expiry_hours', 24);

        return $this->createPreviewToken(now()->addHours($hours), $maxViews);
    }

    /**
     * Revoke a specific preview token
     */
    public function revokePreviewToken(int $tokenId): bool
    {
        return $this->previewTokens()
            ->where('id', $tokenId)
            ->delete() > 0;
    }

    /**
     * Revoke all preview tokens for this model
     */
    public function revokeAllPreviewTokens(): int
    {
        return $this->previewTokens()->delete();
    }

    /**
     * Check if there are any active preview tokens
     */
    public function hasActivePreviewTokens(): bool
    {
        return $this->activePreviewTokens()->exists();
    }

    /**
     * Get the count of active preview tokens
     */
    public function getActivePreviewTokenCount(): int
    {
        return $this->activePreviewTokens()->count();
    }
}
