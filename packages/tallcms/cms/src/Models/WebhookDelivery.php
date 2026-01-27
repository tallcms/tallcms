<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'delivery_id',
        'webhook_id',
        'event',
        'payload',
        'attempt',
        'status_code',
        'response_body',
        'response_headers',
        'duration_ms',
        'success',
        'next_retry_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'response_headers' => 'array',
        'attempt' => 'integer',
        'status_code' => 'integer',
        'duration_ms' => 'integer',
        'success' => 'boolean',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the table name with prefix.
     */
    public function getTable(): string
    {
        return config('tallcms.database.prefix', 'tallcms_').'webhook_deliveries';
    }

    /**
     * Get the webhook this delivery belongs to.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Check if this delivery can be retried.
     */
    public function canRetry(): bool
    {
        $maxRetries = config('tallcms.webhooks.max_retries', 3);

        return ! $this->success && $this->attempt < $maxRetries;
    }

    /**
     * Get the delay for the next retry attempt.
     */
    public function getRetryDelay(): int
    {
        $backoff = config('tallcms.webhooks.retry_backoff', [60, 300, 900]);

        // Use the delay for the current attempt (0-indexed)
        $index = min($this->attempt, count($backoff) - 1);

        return $backoff[$index] ?? 900;
    }
}
