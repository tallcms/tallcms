<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseProxyClient
{
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
     * Check for plugin updates (requires valid license)
     *
     * This is the server-side update gate: updates are only available with a valid license.
     *
     * @return array{success: bool, update_available: bool, license_valid: bool, latest_version: ?string, download_url: ?string, message: string}
     */
    public function checkForUpdates(string $pluginSlug, string $licenseKey, string $domain, string $currentVersion): array
    {
        try {
            $url = "{$this->proxyUrl}/license-proxy/updates";
            $payload = [
                'license_key' => $licenseKey,
                'domain' => $domain,
                'plugin_slug' => $pluginSlug,
                'current_version' => $currentVersion,
            ];

            Log::info('LicenseProxyClient: Update check request', [
                'url' => $url,
                'plugin_slug' => $pluginSlug,
                'current_version' => $currentVersion,
            ]);

            $response = Http::timeout(15)
                ->acceptJson()
                ->post($url, $payload);

            $json = $response->json() ?? [];

            Log::info('LicenseProxyClient: Update check response', [
                'plugin_slug' => $pluginSlug,
                'status' => $response->status(),
                'update_available' => $json['update_available'] ?? false,
            ]);

            // Handle successful response
            if ($response->successful() && ($json['success'] ?? false)) {
                return [
                    'success' => true,
                    'license_valid' => $json['license_valid'] ?? true,
                    'update_available' => $json['update_available'] ?? false,
                    'current_version' => $json['current_version'] ?? $currentVersion,
                    'latest_version' => $json['latest_version'] ?? $currentVersion,
                    'download_url' => $json['download_url'] ?? null,
                    'changelog_url' => $json['changelog_url'] ?? null,
                    'purchase_url' => $json['purchase_url'] ?? null,
                    'requirements' => $json['requirements'] ?? [],
                    'message' => $json['update_available']
                        ? "Update available: v{$json['latest_version']}"
                        : 'You have the latest version',
                ];
            }

            // Handle 403 - license invalid/expired
            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'license_valid' => false,
                    'update_available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => null,
                    'download_url' => null,
                    'changelog_url' => null,
                    'purchase_url' => $json['purchase_url'] ?? null,
                    'message' => $json['message'] ?? 'Valid license required to check for updates',
                ];
            }

            // Handle other errors
            return [
                'success' => false,
                'license_valid' => false,
                'update_available' => false,
                'current_version' => $currentVersion,
                'latest_version' => null,
                'download_url' => null,
                'changelog_url' => null,
                'purchase_url' => null,
                'message' => $json['message'] ?? 'Unable to check for updates',
            ];

        } catch (\Exception $e) {
            Log::error('LicenseProxyClient: Update check exception', [
                'plugin_slug' => $pluginSlug,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'license_valid' => false,
                'update_available' => false,
                'current_version' => $currentVersion,
                'latest_version' => null,
                'download_url' => null,
                'changelog_url' => null,
                'message' => 'Unable to connect to update server',
            ];
        }
    }

    /**
     * Deactivate a license for a plugin
     *
     * @return array{success: bool, message: string}
     */
    public function deactivate(string $pluginSlug, string $licenseKey, string $domain): array
    {
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

        // Handle FINGERPRINT_ALREADY_EXISTS - license is already active for this domain
        // This can happen after plugin reinstall when local DB was cleared but server still has activation
        // Treat this as a successful activation since the license IS active for this domain
        $errorCode = $json['error_code'] ?? $json['code'] ?? '';
        $message = $json['message'] ?? '';
        if (str_contains($errorCode, 'FINGERPRINT_ALREADY_EXISTS') || str_contains($message, 'FINGERPRINT_ALREADY_EXISTS')) {
            return [
                'valid' => true,
                'status' => 'active',
                'message' => 'License is already active for this domain',
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

        // Handle 5xx server errors and 429 rate limiting as 'error'
        if ($response->serverError() || $response->status() === 429) {
            return $this->getErrorResponse(
                $response->status() === 429
                    ? 'License server is busy. Please try again later.'
                    : 'License server temporarily unavailable'
            );
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

        // Handle 5xx server errors and 429 rate limiting as 'error' so grace period applies
        if ($response->serverError() || $response->status() === 429) {
            return $this->getErrorResponse(
                $response->status() === 429
                    ? 'License server is busy. Please try again later.'
                    : 'License server temporarily unavailable'
            );
        }

        return [
            'valid' => false,
            'status' => $json['status'] ?? 'invalid',
            'message' => $json['message'] ?? 'License validation failed',
            'data' => $json['data'] ?? [],
        ];
    }
}
