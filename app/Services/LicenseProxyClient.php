<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseProxyClient
{
    /**
     * Test license key prefix for development/testing
     * Full format: TALLCMS-{PRODUCT}-TEST-LICENSE
     * Example: TALLCMS-PRO-TEST-LICENSE
     * Only works when APP_ENV is local or testing
     */
    public const TEST_LICENSE_PREFIX = 'TALLCMS-';

    public const TEST_LICENSE_SUFFIX = '-TEST-LICENSE';

    protected string $proxyUrl;

    public function __construct(string $proxyUrl)
    {
        $this->proxyUrl = rtrim($proxyUrl, '/');
    }

    /**
     * Activate a license for a plugin
     *
     * @return array{valid: bool, status: string, message: string, data: array}
     */
    public function activate(string $pluginSlug, string $licenseKey, string $domain): array
    {
        if ($this->isTestLicense($pluginSlug, $licenseKey)) {
            return $this->getTestLicenseResponse($pluginSlug);
        }

        try {
            $url = "{$this->proxyUrl}/license-proxy/activate";
            $payload = [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'plugin_slug' => $pluginSlug,
            ];

            Log::info('LicenseProxyClient: Activate request', [
                'url' => $url,
                'plugin_slug' => $pluginSlug,
                'domain' => $domain,
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->post($url, $payload);

            Log::info('LicenseProxyClient: Activate response', [
                'plugin_slug' => $pluginSlug,
                'status' => $response->status(),
                'success' => $response->json('success'),
            ]);

            return $this->parseActivateResponse($response);

        } catch (\Exception $e) {
            Log::error('LicenseProxyClient: Activate exception', [
                'plugin_slug' => $pluginSlug,
                'error' => $e->getMessage(),
            ]);

            return $this->getErrorResponse('Unable to connect to license server');
        }
    }

    /**
     * Validate a license for a plugin
     *
     * @return array{valid: bool, status: string, message: string, data: array}
     */
    public function validate(string $pluginSlug, string $licenseKey, string $domain): array
    {
        if ($this->isTestLicense($pluginSlug, $licenseKey)) {
            return $this->getTestLicenseResponse($pluginSlug);
        }

        try {
            $url = "{$this->proxyUrl}/license-proxy/validate";
            $payload = [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'plugin_slug' => $pluginSlug,
            ];

            Log::info('LicenseProxyClient: Validate request', [
                'url' => $url,
                'plugin_slug' => $pluginSlug,
                'domain' => $domain,
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->post($url, $payload);

            Log::info('LicenseProxyClient: Validate response', [
                'plugin_slug' => $pluginSlug,
                'status' => $response->status(),
                'valid' => $response->json('valid'),
            ]);

            return $this->parseValidateResponse($response);

        } catch (\Exception $e) {
            Log::error('LicenseProxyClient: Validate exception', [
                'plugin_slug' => $pluginSlug,
                'error' => $e->getMessage(),
            ]);

            return $this->getErrorResponse('Unable to connect to license server');
        }
    }

    /**
     * Deactivate a license for a plugin
     *
     * @return array{success: bool, message: string}
     */
    public function deactivate(string $pluginSlug, string $licenseKey, string $domain): array
    {
        if ($this->isTestLicense($pluginSlug, $licenseKey)) {
            return [
                'success' => true,
                'message' => 'Test license deactivated',
            ];
        }

        try {
            $url = "{$this->proxyUrl}/license-proxy/deactivate";
            $payload = [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'plugin_slug' => $pluginSlug,
            ];

            Log::info('LicenseProxyClient: Deactivate request', [
                'url' => $url,
                'plugin_slug' => $pluginSlug,
                'domain' => $domain,
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->post($url, $payload);

            $json = $response->json() ?? [];

            if ($response->successful() && ($json['success'] ?? false)) {
                return [
                    'success' => true,
                    'message' => $json['message'] ?? 'License deactivated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $json['message'] ?? 'Deactivation failed',
            ];

        } catch (\Exception $e) {
            Log::error('LicenseProxyClient: Deactivate exception', [
                'plugin_slug' => $pluginSlug,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to connect to license server',
            ];
        }
    }

    /**
     * Check if the given key is a valid test license for the plugin
     */
    protected function isTestLicense(string $pluginSlug, string $licenseKey): bool
    {
        $expectedTestKey = $this->getTestLicenseKey($pluginSlug);

        return strtoupper($licenseKey) === $expectedTestKey
            && in_array(app()->environment(), ['local', 'testing'], true);
    }

    /**
     * Generate the expected test license key for a plugin
     * Format: TALLCMS-{SLUG-UPPERCASE}-TEST-LICENSE
     * Example: tallcms/pro -> TALLCMS-PRO-TEST-LICENSE
     */
    protected function getTestLicenseKey(string $pluginSlug): string
    {
        // Extract product name from slug (e.g., 'tallcms/pro' -> 'PRO')
        $parts = explode('/', $pluginSlug);
        $product = strtoupper(end($parts));

        return self::TEST_LICENSE_PREFIX.$product.self::TEST_LICENSE_SUFFIX;
    }

    /**
     * Get a mock response for test license
     */
    protected function getTestLicenseResponse(string $pluginSlug): array
    {
        return [
            'valid' => true,
            'status' => 'active',
            'message' => 'Test license activated (development only)',
            'data' => [
                'license_key' => $this->getTestLicenseKey($pluginSlug),
                'plugin_slug' => $pluginSlug,
                'status' => 'active',
                'activated_at' => now()->toIso8601String(),
                'expires_at' => now()->addYear()->toIso8601String(),
                'is_test' => true,
            ],
        ];
    }

    /**
     * Get error response structure
     */
    protected function getErrorResponse(string $message): array
    {
        return [
            'valid' => false,
            'status' => 'error',
            'message' => $message,
            'data' => [],
        ];
    }

    /**
     * Parse activation response from proxy
     */
    protected function parseActivateResponse($response): array
    {
        $json = $response->json() ?? [];

        if ($response->successful() && ($json['success'] ?? false)) {
            return [
                'valid' => true,
                'status' => 'active',
                'message' => $json['message'] ?? 'License activated successfully',
                'data' => $json['data'] ?? [],
            ];
        }

        // Handle 404 - plugin not supported
        if ($response->status() === 404) {
            return [
                'valid' => false,
                'status' => 'not_supported',
                'message' => 'This plugin does not support license activation',
                'data' => [],
            ];
        }

        return [
            'valid' => false,
            'status' => 'invalid',
            'message' => $json['message'] ?? 'License activation failed',
            'data' => $json['data'] ?? [],
        ];
    }

    /**
     * Parse validation response from proxy
     */
    protected function parseValidateResponse($response): array
    {
        $json = $response->json() ?? [];

        if ($response->successful() && ($json['valid'] ?? false)) {
            return [
                'valid' => true,
                'status' => 'active',
                'message' => 'License is valid',
                'data' => $json['data'] ?? [],
            ];
        }

        // Handle 404 - plugin not supported
        if ($response->status() === 404) {
            return [
                'valid' => false,
                'status' => 'not_supported',
                'message' => 'This plugin does not support license validation',
                'data' => [],
            ];
        }

        return [
            'valid' => false,
            'status' => $json['status'] ?? 'invalid',
            'message' => $json['message'] ?? 'License validation failed',
            'data' => $json['data'] ?? [],
        ];
    }
}
