<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TallCMS Version
    |--------------------------------------------------------------------------
    |
    | The current version of TallCMS. Used for theme compatibility checking.
    | This is the single source of truth for version comparisons.
    |
    */
    'version' => '2.0.0',

    /*
    |--------------------------------------------------------------------------
    | Operation Mode
    |--------------------------------------------------------------------------
    |
    | Determines how TallCMS operates. Auto-detection works in most cases:
    | - 'standalone': Full TallCMS installation (tallcms/tallcms skeleton)
    | - 'plugin': Installed as a plugin in existing Filament app
    | - null: Auto-detect based on .tallcms-standalone marker file
    |
    */
    'mode' => env('TALLCMS_MODE'),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Table prefix for all TallCMS tables. Default 'tallcms_' maintains
    | compatibility with v1.x installations. Can be customized in plugin
    | mode to avoid conflicts with existing tables.
    |
    */
    'database' => [
        'prefix' => env('TALLCMS_TABLE_PREFIX', 'tallcms_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Mode Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to plugin mode operation. These settings are
    | ignored in standalone mode.
    |
    */
    'plugin_mode' => [
        // Enable frontend routes. IMPORTANT: When enabled, routes_prefix is REQUIRED
        // to prevent the catch-all /{slug} route from conflicting with your app's routes.
        'routes_enabled' => env('TALLCMS_ROUTES_ENABLED', false),

        // URL prefix for CMS routes (e.g., 'cms' results in /cms/about, /cms/blog)
        // REQUIRED when routes_enabled is true. Must not be empty.
        'routes_prefix' => env('TALLCMS_ROUTES_PREFIX'),

        // Enable the TallCMS plugin system in plugin mode.
        // When false (default), PluginServiceProvider skips all plugin loading.
        // Set to true to enable third-party TallCMS plugins in your Filament app.
        'plugins_enabled' => env('TALLCMS_PLUGINS_ENABLED', false),

        // Path to TallCMS plugins directory. Only used when plugins_enabled is true.
        // Defaults to base_path('plugins') if not set.
        'plugins_path' => env('TALLCMS_PLUGINS_PATH'),

        // Enable the TallCMS theme system in plugin mode.
        // When false (default), ThemeServiceProvider skips all theme loading.
        // Set to true to enable TallCMS themes in your Filament app.
        'themes_enabled' => env('TALLCMS_THEMES_ENABLED', false),

        // Path to TallCMS themes directory. Only used when themes_enabled is true.
        // Defaults to base_path('themes') if not set.
        'themes_path' => env('TALLCMS_THEMES_PATH'),

        // User model class. Must implement TallCmsUserContract.
        // Default works with standard Laravel User model with HasRoles trait.
        'user_model' => env('TALLCMS_USER_MODEL', 'App\\Models\\User'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Panel Configuration
    |--------------------------------------------------------------------------
    |
    | These settings are dynamically set by TallCmsPlugin when registered.
    | They allow customization of navigation group and sort order.
    |
    */
    'filament' => [
        'navigation_group' => 'CMS',
        'navigation_sort' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Information
    |--------------------------------------------------------------------------
    |
    | Default contact information used in templates and merge tags.
    |
    */
    'contact_email' => env('TALLCMS_CONTACT_EMAIL'),
    'company_name' => env('TALLCMS_COMPANY_NAME'),
    'company_address' => env('TALLCMS_COMPANY_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Publishing Workflow
    |--------------------------------------------------------------------------
    |
    | Configuration for the content publishing workflow including
    | revision history and preview tokens.
    |
    */
    'publishing' => [
        // Maximum number of automatic revisions to keep per content item.
        // Set to null for unlimited. Default: 100
        'revision_limit' => env('CMS_REVISION_LIMIT', 100),

        // Maximum number of manual (pinned) snapshots to keep per content item.
        // Set to null for unlimited. Default: 50
        'revision_manual_limit' => env('CMS_REVISION_MANUAL_LIMIT', 50),

        // Notification channels for workflow events
        // Available: 'mail', 'database'
        'notification_channels' => explode(',', env('CMS_NOTIFICATION_CHANNELS', 'mail,database')),

        // Default preview token expiry in hours
        'default_preview_expiry_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | System Updates (Standalone Mode Only)
    |--------------------------------------------------------------------------
    |
    | Configuration for the one-click update system. These settings are
    | IGNORED in plugin mode - use Composer for updates instead.
    |
    */
    'updates' => [
        // Enable or disable the update system (standalone mode only)
        'enabled' => env('TALLCMS_UPDATES_ENABLED', true),

        // How often to check for updates (seconds). Default: 24 hours
        'check_interval' => 86400,

        // Cache TTL for GitHub API responses (seconds). Default: 1 hour
        'cache_ttl' => 3600,

        // GitHub repository for updates
        'github_repo' => 'tallcms/tallcms',

        // Optional GitHub token for higher API rate limits
        'github_token' => env('TALLCMS_GITHUB_TOKEN'),

        // Number of backup sets to retain
        'backup_retention' => 3,

        // Automatically backup files before updating
        'auto_backup' => true,

        // Require database backup before update
        'require_db_backup' => true,

        // Maximum database size for automatic backup (bytes). Default: 100MB
        'db_backup_size_limit' => 100 * 1024 * 1024,

        // Ed25519 public key for release signature verification (hex-encoded)
        'public_key' => env('TALLCMS_UPDATE_PUBLIC_KEY', '6c41c964c60dd5341f7ba649dcda6e6de4b0b7afac2fbb9489527987907d35a9'),
    ],
];
