<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PluginLicense extends Model
{
    protected $table = 'tallcms_plugin_licenses';

    /**
     * Valid license statuses
     */
    public const VALID_STATUSES = ['active', 'expired', 'invalid', 'pending'];

    /**
     * Default attributes - license_source defaults to 'anystack'
     */
    protected $attributes = [
        'license_source' => 'anystack',
        'status' => 'pending',
    ];

    /**
     * Mass assignable attributes
     * Note: license_source is NOT fillable to prevent accidental overwrites
     */
    protected $fillable = [
        'plugin_slug',
        'license_key',
        'status',
        'domain',
        'activated_at',
        'expires_at',
        'last_validated_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    /**
     * Set the status attribute with validation
     */
    public function setStatusAttribute(string $value): void
    {
        if (! in_array($value, self::VALID_STATUSES)) {
            throw new InvalidArgumentException("Invalid license status: {$value}. Valid statuses are: ".implode(', ', self::VALID_STATUSES));
        }
        $this->attributes['status'] = $value;
    }

    /**
     * Explicitly set the license source
     * Use this method when you need to change the source (e.g., for marketplace licenses)
     */
    public function setLicenseSource(string $source): self
    {
        $this->attributes['license_source'] = $source;
        $this->save();

        return $this;
    }

    /**
     * Check if license is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if license expiration date has passed
     * Note: Use isHardExpired() to check if past the renewal grace period
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if license is within the renewal grace period
     * (expired but still within grace window for billing/webhook delays)
     */
    public function isWithinRenewalGracePeriod(int $graceDays = 14): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        // Not expired yet
        if ($this->expires_at->isFuture()) {
            return false;
        }

        // Check if within grace period after expiration
        return $this->expires_at->copy()->addDays($graceDays)->isFuture();
    }

    /**
     * Check if license is truly expired (past the renewal grace period)
     * This is when the license should be marked as 'expired' status
     */
    public function isHardExpired(int $graceDays = 14): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        // Past expiration + grace period
        return $this->expires_at->copy()->addDays($graceDays)->isPast();
    }

    /**
     * Check if license needs revalidation
     */
    public function needsRevalidation(int $cacheTtlSeconds = 86400): bool
    {
        if (! $this->last_validated_at) {
            return true;
        }

        // Use copy() to avoid mutating the original Carbon instance
        return $this->last_validated_at->copy()->addSeconds($cacheTtlSeconds)->isPast();
    }

    /**
     * Check if license is within offline grace period
     */
    public function isWithinGracePeriod(int $graceDays = 7): bool
    {
        if (! $this->last_validated_at) {
            return false;
        }

        // Use copy() to avoid mutating the original Carbon instance
        return $this->last_validated_at->copy()->addDays($graceDays)->isFuture();
    }

    /**
     * Mark license as validated
     */
    public function markValidated(): self
    {
        $this->last_validated_at = now();
        $this->save();

        return $this;
    }

    /**
     * Update license from API response
     */
    public function updateFromValidation(array $data): self
    {
        $this->status = $data['status'] ?? $this->status;
        $this->domain = $data['domain'] ?? $this->domain;
        $this->activated_at = $data['activated_at'] ?? $this->activated_at;
        $this->expires_at = $data['expires_at'] ?? $this->expires_at;
        $this->last_validated_at = now();

        if (isset($data['metadata'])) {
            $this->metadata = array_merge($this->metadata ?? [], $data['metadata']);
        }

        $this->save();

        return $this;
    }

    /**
     * Find license by plugin slug
     */
    public static function findByPluginSlug(string $pluginSlug): ?self
    {
        return static::where('plugin_slug', $pluginSlug)->first();
    }

    /**
     * Find or create a license record for a plugin
     */
    public static function findOrCreateForPlugin(string $pluginSlug): self
    {
        return static::firstOrCreate(
            ['plugin_slug' => $pluginSlug],
            ['status' => 'pending']
        );
    }

    /**
     * Scope to active licenses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to licenses needing validation
     */
    public function scopeNeedsValidation($query, int $cacheTtlSeconds = 86400)
    {
        return $query->where(function ($q) use ($cacheTtlSeconds) {
            $q->whereNull('last_validated_at')
                ->orWhere('last_validated_at', '<', now()->subSeconds($cacheTtlSeconds));
        });
    }

    /**
     * Get masked license key for display
     */
    public function getMaskedKeyAttribute(): string
    {
        if (empty($this->license_key)) {
            return '';
        }

        $key = $this->license_key;
        $length = strlen($key);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4).'...'.substr($key, -4);
    }
}
