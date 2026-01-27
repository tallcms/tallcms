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
    'version' => '2.8.3',

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
        // Automatic revisions are created on every save when content changes.
        // Set to null for unlimited. Default: 100
        'revision_limit' => env('CMS_REVISION_LIMIT', 100),

        // Maximum number of manual (pinned) snapshots to keep per content item.
        // Manual snapshots are created via the "Save Snapshot" action.
        // Set to null for unlimited. Default: 50
        // Note: Combined total of revisions = revision_limit + revision_manual_limit
        'revision_manual_limit' => env('CMS_REVISION_MANUAL_LIMIT', 50),

        // Notification channels for workflow events
        // Available: 'mail', 'database'
        // Set to empty array to disable all notifications
        'notification_channels' => explode(',', env('CMS_NOTIFICATION_CHANNELS', 'mail,database')),

        // Default preview token expiry in hours
        'default_preview_expiry_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | System Updates
    |--------------------------------------------------------------------------
    |
    | Configuration for the one-click update system. Updates are downloaded
    | from GitHub releases and verified using Ed25519 signatures.
    |
    */
    'updates' => [
        // Enable or disable the update system entirely
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

        // Require database backup before update (SQLite/MySQL/PostgreSQL)
        'require_db_backup' => true,

        // Maximum database size for automatic backup (bytes). Default: 100MB
        'db_backup_size_limit' => 100 * 1024 * 1024,

        // Ed25519 public key for release signature verification (hex-encoded, 64 chars)
        // This key is used to verify that releases are authentic and haven't been tampered with.
        // Generate a keypair with: php artisan tallcms:generate-keypair
        // The public key below is a placeholder - replace with your actual key.
        'public_key' => env('TALLCMS_UPDATE_PUBLIC_KEY', '6c41c964c60dd5341f7ba649dcda6e6de4b0b7afac2fbb9489527987907d35a9'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Internationalization (i18n)
    |--------------------------------------------------------------------------
    |
    | Configuration for multilingual content support.
    |
    */
    'i18n' => [
        // Master switch for multilingual features
        'enabled' => env('TALLCMS_I18N_ENABLED', false),

        // Available locales
        'locales' => [
            'en' => [
                'label' => 'English',
                'native' => 'English',
                'rtl' => false,
            ],
            'zh_CN' => [
                'label' => 'Chinese (Simplified)',
                'native' => '简体中文',
                'rtl' => false,
            ],
        ],

        // Default/fallback locale
        'default_locale' => env('TALLCMS_DEFAULT_LOCALE', 'en'),

        // URL strategy: 'prefix' (/en/about) or 'none' (query param)
        'url_strategy' => 'prefix',

        // Hide default locale from URL (/ instead of /en/)
        'hide_default_locale' => env('TALLCMS_HIDE_DEFAULT_LOCALE', true),
    ],
];
