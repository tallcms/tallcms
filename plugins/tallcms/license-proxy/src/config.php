<?php

/**
 * TallCMS License Proxy Configuration
 *
 * This plugin should ONLY be installed on tallcms.com
 * It proxies license validation requests to Anystack
 */

return [
    'anystack' => [
        // Anystack API base URL
        'api_url' => env('ANYSTACK_API_URL', 'https://api.anystack.sh'),

        // Anystack API key (keep secret, only on tallcms.com)
        'api_key' => env('ANYSTACK_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Mapping
    |--------------------------------------------------------------------------
    |
    | Map plugin slugs to their Anystack product IDs.
    | This is the ONLY place where product IDs should be configured.
    | Clients send plugin_slug, proxy maps to product_id.
    |
    | Format: 'vendor/plugin-slug' => env('ANYSTACK_PRODUCT_ID_SLUG')
    |
    */
    'products' => [
        'tallcms/pro' => env('ANYSTACK_PRODUCT_ID_PRO', 'a0cb2ba1-5edc-4cdf-8134-48f8497f18bf'),
        // Future plugins:
        // 'tallcms/seo' => env('ANYSTACK_PRODUCT_ID_SEO'),
        // 'tallcms/ecommerce' => env('ANYSTACK_PRODUCT_ID_ECOMMERCE'),
    ],

    // Allowed origins for CORS (add customer domains or use * for all)
    'allowed_origins' => env('LICENSE_PROXY_ALLOWED_ORIGINS', '*'),

    // Rate limiting (requests per minute per IP)
    'rate_limit' => env('LICENSE_PROXY_RATE_LIMIT', 10),
];
