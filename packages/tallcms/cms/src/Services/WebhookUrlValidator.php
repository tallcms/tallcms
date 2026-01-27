<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use TallCms\Cms\Exceptions\WebhookDeliveryException;

class WebhookUrlValidator
{
    /**
     * @var array<string>
     */
    protected array $allowedHosts;

    /**
     * @var array<string>
     */
    protected array $blockedHosts;

    public function __construct()
    {
        $this->allowedHosts = config('tallcms.webhooks.allowed_hosts', []);
        $this->blockedHosts = config('tallcms.webhooks.blocked_hosts', []);
    }

    /**
     * Validate URL at registration time (basic checks).
     *
     * @return array{valid: bool, error: string|null}
     */
    public function validateOnCreate(string $url): array
    {
        $parsed = parse_url($url);

        if (! $parsed) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        // Must be HTTPS
        if (($parsed['scheme'] ?? '') !== 'https') {
            return ['valid' => false, 'error' => 'URL must use HTTPS'];
        }

        // Must be port 443 (or no port specified)
        if (isset($parsed['port']) && $parsed['port'] !== 443) {
            return ['valid' => false, 'error' => 'Only port 443 is allowed'];
        }

        $host = $parsed['host'] ?? '';

        if (empty($host)) {
            return ['valid' => false, 'error' => 'URL must have a host'];
        }

        // Strip brackets from IPv6 literals for validation
        $hostForValidation = trim($host, '[]');

        // Block IP literals in hostname (force DNS resolution)
        if (filter_var($hostForValidation, FILTER_VALIDATE_IP)) {
            return ['valid' => false, 'error' => 'IP addresses are not allowed. Use a hostname.'];
        }

        // Block localhost variants
        $blockedPatterns = ['localhost', '*.localhost', '*.local', '*.internal'];
        foreach ($blockedPatterns as $pattern) {
            if ($this->matchesPattern($host, $pattern)) {
                return ['valid' => false, 'error' => 'Localhost and internal hosts are not allowed'];
            }
        }

        // Check allowlist (if configured)
        if (! empty($this->allowedHosts)) {
            if (! in_array($host, $this->allowedHosts, true)) {
                return ['valid' => false, 'error' => 'Host is not in the allowed list'];
            }
        }

        // Check blocklist
        if (in_array($host, $this->blockedHosts, true)) {
            return ['valid' => false, 'error' => 'Host is in the blocked list'];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate resolved IPs at delivery time (DNS rebinding protection).
     * Called immediately before HTTP request in DispatchWebhook job.
     *
     * @return array<string> Resolved IP addresses for pinning
     *
     * @throws WebhookDeliveryException
     */
    public function validateAtDelivery(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            throw new WebhookDeliveryException('Invalid URL: cannot extract host');
        }

        // Resolve all A and AAAA records
        $ipv4 = gethostbynamel($host) ?: [];
        $ipv6 = $this->resolveIPv6($host);
        $allIps = array_merge($ipv4, $ipv6);

        if (empty($allIps)) {
            throw new WebhookDeliveryException('DNS resolution failed for: '.$host);
        }

        // Validate ALL resolved IPs
        foreach ($allIps as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new WebhookDeliveryException(
                    "Webhook blocked: {$host} resolves to private/reserved IP"
                );
            }
        }

        return $allIps;
    }

    /**
     * Resolve IPv6 addresses for a hostname.
     *
     * @return array<string>
     */
    protected function resolveIPv6(string $host): array
    {
        $records = @dns_get_record($host, DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return array_column($records, 'ipv6');
    }

    /**
     * Check if an IP address is public (not private or reserved).
     */
    protected function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * Match a hostname against a pattern (supports * wildcard).
     */
    protected function matchesPattern(string $host, string $pattern): bool
    {
        // Exact match
        if ($host === $pattern) {
            return true;
        }

        // Wildcard pattern
        if (str_starts_with($pattern, '*.')) {
            $suffix = substr($pattern, 1); // e.g., .localhost

            return str_ends_with($host, $suffix);
        }

        return false;
    }
}
