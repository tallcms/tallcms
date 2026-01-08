<?php

namespace Tallcms\Pro\Services\Analytics;

interface AnalyticsProviderInterface
{
    /**
     * Get the provider name
     */
    public function getName(): string;

    /**
     * Check if the provider is configured
     */
    public function isConfigured(): bool;

    /**
     * Get overview metrics for the dashboard widget
     * Returns: visitors, pageviews, bounce_rate, avg_session_duration
     */
    public function getOverviewMetrics(string $period = '7d'): array;

    /**
     * Get top pages
     */
    public function getTopPages(int $limit = 5, string $period = '7d'): array;

    /**
     * Get traffic sources
     */
    public function getTrafficSources(int $limit = 5, string $period = '7d'): array;

    /**
     * Get visitor trend data for chart
     */
    public function getVisitorTrend(string $period = '7d'): array;

    /**
     * Test the connection/credentials
     */
    public function testConnection(): bool;
}
