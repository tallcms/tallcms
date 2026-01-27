<?php

declare(strict_types=1);

namespace TallCms\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    /**
     * Available webhook events.
     */
    public const EVENTS = [
        'page.created',
        'page.updated',
        'page.published',
        'page.unpublished',
        'page.deleted',
        'page.restored',
        'post.created',
        'post.updated',
        'post.published',
        'post.unpublished',
        'post.deleted',
        'post.restored',
        'category.created',
        'category.updated',
        'category.deleted',
        'media.created',
        'media.updated',
        'media.deleted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'timeout',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'timeout' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the table name with prefix.
     */
    public function getTable(): string
    {
        return config('tallcms.database.prefix', 'tallcms_').'webhooks';
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Webhook $webhook) {
            // Generate secret if not provided
            if (empty($webhook->secret)) {
                $webhook->secret = bin2hex(random_bytes(32));
            }
        });
    }

    /**
     * Get the user who created this webhook.
     */
    public function creator(): BelongsTo
    {
        $userModel = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'created_by');
    }

    /**
     * Get the delivery logs for this webhook.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Get recent deliveries for this webhook.
     */
    public function recentDeliveries(): HasMany
    {
        return $this->deliveries()->latest()->limit(10);
    }

    /**
     * Check if this webhook is subscribed to a specific event.
     */
    public function subscribedTo(string $event): bool
    {
        return in_array($event, $this->events, true) || in_array('*', $this->events, true);
    }

    /**
     * Generate the signature for a payload.
     */
    public function generateSignature(string $payload): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $this->secret);
    }
}
