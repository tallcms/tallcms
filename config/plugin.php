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
];
