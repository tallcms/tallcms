<?php

namespace Tallcms\Pro\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tallcms\Pro\Models\ProLicense;

class LicenseService
{
    protected AnystackClient $client;

    protected ?bool $cachedValidState = null;

    public function __construct(AnystackClient $client)
    {
        $this->client = $client;
    }

    /**
     * Check if the current license is valid
     *
     * Uses caching to minimize API calls:
     * 1. Check in-memory cache
     * 2. Check database cache
     * 3. If cache expired, validate with Anystack
     * 4. If Anystack unreachable, use grace period
     */
    public function isValid(): bool
    {
        // In-memory cache for this request
        if ($this->cachedValidState !== null) {
            return $this->cachedValidState;
        }

        $license = ProLicense::current();

        // No license stored
        if (! $license) {
            $this->cachedValidState = false;

            return false;
        }

        // Check if cached validation is still fresh
        if ($license->isCacheFresh() && $license->isActive()) {
            $this->cachedValidState = true;

            return true;
        }

        // Try to validate with Anystack
        $result = $this->validate($license->license_key);

        if ($result['valid']) {
            $this->cachedValidState = true;

            return true;
        }

        // If validation failed but we're within grace period, allow access
        if ($result['status'] === 'error' && $license->isWithinGracePeriod()) {
            Log::warning('TallCMS Pro: Using offline grace period for license', [
                'license_key' => substr($license->license_key, 0, 8).'...',
                'last_validated' => $license->last_validated_at?->toDateTimeString(),
            ]);
            $this->cachedValidState = true;

            return true;
        }

        $this->cachedValidState = false;

        return false;
    }

    /**
     * Validate a license key (without saving)
     */
    public function validate(string $licenseKey): array
    {
        $result = $this->client->validateLicense($licenseKey);

        // Update the database cache if we have a license record
        $license = ProLicense::where('license_key', $licenseKey)->first();
        if ($license && $result['status'] !== 'error') {
            $license->updateValidation($result['data'], $result['status']);
        }

        return $result;
    }

    /**
     * Activate a new license key
     */
    public function activate(string $licenseKey): array
    {
        // First validate/activate with Anystack
        $result = $this->client->activateLicense($licenseKey);

        if (! $result['valid']) {
            return $result;
        }

        // Remove any existing license
        ProLicense::truncate();

        // Create the new license record
        $license = ProLicense::create([
            'license_key' => $licenseKey,
            'status' => 'active',
            'activated_at' => now(),
            'last_validated_at' => now(),
            'domain' => request()->getHost(),
            'expires_at' => isset($result['data']['expires_at'])
                ? \Carbon\Carbon::parse($result['data']['expires_at'])
                : null,
            'validation_response' => $result['data'],
        ]);

        // Clear any cached state
        $this->cachedValidState = null;
        Cache::forget('tallcms_pro_license_valid');

        Log::info('TallCMS Pro: License activated', [
            'license_key' => substr($licenseKey, 0, 8).'...',
            'domain' => $license->domain,
        ]);

        return $result;
    }

    /**
     * Deactivate the current license
     */
    public function deactivate(): array
    {
        $license = ProLicense::current();

        if (! $license) {
            return [
                'success' => false,
                'message' => 'No license is currently active',
            ];
        }

        // Deactivate with Anystack
        $result = $this->client->deactivateLicense($license->license_key);

        // Remove the local license record regardless of API result
        $license->delete();

        // Clear cached state
        $this->cachedValidState = null;
        Cache::forget('tallcms_pro_license_valid');

        Log::info('TallCMS Pro: License deactivated');

        return $result;
    }

    /**
     * Get the current license status for display
     */
    public function getStatus(): array
    {
        $license = ProLicense::current();

        if (! $license) {
            return [
                'has_license' => false,
                'status' => 'none',
                'status_label' => 'No License',
                'status_color' => 'gray',
                'message' => 'Enter your license key to activate TallCMS Pro',
            ];
        }

        $isValid = $this->isValid();

        return [
            'has_license' => true,
            'status' => $license->status,
            'status_label' => match ($license->status) {
                'active' => 'Active',
                'expired' => 'Expired',
                'invalid' => 'Invalid',
                'pending' => 'Pending',
                default => 'Unknown',
            },
            'status_color' => match ($license->status) {
                'active' => 'success',
                'expired' => 'warning',
                'invalid' => 'danger',
                'pending' => 'info',
                default => 'gray',
            },
            'is_valid' => $isValid,
            'license_key' => $this->maskLicenseKey($license->license_key),
            'domain' => $license->domain,
            'activated_at' => $license->activated_at?->format('M j, Y'),
            'expires_at' => $license->expires_at?->format('M j, Y'),
            'last_validated' => $license->last_validated_at?->diffForHumans(),
            'message' => $isValid
                ? 'Your license is active and valid'
                : 'Your license needs attention',
        ];
    }

    /**
     * Mask a license key for display
     */
    protected function maskLicenseKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4).'...'.substr($key, -4);
    }
}
