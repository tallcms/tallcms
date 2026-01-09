<?php

namespace Tallcms\Pro\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnystackClient
{
    /**
     * Test license key for development/testing
     * Only works when APP_ENV is local or testing
     */
    public const TEST_LICENSE_KEY = 'TALLCMS-PRO-TEST-LICENSE';

    protected string $apiUrl;

    protected ?string $productId;

    public function __construct(string $apiUrl, ?string $productId = null)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->productId = $productId;
    }

    /**
     * Check if the given key is a valid test license
     */
    protected function isTestLicense(string $licenseKey): bool
    {
        return $licenseKey === self::TEST_LICENSE_KEY
            && in_array(app()->environment(), ['local', 'testing'], true);
    }

    /**
     * Get a mock response for test license
     */
    protected function getTestLicenseResponse(): array
    {
        return [
            'valid' => true,
            'status' => 'active',
            'message' => 'Test license activated (development only)',
            'data' => [
                'license_key' => self::TEST_LICENSE_KEY,
                'status' => 'active',
                'expires_at' => now()->addYear()->toIso8601String(),
                'is_test' => true,
            ],
        ];
    }

    /**
     * Validate a license key with Anystack
     *
     * @return array{valid: bool, status: string, message: string, data: array}
     */
    public function validateLicense(string $licenseKey, ?string $domain = null): array
    {
        // Allow test license in development
        if ($this->isTestLicense($licenseKey)) {
            return $this->getTestLicenseResponse();
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiUrl}/v1/licenses/validate", [
                    'license_key' => $licenseKey,
                    'product_id' => $this->productId,
                    'domain' => $domain ?? request()->getHost(),
                ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Anystack license validation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'status' => 'error',
                'message' => 'Unable to connect to license server',
                'data' => [],
            ];
        }
    }

    /**
     * Activate a license key
     *
     * @return array{valid: bool, status: string, message: string, data: array}
     */
    public function activateLicense(string $licenseKey, ?string $domain = null): array
    {
        // Allow test license in development
        if ($this->isTestLicense($licenseKey)) {
            return $this->getTestLicenseResponse();
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiUrl}/v1/licenses/activate", [
                    'license_key' => $licenseKey,
                    'product_id' => $this->productId,
                    'domain' => $domain ?? request()->getHost(),
                    'device_name' => config('app.name', 'TallCMS'),
                ]);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::error('Anystack license activation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'status' => 'error',
                'message' => 'Unable to connect to license server',
                'data' => [],
            ];
        }
    }

    /**
     * Deactivate a license key
     *
     * @return array{success: bool, message: string}
     */
    public function deactivateLicense(string $licenseKey, ?string $domain = null): array
    {
        // Allow test license deactivation in development
        if ($this->isTestLicense($licenseKey)) {
            return [
                'success' => true,
                'message' => 'Test license deactivated',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiUrl}/v1/licenses/deactivate", [
                    'license_key' => $licenseKey,
                    'product_id' => $this->productId,
                    'domain' => $domain ?? request()->getHost(),
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'License deactivated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message', 'Deactivation failed'),
            ];
        } catch (\Exception $e) {
            Log::error('Anystack license deactivation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to connect to license server',
            ];
        }
    }

    /**
     * Parse the API response into a standard format
     */
    protected function parseResponse(Response $response): array
    {
        $data = $response->json() ?? [];

        if ($response->successful() && ($data['valid'] ?? false)) {
            return [
                'valid' => true,
                'status' => 'active',
                'message' => 'License is valid',
                'data' => $data,
            ];
        }

        // Handle specific error cases
        $status = match (true) {
            ($data['status'] ?? '') === 'expired' => 'expired',
            ($data['status'] ?? '') === 'suspended' => 'invalid',
            $response->status() === 404 => 'invalid',
            $response->status() === 422 => 'invalid',
            default => 'invalid',
        };

        return [
            'valid' => false,
            'status' => $status,
            'message' => $data['message'] ?? 'License validation failed',
            'data' => $data,
        ];
    }
}
