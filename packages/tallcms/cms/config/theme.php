<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Theme
    |--------------------------------------------------------------------------
    |
    | The slug of the currently active theme. This is managed by ThemeManager
    | and should not be manually edited unless necessary.
    |
    */
    'active' => env('TALLCMS_THEME', 'talldaisy'),

    /*
    |--------------------------------------------------------------------------
    | Theme Caching
    |--------------------------------------------------------------------------
    |
    | Enable caching of theme discovery results for improved performance
    | in production environments.
    |
    */
    'cache_enabled' => env('TALLCMS_THEME_CACHE', false),

    // Cache time-to-live in seconds (1 hour default)
    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Theme Preview
    |--------------------------------------------------------------------------
    |
    | Duration in minutes for theme preview sessions.
    | After this period, the preview expires and reverts to active theme.
    |
    */
    'preview_duration' => 30,

    /*
    |--------------------------------------------------------------------------
    | Theme Rollback
    |--------------------------------------------------------------------------
    |
    | Duration in hours that rollback to previous theme is available.
    | After this period, the rollback option expires.
    |
    */
    'rollback_duration' => 24,

    /*
    |--------------------------------------------------------------------------
    | Theme Uploads (Standalone Mode Only)
    |--------------------------------------------------------------------------
    |
    | Allow uploading themes via ZIP file in the admin panel.
    | Disabled in plugin mode for security - use Composer instead.
    |
    */
    'allow_uploads' => env('TALLCMS_THEME_UPLOADS', true),
];
