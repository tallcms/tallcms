<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TallCms\Cms\Http\Controllers\Api\V1\Concerns\HandlesPagination;
use TallCms\Cms\Http\Requests\Api\V1\StoreWebhookRequest;
use TallCms\Cms\Http\Requests\Api\V1\UpdateWebhookRequest;
use TallCms\Cms\Http\Resources\Api\V1\WebhookResource;
use TallCms\Cms\Models\Webhook;
use TallCms\Cms\Services\WebhookDispatcher;

class WebhookController extends Controller
{
    use HandlesPagination;

    /**
     * List all webhooks.
     *
     * @authenticated
     *
     * @group Webhooks
     *
     * @queryParam page int Page number. Example: 1
     * @queryParam per_page int Items per page (max 100). Example: 15
     *
     * @response 200 {"data": [...], "meta": {...}, "links": {...}}
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 15), 100))
            ->withQueryString();

        return response()->json([
            'data' => WebhookResource::collection($webhooks),
            'meta' => $this->paginationMeta($webhooks),
            'links' => $this->paginationLinks($webhooks),
        ]);
    }

    /**
     * Get a specific webhook.
     *
     * @authenticated
     *
     * @group Webhooks
     */
    public function show(int $webhook): JsonResponse
    {
        $webhookModel = Webhook::with(['creator', 'recentDeliveries'])->findOrFail($webhook);

        return $this->respondWithData(new WebhookResource($webhookModel));
    }

    /**
     * Create a new webhook.
     *
     * @authenticated
     *
     * @group Webhooks
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = Webhook::create([
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'events' => $request->validated('events'),
            'is_active' => $request->validated('is_active', true),
            'timeout' => $request->validated('timeout', config('tallcms.webhooks.timeout', 30)),
            'created_by' => $request->user()->id,
        ]);

        return $this->respondCreated(new WebhookResource($webhook->fresh(['creator'])));
    }

    /**
     * Update a webhook.
     *
     * @authenticated
     *
     * @group Webhooks
     */
    public function update(UpdateWebhookRequest $request, int $webhook): JsonResponse
    {
        $webhookModel = Webhook::findOrFail($webhook);

        $webhookModel->update($request->validated());

        return $this->respondWithData(new WebhookResource($webhookModel->fresh(['creator'])));
    }

    /**
     * Delete a webhook.
     *
     * @authenticated
     *
     * @group Webhooks
     */
    public function destroy(int $webhook): JsonResponse
    {
        $webhookModel = Webhook::findOrFail($webhook);

        $webhookModel->delete();

        return $this->respondWithMessage('Webhook deleted successfully');
    }

    /**
     * Send a test webhook.
     *
     * @authenticated
     *
     * @group Webhooks
     */
    public function test(Request $request, int $webhook, WebhookDispatcher $dispatcher): JsonResponse
    {
        $webhookModel = Webhook::findOrFail($webhook);

        // Create a test payload
        $testPayload = [
            'event' => 'test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => 0,
                'type' => 'test',
                'attributes' => [
                    'message' => 'This is a test webhook delivery',
                ],
            ],
            'meta' => [
                'triggered_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                ],
            ],
        ];

        // Dispatch test webhook directly (not queued)
        \TallCms\Cms\Jobs\DispatchWebhook::dispatchSync(
            $webhookModel,
            $testPayload,
            'wh_test_'.\Illuminate\Support\Str::uuid()->toString()
        );

        return $this->respondWithMessage('Test webhook sent successfully');
    }
}
