<?php

/**
 * TallCMS Pro Configuration
 *
 * Merged via: $this->mergeConfigFrom(__DIR__.'/config.php', 'tallcms-pro')
 * Access via: config('tallcms-pro.license.cache_ttl')
 */

return [
    /*
    |--------------------------------------------------------------------------
    | License Configuration
    |--------------------------------------------------------------------------
    */
    'license' => [
        // How long to cache a valid license response (24 hours)
        'cache_ttl' => 86400,

        // Grace period when Anystack is unreachable (7 days)
        'offline_grace_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anystack Configuration
    |--------------------------------------------------------------------------
    */
    'anystack' => [
        // Anystack API base URL
        'api_url' => env('ANYSTACK_API_URL', 'https://api.anystack.sh'),

        // Product ID (set in Anystack dashboard)
        'product_id' => env('ANYSTACK_PRODUCT_ID'),

        // Webhook secret for signature verification (env-only for security)
        'webhook_secret' => env('ANYSTACK_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        // How long to cache analytics data (15 minutes)
        'cache_ttl' => 900,

        // Default provider (google_analytics, plausible, fathom)
        'default_provider' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Marketing Configuration
    |--------------------------------------------------------------------------
    */
    'email' => [
        // Default provider (mailchimp, convertkit, sendinblue)
        'default_provider' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maps Configuration
    |--------------------------------------------------------------------------
    */
    'maps' => [
        // Default provider (google_maps, mapbox, openstreetmap)
        'default_provider' => 'openstreetmap',
    ],
];
