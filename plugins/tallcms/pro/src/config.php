<?php

/**
 * TallCMS Pro Configuration
 *
 * Merged via: $this->mergeConfigFrom(__DIR__.'/config.php', 'tallcms-pro')
 * Access via: config('tallcms-pro.analytics.cache_ttl')
 *
 * NOTE: License configuration is now handled by core TallCMS (config/plugin.php).
 * This plugin only needs "license_required": true in plugin.json.
 */

return [
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
