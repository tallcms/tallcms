<?php

namespace Tallcms\Pro\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tallcms\Pro\Models\ProSetting;
use Tallcms\Pro\Services\AnystackClient;
use Tallcms\Pro\Services\LicenseService;

class WebhookController
{
    /**
     * Handle incoming webhooks from Anystack
     */
    public function handleAnystack(Request $request): JsonResponse
    {
        // Get the webhook secret from settings or env
        $webhookSecret = ProSetting::get('anystack_webhook_secret')
            ?: config('tallcms-pro.anystack.webhook_secret');

        // Fail closed: reject all webhooks if no secret is configured
        if (empty($webhookSecret)) {
            Log::warning('TallCMS Pro: Webhook rejected - no secret configured');

            return response()->json(['error' => 'Webhook not configured'], 403);
        }

        // Verify signature
        $signature = $request->header('X-Anystack-Signature', '');
        $payload = $request->getContent();

        $client = app(AnystackClient::class);

        if (! $client->verifyWebhookSignature($payload, $signature, $webhookSecret)) {
            Log::warning('TallCMS Pro: Invalid webhook signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse the webhook payload
        $event = $request->input('event');
        $data = $request->input('data', []);

        if (! $event) {
            return response()->json(['error' => 'Missing event'], 400);
        }

        Log::info('TallCMS Pro: Webhook received', [
            'event' => $event,
        ]);

        // Handle the webhook
        try {
            $licenseService = app(LicenseService::class);
            $licenseService->handleWebhook($data, $event);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('TallCMS Pro: Webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
