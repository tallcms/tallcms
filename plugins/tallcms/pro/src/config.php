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

        // Product ID for TallCMS Pro in Anystack
        'product_id' => 'a0cb2ba1-5edc-4cdf-8134-48f8497f18bf',
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
