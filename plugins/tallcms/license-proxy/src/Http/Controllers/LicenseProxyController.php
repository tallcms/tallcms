<?php

namespace Tallcms\LicenseProxy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LicenseProxyController extends Controller
{
    protected string $apiUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('license-proxy.anystack.api_url'), '/');
        $this->apiKey = config('license-proxy.anystack.api_key', '');
    }

    /**
     * Get product ID for a plugin slug
     * Returns null if plugin is not supported
     */
    protected function getProductId(string $pluginSlug): ?string
    {
        return config("license-proxy.products.{$pluginSlug}");
    }

    /**
     * Validate plugin_slug and get product ID
     * Returns product ID or null with appropriate logging
     */
    protected function resolveProductId(Request $request, string $pluginSlug): ?string
    {
        $productId = $this->getProductId($pluginSlug);

        if (! $productId) {
            Log::warning('License Proxy: Unknown plugin_slug attempted', [
                'plugin_slug' => $pluginSlug,
                'ip' => $request->ip(),
                'domain' => $request->input('domain'),
            ]);
        }

        return $productId;
    }

    /**
     * Activate a license key
     *
     * POST /license-proxy/activate
     * Body: { "license_key": "xxx", "domain": "customer.com", "plugin_slug": "tallcms/pro" }
     */
    public function activate(Request $request): JsonResponse
    {
        // Rate limiting
        if ($this->isRateLimited($request)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        // Validate input
        $validated = $request->validate([
            'license_key' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'plugin_slug' => 'required|string|max:100',
        ]);

        // Resolve product ID from plugin slug
        $productId = $this->resolveProductId($request, $validated['plugin_slug']);
        if (! $productId) {
            return $this->errorResponse('Not found', 404);
        }

        // Check API key configuration
        if (empty($this->apiKey)) {
            Log::error('License Proxy: ANYSTACK_API_KEY not configured');

            return $this->errorResponse('License server configuration error', 500);
        }

        try {
            // Call Anystack API
            $url = "{$this->apiUrl}/v1/products/{$productId}/licenses/activate-key";

            Log::info('License Proxy: Activate request', [
                'url' => $url,
                'plugin_slug' => $validated['plugin_slug'],
                'domain' => $validated['domain'],
            ]);

            $response = Http::timeout(15)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post($url, [
                    'key' => $validated['license_key'],
                    'fingerprint' => $validated['domain'],
                    'hostname' => $validated['domain'],
                ]);

            Log::info('License Proxy: Activate response', [
                'plugin_slug' => $validated['plugin_slug'],
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json('data', []);

                return response()->json([
                    'success' => true,
                    'message' => 'License activated successfully',
                    'data' => [
                        'license_key' => $validated['license_key'],
                        'plugin_slug' => $validated['plugin_slug'],
                        'domain' => $validated['domain'],
                        'activated_at' => now()->toIso8601String(),
                        'expires_at' => $data['license']['expires_at'] ?? null,
                    ],
                ]);
            }

            // Handle specific error cases
            $errorMessage = $response->json('message', 'License activation failed');
            $statusCode = $response->status();

            if ($statusCode === 404) {
                return $this->errorResponse('Invalid license key', 404);
            }

            if ($statusCode === 422) {
                // Could be activation limit reached
                return $this->errorResponse($errorMessage, 422);
            }

            return $this->errorResponse($errorMessage, $statusCode);

        } catch (\Exception $e) {
            Log::error('License Proxy: Activate exception', [
                'plugin_slug' => $validated['plugin_slug'],
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Unable to connect to license server', 503);
        }
    }

    /**
     * Validate a license key
     *
     * POST /license-proxy/validate
     * Body: { "license_key": "xxx", "domain": "customer.com", "plugin_slug": "tallcms/pro" }
     */
    public function validate(Request $request): JsonResponse
    {
        // Rate limiting
        if ($this->isRateLimited($request)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        // Validate input
        $validated = $request->validate([
            'license_key' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'plugin_slug' => 'required|string|max:100',
        ]);

        // Resolve product ID from plugin slug
        $productId = $this->resolveProductId($request, $validated['plugin_slug']);
        if (! $productId) {
            return $this->errorResponse('Not found', 404);
        }

        if (empty($this->apiKey)) {
            Log::error('License Proxy: ANYSTACK_API_KEY not configured');

            return $this->errorResponse('License server configuration error', 500);
        }

        try {
            // Get license from Anystack
            $url = "{$this->apiUrl}/v1/products/{$productId}/licenses/{$validated['license_key']}";

            $response = Http::timeout(15)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                return $this->errorResponse('Invalid license key', 404);
            }

            $data = $response->json('data', []);
            $status = $data['status'] ?? 'inactive';
            $activations = $data['activations'] ?? [];

            // Check if this domain is activated
            $domainActivated = false;
            foreach ($activations as $activation) {
                if (($activation['fingerprint'] ?? '') === $validated['domain'] ||
                    ($activation['hostname'] ?? '') === $validated['domain']) {
                    $domainActivated = true;
                    break;
                }
            }

            return response()->json([
                'success' => true,
                'valid' => $status === 'active' && $domainActivated,
                'status' => $status,
                'domain_activated' => $domainActivated,
                'data' => [
                    'license_key' => $validated['license_key'],
                    'plugin_slug' => $validated['plugin_slug'],
                    'domain' => $validated['domain'],
                    'expires_at' => $data['expires_at'] ?? null,
                    'activation_limit' => $data['activation_limit'] ?? null,
                    'activation_count' => count($activations),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('License Proxy: Validate exception', [
                'plugin_slug' => $validated['plugin_slug'],
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Unable to connect to license server', 503);
        }
    }

    /**
     * Deactivate a license key for a domain
     *
     * POST /license-proxy/deactivate
     * Body: { "license_key": "xxx", "domain": "customer.com", "plugin_slug": "tallcms/pro" }
     */
    public function deactivate(Request $request): JsonResponse
    {
        // Rate limiting
        if ($this->isRateLimited($request)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        // Validate input
        $validated = $request->validate([
            'license_key' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'plugin_slug' => 'required|string|max:100',
        ]);

        // Resolve product ID from plugin slug
        $productId = $this->resolveProductId($request, $validated['plugin_slug']);
        if (! $productId) {
            return $this->errorResponse('Not found', 404);
        }

        if (empty($this->apiKey)) {
            Log::error('License Proxy: ANYSTACK_API_KEY not configured');

            return $this->errorResponse('License server configuration error', 500);
        }

        try {
            // First get the license to find the activation ID
            $url = "{$this->apiUrl}/v1/products/{$productId}/licenses/{$validated['license_key']}";

            $response = Http::timeout(15)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                return $this->errorResponse('Invalid license key', 404);
            }

            $data = $response->json('data', []);
            $activations = $data['activations'] ?? [];

            // Find activation for this domain
            $activationId = null;
            foreach ($activations as $activation) {
                if (($activation['fingerprint'] ?? '') === $validated['domain'] ||
                    ($activation['hostname'] ?? '') === $validated['domain']) {
                    $activationId = $activation['id'] ?? null;
                    break;
                }
            }

            if (! $activationId) {
                return response()->json([
                    'success' => true,
                    'message' => 'No activation found for this domain',
                ]);
            }

            // Delete the activation
            $deleteUrl = "{$this->apiUrl}/v1/products/{$productId}/licenses/{$validated['license_key']}/activations/{$activationId}";

            $deleteResponse = Http::timeout(15)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->delete($deleteUrl);

            if ($deleteResponse->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'License deactivated successfully',
                ]);
            }

            return $this->errorResponse('Failed to deactivate license', $deleteResponse->status());

        } catch (\Exception $e) {
            Log::error('License Proxy: Deactivate exception', [
                'plugin_slug' => $validated['plugin_slug'],
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Unable to connect to license server', 503);
        }
    }

    /**
     * Check rate limiting
     */
    protected function isRateLimited(Request $request): bool
    {
        $key = 'license-proxy:'.$request->ip();
        $limit = config('license-proxy.rate_limit', 10);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
