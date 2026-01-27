<?php

declare(strict_types=1);

namespace TallCms\Cms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use TallCms\Cms\Exceptions\WebhookDeliveryException;
use TallCms\Cms\Models\Webhook;
use TallCms\Cms\Models\WebhookDelivery;
use TallCms\Cms\Services\WebhookUrlValidator;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1; // We handle retries manually

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Webhook $webhook,
        public array $payload,
        public string $deliveryId,
        public int $attempt = 1
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookUrlValidator $validator): void
    {
        // Add delivery metadata to payload
        $maxAttempts = config('tallcms.webhooks.max_retries', 3);
        $this->payload['id'] = $this->deliveryId;
        $this->payload['attempt'] = $this->attempt;
        $this->payload['max_attempts'] = $maxAttempts;

        $startTime = microtime(true);
        $statusCode = null;
        $responseBody = null;
        $responseHeaders = null;
        $success = false;
        $error = null;

        try {
            // Re-validate URL at delivery time (DNS rebinding protection)
            $resolvedIps = $validator->validateAtDelivery($this->webhook->url);

            // Prepare the payload JSON
            $payloadJson = json_encode($this->payload, JSON_THROW_ON_ERROR);

            // Generate signature
            $signature = $this->webhook->generateSignature($payloadJson);

            // Build CURL options for IP pinning
            $host = parse_url($this->webhook->url, PHP_URL_HOST);
            $curlOptions = [
                CURLOPT_RESOLVE => [
                    "{$host}:443:{$resolvedIps[0]}",
                ],
            ];

            // Make the HTTP request
            $response = Http::timeout($this->webhook->timeout)
                ->withOptions(['curl' => $curlOptions])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-TallCMS-Event' => $this->payload['event'],
                    'X-TallCMS-Signature' => $signature,
                    'X-TallCMS-Delivery' => $this->deliveryId,
                    'X-TallCMS-Attempt' => (string) $this->attempt,
                    'User-Agent' => 'TallCMS-Webhook/1.0',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($this->webhook->url);

            $statusCode = $response->status();
            $responseHeaders = $response->headers();
            $responseBody = $this->truncateResponse($response->body());

            // Consider 2xx responses as success
            $success = $response->successful();

            if (! $success) {
                $error = "HTTP {$statusCode}";
            }
        } catch (WebhookDeliveryException $e) {
            $error = $e->getMessage();
            $statusCode = $e->getStatusCode();
            $responseBody = $e->getResponseBody();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // Calculate next retry time if needed
        $nextRetryAt = null;
        if (! $success && $this->attempt < $maxAttempts) {
            $backoff = config('tallcms.webhooks.retry_backoff', [60, 300, 900]);
            $delay = $backoff[$this->attempt - 1] ?? 900;
            $nextRetryAt = now()->addSeconds($delay);
        }

        // Log the delivery (unique on delivery_id + attempt composite key)
        $delivery = WebhookDelivery::create([
            'delivery_id' => $this->deliveryId,
            'webhook_id' => $this->webhook->id,
            'event' => $this->payload['event'],
            'payload' => $this->payload,
            'attempt' => $this->attempt,
            'status_code' => $statusCode,
            'response_body' => $responseBody,
            'response_headers' => $responseHeaders,
            'duration_ms' => $durationMs,
            'success' => $success,
            'next_retry_at' => $nextRetryAt,
        ]);

        // Schedule retry if needed
        if (! $success && $this->attempt < $maxAttempts) {
            $backoff = config('tallcms.webhooks.retry_backoff', [60, 300, 900]);
            $delay = $backoff[$this->attempt - 1] ?? 900;

            self::dispatch(
                $this->webhook,
                $this->payload,
                $this->deliveryId,
                $this->attempt + 1
            )->delay(now()->addSeconds($delay))
                ->onQueue(config('tallcms.webhooks.queue', 'default'));
        }
    }

    /**
     * Truncate response body to configured max size.
     */
    protected function truncateResponse(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $maxSize = config('tallcms.webhooks.response_max_size', 10000);

        if (strlen($body) <= $maxSize) {
            return $body;
        }

        return substr($body, 0, $maxSize).'... [truncated]';
    }
}
