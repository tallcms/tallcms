<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use TallCms\Cms\Models\Plugin;
use TallCms\Cms\Models\PluginLicense;

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
     * Check if a plugin has ever been licensed (activated_at exists)
     *
     * This is used for watermark logic - once a license has been activated,
     * the watermark should never show again, even if the license expires.
     * Expired licenses only lose access to updates, not functionality.
     */
    public function hasEverBeenLicensed(string $pluginSlug): bool
    {
        $license = PluginLicense::findByPluginSlug($pluginSlug);

        return $license && $license->activated_at !== null;
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

        // If validation failed due to connection error, check grace periods
        $offlineGraceDays = config('plugin.license.offline_grace_days', 7);
        if ($result['status'] === 'error') {
            // Honor either offline grace (7 days from last validation) OR renewal grace (14 days from expiry)
            if ($license->isWithinGracePeriod($offlineGraceDays) || $license->isWithinRenewalGracePeriod($renewalGraceDays)) {
                Log::warning('PluginLicenseService: Using grace period (proxy unreachable)', [
                    'plugin_slug' => $pluginSlug,
                    'license_key' => $license->masked_key,
                    'last_validated' => $license->last_validated_at?->toDateTimeString(),
                    'expires_at' => $license->expires_at?->toDateTimeString(),
                ]);

                $this->cachedValidStates[$pluginSlug] = true;

                return true;
            }
        }

        // Proxy returned a definitive status (not a connection error)
        if ($result['status'] !== 'error') {
            // If proxy says expired but we're within renewal grace period, honor the grace
            // This gives customers 14 days after expiry to renew before features are restricted
            if ($result['status'] === 'expired' && $license->isWithinRenewalGracePeriod($renewalGraceDays)) {
                Log::info('PluginLicenseService: License expired but within renewal grace period', [
                    'plugin_slug' => $pluginSlug,
                    'license_key' => $license->masked_key,
                    'expires_at' => $license->expires_at?->toDateTimeString(),
                    'grace_ends' => $license->expires_at?->copy()->addDays($renewalGraceDays)->toDateTimeString(),
                ]);

                // Keep status as 'active' during grace period so UI shows "Renewal Due" not "Expired"
                // Don't change status here - let it remain 'active'
                $this->cachedValidStates[$pluginSlug] = true;

                return true;
            }

            // Outside grace period or non-expired failure - update status
            // Don't persist 'active' when valid=false (e.g., domain not activated)
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
                'purchase_url' => config("plugin.license.purchase_urls.{$pluginSlug}"),
                'download_url' => config("plugin.license.download_urls.{$pluginSlug}"),
            ];
        }

        $isValid = $this->isValid($pluginSlug);
        $renewalGraceDays = config('plugin.license.renewal_grace_days', 14);
        $inRenewalGrace = $license->isWithinRenewalGracePeriod($renewalGraceDays);

        // Determine detailed message based on license state
        $message = $this->getLicenseMessage($license, $isValid, $renewalGraceDays);

        // Determine status label and color
        // Grace period takes precedence - even if status is 'expired', show 'Renewal Due' during grace
        $statusLabel = $this->getStatusLabel($license, $inRenewalGrace);
        $statusColor = $this->getStatusColor($license, $inRenewalGrace);

        return [
            'has_license' => true,
            'status' => $license->status,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'is_valid' => $isValid,
            'license_key' => $license->masked_key,
            'domain' => $license->domain,
            'activated_at' => $license->activated_at?->format('M j, Y'),
            'expires_at' => $license->expires_at?->format('M j, Y'),
            'last_validated' => $license->last_validated_at?->diffForHumans(),
            'message' => $message,
            'is_in_grace_period' => $inRenewalGrace,
            'purchase_url' => config("plugin.license.purchase_urls.{$pluginSlug}"),
            'download_url' => config("plugin.license.download_urls.{$pluginSlug}"),
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

        // Within renewal grace period - check this BEFORE checking 'expired' status
        // This ensures "Renewal Due" message shows even if status got set to 'expired'
        if ($license->isWithinRenewalGracePeriod($renewalGraceDays)) {
            $daysLeft = (int) now()->diffInDays($license->expires_at->copy()->addDays($renewalGraceDays));

            return "Your license renewal is being processed. If you haven't renewed yet, please do so within {$daysLeft} days to maintain access to updates.";
        }

        // Hard expired (past grace period) - site keeps working but no updates
        if ($license->status === 'expired' || $license->isHardExpired($renewalGraceDays)) {
            return 'Your license has expired. This plugin keeps working, but updates and support are paused. Renew to get the latest features and security updates.';
        }

        // Active and valid
        if ($isValid && $license->status === 'active') {
            if ($license->expires_at) {
                $daysUntilExpiry = (int) now()->diffInDays($license->expires_at, false);
                if ($daysUntilExpiry <= 30 && $daysUntilExpiry > 0) {
                    return "Your license is active and expires in {$daysUntilExpiry} days. Consider renewing soon for uninterrupted updates.";
                }
            }

            return 'Your license is active and valid. You have access to all features and updates.';
        }

        return 'Your license needs attention.';
    }

    /**
     * Get status label for display
     * Grace period takes precedence over status
     */
    protected function getStatusLabel(PluginLicense $license, bool $inRenewalGrace): string
    {
        // Grace period takes precedence - show "Renewal Due" even if status is 'expired'
        if ($inRenewalGrace) {
            return 'Renewal Due';
        }

        return match ($license->status) {
            'active' => 'Active',
            'expired' => 'Expired',
            'invalid' => 'Invalid',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for display
     * Grace period takes precedence over status
     */
    protected function getStatusColor(PluginLicense $license, bool $inRenewalGrace): string
    {
        // Grace period takes precedence - show warning color even if status is 'expired'
        if ($inRenewalGrace) {
            return 'warning';
        }

        return match ($license->status) {
            'active' => 'success',
            'expired' => 'warning',
            'invalid' => 'danger',
            'pending' => 'info',
            default => 'gray',
        };
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
     * Check for plugin updates (requires valid license)
     *
     * This is the server-side update gate: the proxy validates the license
     * before returning update information. No valid license = no updates.
     *
     * @return array{success: bool, update_available: bool, license_valid: bool, latest_version: ?string, download_url: ?string, message: string}
     */
    public function checkForUpdates(string $pluginSlug): array
    {
        $license = PluginLicense::findByPluginSlug($pluginSlug);
        $purchaseUrl = config("plugin.license.purchase_urls.{$pluginSlug}");

        if (! $license) {
            return [
                'success' => false,
                'license_valid' => false,
                'update_available' => false,
                'current_version' => null,
                'latest_version' => null,
                'download_url' => null,
                'changelog_url' => null,
                'purchase_url' => $purchaseUrl,
                'message' => 'No license found. Please purchase and activate a license to check for updates.',
            ];
        }

        // Get current installed version
        $plugin = $this->pluginManager->getInstalledPlugins()
            ->first(fn (Plugin $p) => $p->getLicenseSlug() === $pluginSlug);

        $currentVersion = $plugin?->version ?? '0.0.0';
        $domain = $this->getCurrentDomain();

        // Call proxy to check for updates (proxy validates license)
        $result = $this->client->checkForUpdates(
            $pluginSlug,
            $license->license_key,
            $domain,
            $currentVersion
        );

        // Log update check
        Log::info('PluginLicenseService: Update check', [
            'plugin_slug' => $pluginSlug,
            'current_version' => $currentVersion,
            'license_valid' => $result['license_valid'] ?? false,
            'update_available' => $result['update_available'] ?? false,
        ]);

        return $result;
    }

    /**
     * Check for updates across all licensable plugins
     *
     * @return array<string, array>
     */
    public function checkAllForUpdates(): array
    {
        $results = [];

        foreach ($this->getLicensablePlugins() as $plugin) {
            $pluginSlug = $plugin->getLicenseSlug();
            $results[$pluginSlug] = array_merge(
                $this->checkForUpdates($pluginSlug),
                [
                    'plugin_name' => $plugin->name,
                    'plugin_vendor' => $plugin->vendor,
                ]
            );
        }

        return $results;
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

    /**
     * Check for updates automatically (rate-limited, cached)
     *
     * This is called on admin page loads to proactively check for updates
     * without requiring user action. Results are cached to avoid excessive API calls.
     */
    public function checkForUpdatesAutomatically(): void
    {
        $cacheKey = 'plugin_updates_last_check';
        $checkInterval = config('plugin.license.update_check_interval', 86400); // 24 hours default

        // Check if we've already checked recently
        $lastCheck = Cache::get($cacheKey);
        if ($lastCheck && now()->diffInSeconds($lastCheck) < $checkInterval) {
            return;
        }

        // Mark that we're checking now (prevents concurrent checks)
        Cache::put($cacheKey, now(), $checkInterval);

        // Get all licensable plugins with valid licenses
        $licensablePlugins = $this->getLicensablePlugins();

        if ($licensablePlugins->isEmpty()) {
            return;
        }

        $updates = [];

        foreach ($licensablePlugins as $plugin) {
            $pluginSlug = $plugin->getLicenseSlug();
            $license = PluginLicense::findByPluginSlug($pluginSlug);

            // Only check if we have a license (don't waste API calls for unlicensed plugins)
            if (! $license) {
                continue;
            }

            try {
                $result = $this->checkForUpdates($pluginSlug);

                if ($result['update_available'] ?? false) {
                    $updates[$pluginSlug] = [
                        'plugin_name' => $plugin->name,
                        'current_version' => $result['current_version'] ?? $plugin->version,
                        'latest_version' => $result['latest_version'],
                        'download_url' => $result['download_url'] ?? null,
                        'changelog_url' => $result['changelog_url'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('PluginLicenseService: Auto update check failed', [
                    'plugin_slug' => $pluginSlug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cache available updates
        Cache::put('plugin_available_updates', $updates, $checkInterval);

        if (! empty($updates)) {
            Log::info('PluginLicenseService: Updates available', [
                'plugins' => array_keys($updates),
            ]);
        }
    }

    /**
     * Get cached available updates
     *
     * @return array<string, array{plugin_name: string, current_version: string, latest_version: string, download_url: ?string, changelog_url: ?string}>
     */
    public function getAvailableUpdates(): array
    {
        return Cache::get('plugin_available_updates', []);
    }

    /**
     * Check if there are any available updates
     */
    public function hasAvailableUpdates(): bool
    {
        return ! empty($this->getAvailableUpdates());
    }

    /**
     * Get count of available updates
     */
    public function getAvailableUpdatesCount(): int
    {
        return count($this->getAvailableUpdates());
    }

    /**
     * Clear update cache (force re-check on next page load)
     */
    public function clearUpdateCache(): void
    {
        Cache::forget('plugin_updates_last_check');
        Cache::forget('plugin_available_updates');
    }
}
