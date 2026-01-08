<?php

namespace Tallcms\Pro\Services\Analytics;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tallcms\Pro\Models\ProSetting;

class GoogleAnalyticsProvider implements AnalyticsProviderInterface
{
    protected const API_BASE = 'https://analyticsdata.googleapis.com/v1beta';

    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    protected ?string $propertyId = null;

    protected ?array $credentials = null;

    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->propertyId = ProSetting::get('ga4_property_id');
        $credentialsJson = ProSetting::get('ga4_credentials_json'); // auto-decrypted by ProSetting

        if ($credentialsJson) {
            $this->credentials = json_decode($credentialsJson, true);
        }
    }

    public function getName(): string
    {
        return 'google';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->propertyId) && ! empty($this->credentials);
    }

    public function getOverviewMetrics(string $period = '7d'): array
    {
        $dateRange = $this->parsePeriod($period);
        $previousRange = $this->getPreviousPeriod($dateRange);

        // Current period metrics
        $current = $this->runReport([
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'screenPageViews'],
                ['name' => 'bounceRate'],
                ['name' => 'averageSessionDuration'],
            ],
            'dateRanges' => [$dateRange],
        ]);

        // Previous period for comparison
        $previous = $this->runReport([
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'screenPageViews'],
            ],
            'dateRanges' => [$previousRange],
        ]);

        $currentRow = $current['rows'][0]['metricValues'] ?? [];
        $previousRow = $previous['rows'][0]['metricValues'] ?? [];

        $visitors = (int) ($currentRow[0]['value'] ?? 0);
        $pageviews = (int) ($currentRow[1]['value'] ?? 0);
        $bounceRate = round((float) ($currentRow[2]['value'] ?? 0) * 100, 1);
        $avgDuration = (int) ($currentRow[3]['value'] ?? 0);

        $prevVisitors = (int) ($previousRow[0]['value'] ?? 0);
        $prevPageviews = (int) ($previousRow[1]['value'] ?? 0);

        return [
            'visitors' => $visitors,
            'pageviews' => $pageviews,
            'bounce_rate' => $bounceRate,
            'avg_session_duration' => $avgDuration,
            'visitors_change' => $this->calculateChange($visitors, $prevVisitors),
            'pageviews_change' => $this->calculateChange($pageviews, $prevPageviews),
        ];
    }

    public function getTopPages(int $limit = 5, string $period = '7d'): array
    {
        $dateRange = $this->parsePeriod($period);

        $result = $this->runReport([
            'dimensions' => [
                ['name' => 'pagePath'],
                ['name' => 'pageTitle'],
            ],
            'metrics' => [
                ['name' => 'screenPageViews'],
            ],
            'dateRanges' => [$dateRange],
            'orderBys' => [
                ['metric' => ['metricName' => 'screenPageViews'], 'desc' => true],
            ],
            'limit' => $limit,
        ]);

        $pages = [];
        foreach ($result['rows'] ?? [] as $row) {
            $pages[] = [
                'path' => $row['dimensionValues'][0]['value'] ?? '/',
                'title' => $row['dimensionValues'][1]['value'] ?? 'Untitled',
                'views' => (int) ($row['metricValues'][0]['value'] ?? 0),
            ];
        }

        return $pages;
    }

    public function getTrafficSources(int $limit = 5, string $period = '7d'): array
    {
        $dateRange = $this->parsePeriod($period);

        $result = $this->runReport([
            'dimensions' => [
                ['name' => 'sessionSource'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
            ],
            'dateRanges' => [$dateRange],
            'orderBys' => [
                ['metric' => ['metricName' => 'sessions'], 'desc' => true],
            ],
            'limit' => $limit,
        ]);

        $sources = [];
        foreach ($result['rows'] ?? [] as $row) {
            $sources[] = [
                'source' => $row['dimensionValues'][0]['value'] ?? 'direct',
                'sessions' => (int) ($row['metricValues'][0]['value'] ?? 0),
            ];
        }

        return $sources;
    }

    public function getVisitorTrend(string $period = '7d'): array
    {
        $dateRange = $this->parsePeriod($period);

        $result = $this->runReport([
            'dimensions' => [
                ['name' => 'date'],
            ],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'screenPageViews'],
            ],
            'dateRanges' => [$dateRange],
            'orderBys' => [
                ['dimension' => ['dimensionName' => 'date'], 'desc' => false],
            ],
        ]);

        $trend = [];
        foreach ($result['rows'] ?? [] as $row) {
            $date = $row['dimensionValues'][0]['value'] ?? '';
            $trend[] = [
                'date' => $this->formatDate($date),
                'visitors' => (int) ($row['metricValues'][0]['value'] ?? 0),
                'pageviews' => (int) ($row['metricValues'][1]['value'] ?? 0),
            ];
        }

        return $trend;
    }

    public function testConnection(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $token = $this->getAccessToken();

            if (! $token) {
                return false;
            }

            // Try a simple metadata request
            $response = Http::withToken($token)
                ->get(self::API_BASE."/properties/{$this->propertyId}/metadata");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('GA4 connection test failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Run a GA4 Data API report
     */
    protected function runReport(array $request): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            throw new \RuntimeException('Failed to get access token');
        }

        $response = Http::withToken($token)
            ->post(self::API_BASE."/properties/{$this->propertyId}:runReport", $request);

        if (! $response->successful()) {
            Log::error('GA4 API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('GA4 API request failed: '.$response->status());
        }

        return $response->json();
    }

    /**
     * Get OAuth2 access token using service account
     */
    protected function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        if (! $this->credentials) {
            return null;
        }

        try {
            $jwt = $this->createJwt();

            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                Log::error('GA4 token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $this->accessToken = $response->json('access_token');

            return $this->accessToken;
        } catch (\Throwable $e) {
            Log::error('GA4 token generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create JWT for service account authentication
     */
    protected function createJwt(): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $claims = [
            'iss' => $this->credentials['client_email'] ?? '',
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $claimsEncoded = $this->base64UrlEncode(json_encode($claims));

        $signatureInput = "{$headerEncoded}.{$claimsEncoded}";

        $privateKey = $this->credentials['private_key'] ?? '';
        $signature = '';

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$signatureInput}.{$signatureEncoded}";
    }

    /**
     * Base64 URL-safe encode
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Parse period string to date range
     */
    protected function parsePeriod(string $period): array
    {
        $days = match ($period) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        return [
            'startDate' => now()->subDays($days)->format('Y-m-d'),
            'endDate' => now()->subDay()->format('Y-m-d'), // GA4 data has ~24h delay
        ];
    }

    /**
     * Get the previous period for comparison
     */
    protected function getPreviousPeriod(array $currentRange): array
    {
        $start = \Carbon\Carbon::parse($currentRange['startDate']);
        $end = \Carbon\Carbon::parse($currentRange['endDate']);
        $days = $start->diffInDays($end) + 1;

        return [
            'startDate' => $start->subDays($days)->format('Y-m-d'),
            'endDate' => $end->subDays($days)->format('Y-m-d'),
        ];
    }

    /**
     * Calculate percentage change
     */
    protected function calculateChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Format GA4 date (YYYYMMDD) to readable format
     */
    protected function formatDate(string $date): string
    {
        if (strlen($date) !== 8) {
            return $date;
        }

        return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
    }
}
