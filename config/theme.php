<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Theme
    |--------------------------------------------------------------------------
    |
    | This value determines which theme is currently active. The theme must
    | be registered in the 'available' array below and implement the
    | ThemeInterface.
    |
    */
    'active' => env('ACTIVE_THEME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    |
    | Register all available themes here. Each theme must implement the
    | ThemeInterface and provide complete color palettes and presets.
    |
    */
    'available' => [
        'default' => \App\Support\ThemeColors::class,
        // Add custom themes here:
        // 'custom' => \App\Themes\CustomTheme::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Cache
    |--------------------------------------------------------------------------
    |
    | Whether to cache theme colors and presets for better performance.
    | When enabled, theme changes may require cache clearing.
    |
    */
    'cache_enabled' => env('THEME_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Theme Discovery
    |--------------------------------------------------------------------------
    |
    | Automatically discover themes in specified directories.
    | All discovered themes must implement ThemeInterface.
    |
    */
    'auto_discover' => [
        'enabled' => env('THEME_AUTO_DISCOVER', false),
        'paths' => [
            app_path('Themes'),
        ],
    ],
];