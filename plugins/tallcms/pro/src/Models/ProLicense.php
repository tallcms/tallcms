<?php

namespace Tallcms\Pro\Models;

use Illuminate\Database\Eloquent\Model;

class ProLicense extends Model
{
    protected $table = 'tallcms_pro_licenses';

    protected $fillable = [
        'license_key',
        'status',
        'domain',
        'activated_at',
        'expires_at',
        'last_validated_at',
        'validation_response',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_validated_at' => 'datetime',
        'validation_response' => 'array',
    ];

    /**
     * Check if the license is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the license is expired
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
     * Check if the cached validation is still fresh
     */
    public function isCacheFresh(): bool
    {
        if (! $this->last_validated_at) {
            return false;
        }

        $cacheTtl = config('tallcms-pro.license.cache_ttl', 86400);

        return $this->last_validated_at->addSeconds($cacheTtl)->isFuture();
    }

    /**
     * Check if we're within the offline grace period
     */
    public function isWithinGracePeriod(): bool
    {
        if (! $this->last_validated_at) {
            return false;
        }

        $graceDays = config('tallcms-pro.license.offline_grace_days', 7);

        return $this->last_validated_at->addDays($graceDays)->isFuture();
    }

    /**
     * Get the current license (singleton pattern)
     */
    public static function current(): ?self
    {
        return static::first();
    }

    /**
     * Update the validation response cache
     */
    public function updateValidation(array $response, string $status): self
    {
        $data = [
            'status' => $status,
            'last_validated_at' => now(),
            'validation_response' => $response,
        ];

        // Update expires_at if present in response (handles renewals)
        if (isset($response['expires_at'])) {
            $data['expires_at'] = \Carbon\Carbon::parse($response['expires_at']);
        }

        $this->update($data);

        return $this;
    }

    /**
     * Activate the license
     */
    public function activate(array $response): self
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
            'last_validated_at' => now(),
            'expires_at' => isset($response['expires_at']) ? \Carbon\Carbon::parse($response['expires_at']) : null,
            'validation_response' => $response,
            'domain' => request()->getHost(),
        ]);

        return $this;
    }
}
