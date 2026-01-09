<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\PluginLicense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PluginLicenseService
{
    protected LicenseProxyClient $client;

    protected PluginManager $pluginManager;

    /**
     * In-memory cache for license validity per plugin
     */
    protected array $cachedValidStates = [];

    public function __construct(LicenseProxyClient $client, PluginManager $pluginManager)
    {
        $this->client = $client;
        $this->pluginManager = $pluginManager;
    }

    /**
     * Check if a plugin's license is valid
     *
     * Uses 3-tier caching to minimize API calls:
     * 1. Check in-memory cache (same request)
     * 2. Check database cache (across requests)
     * 3. If cache expired, validate with license proxy
     * 4. If proxy unreachable, use offline grace period
     */
    public function isValid(string $pluginSlug): bool
    {
        // Tier 1: In-memory cache for this request
        if (isset($this->cachedValidStates[$pluginSlug])) {
            return $this->cachedValidStates[$pluginSlug];
        }

        $license = PluginLicense::findByPluginSlug($pluginSlug);

        // No license stored
        if (! $license) {
            $this->cachedValidStates[$pluginSlug] = false;

            return false;
        }

        // Tier 2: Database cache - check if cached validation is still fresh
        $cacheTtl = config('plugin.license.cache_ttl', 86400);
        $renewalGraceDays = config('plugin.license.renewal_grace_days', 14);

        // License is valid if:
        // - Cache is fresh AND status is active AND not hard-expired (past grace period)
        // - OR license is within renewal grace period (allows time for billing webhooks)
        if (! $license->needsRevalidation($cacheTtl) && $license->isActive()) {
            if (! $license->isHardExpired($renewalGraceDays)) {
                $this->cachedValidStates[$pluginSlug] = true;

                return true;
            }
        }

        // Tier 3: Try to validate with license proxy
        $domain = $this->getCurrentDomain();
        $result = $this->client->validate($pluginSlug, $license->license_key, $domain);

        if ($result['valid']) {
            // Update license record with fresh validation data including expires_at for renewals
            $updateData = [
                'status' => 'active',
                'domain' => $domain,
                'metadata' => $result['data'],
            ];

            // Refresh expires_at from API response (handles renewals)
            if (isset($result['data']['expires_at'])) {
                $updateData['expires_at'] = Carbon::parse($result['data']['expires_at']);
            }

            $license->updateFromValidation($updateData);

            $this->cachedValidStates[$pluginSlug] = true;

            return true;
        }

        // If validation failed due to connection error, check grace period
        $graceDays = config('plugin.license.offline_grace_days', 7);
        if ($result['status'] === 'error' && $license->isWithinGracePeriod($graceDays)) {
            Log::warning('PluginLicenseService: Using offline grace period', [
                'plugin_slug' => $pluginSlug,
                'license_key' => $license->masked_key,
                'last_validated' => $license->last_validated_at?->toDateTimeString(),
            ]);

            $this->cachedValidStates[$pluginSlug] = true;

            return true;
        }

        // Update license status if validation returned a specific status
        if ($result['status'] !== 'error') {
            // Don't persist 'active' when valid=false (e.g., domain not activated)
            // This would make the UI inconsistent - license shows "active" but doesn't work
            // Preserve 'expired' as it's informative, otherwise mark as 'invalid'
            $license->status = $result['status'] === 'expired' ? 'expired' : 'invalid';
            $license->save();
        }

        $this->cachedValidStates[$pluginSlug] = false;

        return false;
    }

    /**
     * Activate a license for a plugin
     */
    public function activate(string $pluginSlug, string $licenseKey): array
    {
        $domain = $this->getCurrentDomain();

        // Activate with the license proxy
        $result = $this->client->activate($pluginSlug, $licenseKey, $domain);

        if (! $result['valid']) {
            return $result;
        }

        // Find existing license or create new one (overwrites existing)
        $license = PluginLicense::findByPluginSlug($pluginSlug);

        if ($license) {
            // Update existing license record
            $license->update([
                'license_key' => $licenseKey,
                'status' => 'active',
                'domain' => $domain,
                'activated_at' => now(),
                'last_validated_at' => now(),
                'expires_at' => isset($result['data']['expires_at'])
                    ? Carbon::parse($result['data']['expires_at'])
                    : null,
                'metadata' => $result['data'],
            ]);
        } else {
            // Create new license record
            $license = PluginLicense::create([
                'plugin_slug' => $pluginSlug,
                'license_key' => $licenseKey,
                'status' => 'active',
                'domain' => $domain,
                'activated_at' => now(),
                'last_validated_at' => now(),
                'expires_at' => isset($result['data']['expires_at'])
                    ? Carbon::parse($result['data']['expires_at'])
                    : null,
                'metadata' => $result['data'],
            ]);
        }

        // Clear cached state
        unset($this->cachedValidStates[$pluginSlug]);
        Cache::forget("plugin_license_valid:{$pluginSlug}");

        Log::info('PluginLicenseService: License activated', [
            'plugin_slug' => $pluginSlug,
            'license_key' => $license->masked_key,
            'domain' => $domain,
        ]);

        return $result;
    }

    /**
     * Deactivate a plugin's license
     */
    public function deactivate(string $pluginSlug): array
    {
        $license = PluginLicense::findByPluginSlug($pluginSlug);

        if (! $license) {
            return [
                'success' => false,
                'message' => 'No license is currently active for this plugin',
            ];
        }

        $domain = $this->getCurrentDomain();

        // Deactivate with license proxy
        $result = $this->client->deactivate($pluginSlug, $license->license_key, $domain);

        // Only mark license as invalid if proxy confirmed deactivation
        // Don't invalidate locally if proxy call failed (network/500) - license may still be active
        if ($result['success']) {
            $license->status = 'invalid';
            $license->save();

            // Clear cached state
            unset($this->cachedValidStates[$pluginSlug]);
            Cache::forget("plugin_license_valid:{$pluginSlug}");

            Log::info('PluginLicenseService: License deactivated', [
                'plugin_slug' => $pluginSlug,
            ]);
        } else {
            Log::warning('PluginLicenseService: Deactivation failed, license unchanged', [
                'plugin_slug' => $pluginSlug,
                'message' => $result['message'],
            ]);
        }

        return $result;
    }

    /**
     * Get license status for a plugin
     */
    public function getStatus(string $pluginSlug): array
    {
        $license = PluginLicense::findByPluginSlug($pluginSlug);

        if (! $license) {
            return [
                'has_license' => false,
                'status' => 'none',
                'status_label' => 'No License',
                'status_color' => 'gray',
                'message' => 'Enter your license key to activate this plugin',
            ];
        }

        $isValid = $this->isValid($pluginSlug);
        $renewalGraceDays = config('plugin.license.renewal_grace_days', 14);

        // Determine detailed message based on license state
        $message = $this->getLicenseMessage($license, $isValid, $renewalGraceDays);

        return [
            'has_license' => true,
            'status' => $license->status,
            'status_label' => match ($license->status) {
                'active' => $license->isWithinRenewalGracePeriod($renewalGraceDays) ? 'Renewal Due' : 'Active',
                'expired' => 'Expired',
                'invalid' => 'Invalid',
                'pending' => 'Pending',
                default => 'Unknown',
            },
            'status_color' => match ($license->status) {
                'active' => $license->isWithinRenewalGracePeriod($renewalGraceDays) ? 'warning' : 'success',
                'expired' => 'warning',
                'invalid' => 'danger',
                'pending' => 'info',
                default => 'gray',
            },
            'is_valid' => $isValid,
            'license_key' => $license->masked_key,
            'domain' => $license->domain,
            'activated_at' => $license->activated_at?->format('M j, Y'),
            'expires_at' => $license->expires_at?->format('M j, Y'),
            'last_validated' => $license->last_validated_at?->diffForHumans(),
            'message' => $message,
            'is_in_grace_period' => $license->isWithinRenewalGracePeriod($renewalGraceDays),
        ];
    }

    /**
     * Get detailed license message based on current state
     */
    protected function getLicenseMessage(PluginLicense $license, bool $isValid, int $renewalGraceDays): string
    {
        // Invalid license (never activated, revoked, or tampered)
        if ($license->status === 'invalid') {
            return 'Your license is invalid. Please enter a valid license key to activate this plugin.';
        }

        // Pending activation
        if ($license->status === 'pending') {
            return 'Your license is pending activation. Please enter your license key.';
        }

        // Hard expired (past grace period) - site keeps working but no updates
        if ($license->status === 'expired') {
            return 'Your license has expired. Your site keeps working, but updates and support are paused. Renew to get the latest features and security updates.';
        }

        // Active but within renewal grace period
        if ($license->isWithinRenewalGracePeriod($renewalGraceDays)) {
            $daysLeft = now()->diffInDays($license->expires_at->copy()->addDays($renewalGraceDays));

            return "Your license renewal is being processed. If you haven't renewed yet, please do so within {$daysLeft} days to maintain access to updates.";
        }

        // Active and valid
        if ($isValid && $license->status === 'active') {
            if ($license->expires_at) {
                $daysUntilExpiry = now()->diffInDays($license->expires_at, false);
                if ($daysUntilExpiry <= 30 && $daysUntilExpiry > 0) {
                    return "Your license is active and expires in {$daysUntilExpiry} days. Consider renewing soon for uninterrupted updates.";
                }
            }

            return 'Your license is active and valid. You have access to all features and updates.';
        }

        return 'Your license needs attention.';
    }

    /**
     * Get all plugins that require licenses
     */
    public function getLicensablePlugins(): \Illuminate\Support\Collection
    {
        return $this->pluginManager->getInstalledPlugins()
            ->filter(fn (Plugin $plugin) => $plugin->requiresLicense());
    }

    /**
     * Get status for all licensable plugins
     */
    public function getAllStatuses(): array
    {
        $statuses = [];

        foreach ($this->getLicensablePlugins() as $plugin) {
            $statuses[$plugin->getLicenseSlug()] = array_merge(
                $this->getStatus($plugin->getLicenseSlug()),
                [
                    'plugin_name' => $plugin->name,
                    'plugin_version' => $plugin->version,
                    'plugin_vendor' => $plugin->vendor,
                ]
            );
        }

        return $statuses;
    }

    /**
     * Get the current domain for license operations
     * Works in both HTTP and CLI contexts
     */
    protected function getCurrentDomain(): string
    {
        // HTTP context - use request host
        if (app()->runningInConsole() === false && request()) {
            return request()->getHost();
        }

        // CLI/queue fallback - parse from app.url config
        $appUrl = config('app.url', 'http://localhost');

        // Guard against malformed URLs or missing scheme
        $host = parse_url($appUrl, PHP_URL_HOST);

        if ($host === null || $host === false) {
            // Fallback if app.url is malformed (e.g., just "localhost" without scheme)
            // Try parsing with added scheme
            $host = parse_url('http://'.ltrim($appUrl, '/'), PHP_URL_HOST);
        }

        return $host ?: 'localhost';
    }

    /**
     * Clear in-memory cache for testing
     */
    public function clearCache(?string $pluginSlug = null): void
    {
        if ($pluginSlug) {
            unset($this->cachedValidStates[$pluginSlug]);
            Cache::forget("plugin_license_valid:{$pluginSlug}");
        } else {
            $this->cachedValidStates = [];
        }
    }
}
