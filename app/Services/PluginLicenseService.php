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
        if (! $license->needsRevalidation($cacheTtl) && $license->isActive() && ! $license->isExpired()) {
            $this->cachedValidStates[$pluginSlug] = true;

            return true;
        }

        // Tier 3: Try to validate with license proxy
        $domain = $this->getCurrentDomain();
        $result = $this->client->validate($pluginSlug, $license->license_key, $domain);

        if ($result['valid']) {
            // Update license record with fresh validation data
            $license->updateFromValidation([
                'status' => 'active',
                'domain' => $domain,
                'metadata' => $result['data'],
            ]);

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
            // Preserve specific statuses (active, expired) rather than coercing to invalid
            $license->status = in_array($result['status'], ['active', 'expired'], true)
                ? $result['status']
                : 'invalid';
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

        // Mark license as invalid but keep the record (allows re-activation)
        $license->status = 'invalid';
        $license->save();

        // Clear cached state
        unset($this->cachedValidStates[$pluginSlug]);
        Cache::forget("plugin_license_valid:{$pluginSlug}");

        Log::info('PluginLicenseService: License deactivated', [
            'plugin_slug' => $pluginSlug,
        ]);

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
            'license_key' => $license->masked_key,
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
