<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use TallCms\Cms\Jobs\DispatchWebhook;
use TallCms\Cms\Models\Webhook;

class WebhookDispatcher
{
    /**
     * Dispatch webhooks for an event.
     *
     * @param  array<string, mixed>|null  $additionalData
     */
    public function dispatch(string $event, Model $model, ?array $additionalData = null): void
    {
        if (! config('tallcms.webhooks.enabled', false)) {
            return;
        }

        // Find all active webhooks subscribed to this event
        $webhooks = Webhook::where('is_active', true)
            ->get()
            ->filter(fn (Webhook $webhook) => $webhook->subscribedTo($event));

        if ($webhooks->isEmpty()) {
            return;
        }

        // Build the payload
        $payload = $this->buildPayload($event, $model, $additionalData);

        // Queue webhook delivery for each subscription
        foreach ($webhooks as $webhook) {
            $deliveryId = $this->generateDeliveryId();

            DispatchWebhook::dispatch($webhook, $payload, $deliveryId)
                ->onQueue(config('tallcms.webhooks.queue', 'default'));
        }
    }

    /**
     * Build the webhook payload.
     *
     * @param  array<string, mixed>|null  $additionalData
     * @return array<string, mixed>
     */
    protected function buildPayload(string $event, Model $model, ?array $additionalData = null): array
    {
        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $model->getKey(),
                'type' => $this->getModelType($model),
                'attributes' => $this->getModelAttributes($model),
            ],
        ];

        // Add triggered_by if authenticated
        if (auth()->check()) {
            $payload['meta'] = [
                'triggered_by' => [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name ?? null,
                ],
            ];
        }

        // Merge additional data
        if ($additionalData) {
            $payload['data'] = array_merge($payload['data'], $additionalData);
        }

        return $payload;
    }

    /**
     * Get the model type for the payload.
     */
    protected function getModelType(Model $model): string
    {
        $class = class_basename($model);

        return match ($class) {
            'CmsPage' => 'page',
            'CmsPost' => 'post',
            'CmsCategory' => 'category',
            'TallcmsMedia' => 'media',
            'MediaCollection' => 'media_collection',
            default => Str::snake($class),
        };
    }

    /**
     * Get the model attributes for the payload.
     *
     * @return array<string, mixed>
     */
    protected function getModelAttributes(Model $model): array
    {
        // Get safe attributes (exclude sensitive data)
        $hidden = ['password', 'remember_token', 'secret'];
        $attributes = collect($model->toArray())
            ->except($hidden)
            ->toArray();

        // Convert dates to ISO8601
        foreach (['created_at', 'updated_at', 'published_at', 'deleted_at'] as $dateField) {
            if (isset($attributes[$dateField]) && $attributes[$dateField]) {
                $attributes[$dateField] = $model->{$dateField}?->toIso8601String();
            }
        }

        return $attributes;
    }

    /**
     * Generate a unique delivery ID.
     */
    protected function generateDeliveryId(): string
    {
        return 'wh_del_'.Str::random(16);
    }
}
