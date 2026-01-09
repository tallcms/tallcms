<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin Path
    |--------------------------------------------------------------------------
    |
    | The path where plugins are stored. Plugins follow the structure:
    | plugins/{vendor}/{slug}/
    |
    */
    'path' => base_path('plugins'),

    /*
    |--------------------------------------------------------------------------
    | Allow Uploads
    |--------------------------------------------------------------------------
    |
    | Enable or disable ZIP-based plugin uploads through the admin UI.
    | Set to false in environments where plugins should only be installed
    | via Composer.
    |
    */
    'allow_uploads' => env('PLUGIN_ALLOW_UPLOADS', true),

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Size
    |--------------------------------------------------------------------------
    |
    | Maximum upload size for plugin ZIP files in bytes.
    | Default: 50MB
    |
    */
    'max_upload_size' => env('PLUGIN_MAX_UPLOAD_SIZE', 50 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Plugin discovery caching configuration.
    |
    */
    'cache_enabled' => env('PLUGIN_CACHE_ENABLED', true),
    'cache_ttl' => 3600, // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Auto Migrate
    |--------------------------------------------------------------------------
    |
    | Automatically run plugin migrations on install. If disabled, migrations
    | must be run manually via the plugin:migrate command.
    |
    */
    'auto_migrate' => env('PLUGIN_AUTO_MIGRATE', true),

    /*
    |--------------------------------------------------------------------------
    | License Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for plugin license management.
    |
    */
    'license' => [
        // License proxy URL for official TallCMS plugins
        'proxy_url' => env('TALLCMS_LICENSE_PROXY_URL', 'https://tallcms.com'),

        // How long to cache license validation results (in seconds)
        // Default: 24 hours
        'cache_ttl' => 86400,

        // Number of days a license remains valid when the license server is unreachable
        // Default: 7 days
        'offline_grace_days' => 7,

        // Grace period after expiration before license is marked expired
        // Allows time for billing webhooks and renewal processing
        // Default: 14 days
        'renewal_grace_days' => 14,

        // Test license key prefix (for development/testing only)
        // Format: TALLCMS-{PRODUCT}-TEST-LICENSE
        'test_license_prefix' => 'TALLCMS-',
    ],
];
