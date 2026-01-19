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
    | Route Configuration (Standalone Mode)
    |--------------------------------------------------------------------------
    |
    | These settings apply to standalone mode route naming and exclusions.
    |
    */
    'route_name_prefix' => env('TALLCMS_ROUTE_NAME_PREFIX', ''),

    'route_exclusions' => env(
        'TALLCMS_ROUTE_EXCLUSIONS',
        '^(?!preview|admin|livewire|storage|api|install).*'
    ),

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
        // Enable frontend CMS page routes for /{slug} paths.
        // Set TALLCMS_ROUTES_ENABLED=true in .env to enable.
        // Routes automatically exclude common app paths (admin, api, livewire, etc.)
        //
        // NOTE: The homepage (/) must be configured in your routes/web.php:
        //
        //     use TallCms\Cms\Livewire\CmsPageRenderer;
        //
        //     if (config('tallcms.plugin_mode.routes_enabled')) {
        //         Route::get('/', CmsPageRenderer::class)->defaults('slug', '/');
        //     } else {
        //         Route::get('/', fn () => view('welcome'));
        //     }
        //
        'routes_enabled' => env('TALLCMS_ROUTES_ENABLED', false),

        // Optional URL prefix for CMS routes (e.g., 'cms' results in /cms/about)
        // Leave empty for root-level routes (e.g., /about, /contact)
        // When empty, smart exclusions prevent conflicts with your app routes.
        'routes_prefix' => env('TALLCMS_ROUTES_PREFIX', ''),

        // Route name prefix for plugin mode (e.g., 'tallcms.' results in tallcms.cms.page)
        'route_name_prefix' => env('TALLCMS_PLUGIN_ROUTE_NAME_PREFIX', 'tallcms.'),

        // Route exclusion pattern - paths matching this regex are excluded from CMS routing.
        // Default excludes: admin, app, api, livewire, sanctum, and underscore-prefixed paths.
        // Customize if your app uses other reserved paths.
        'route_exclusions' => env('TALLCMS_ROUTE_EXCLUSIONS', '^(?!admin|app|api|livewire|sanctum|_).*$'),

        // Optional prefix for essential routes (preview, contact API) to avoid conflicts
        // e.g., 'tallcms' results in /tallcms/preview/page/{id}
        'essential_routes_prefix' => env('TALLCMS_ESSENTIAL_ROUTES_PREFIX', ''),

        // Enable the TallCMS plugin system.
        // When enabled, the Plugin Manager page is visible and third-party plugins can be loaded.
        // Set to false to disable the plugin system entirely.
        'plugins_enabled' => env('TALLCMS_PLUGINS_ENABLED', true),

        // Path to TallCMS plugins directory. Only used when plugins_enabled is true.
        // Defaults to base_path('plugins') if not set.
        'plugins_path' => env('TALLCMS_PLUGINS_PATH'),

        // Enable the TallCMS theme system.
        // When enabled, the Theme Manager page is visible and themes can be loaded.
        // Set to false to disable the theme system entirely.
        'themes_enabled' => env('TALLCMS_THEMES_ENABLED', true),

        // Path to TallCMS themes directory. Only used when themes_enabled is true.
        // Defaults to base_path('themes') if not set.
        'themes_path' => env('TALLCMS_THEMES_PATH'),

        // User model class. Must implement TallCmsUserContract.
        // Default works with standard Laravel User model with HasRoles trait.
        'user_model' => env('TALLCMS_USER_MODEL', 'App\\Models\\User'),

        // Skip installer.lock check for maintenance mode in plugin mode.
        // In plugin mode, the host app doesn't use TallCMS's installer,
        // so we assume the app is properly installed. Default: true
        'skip_installer_check' => env('TALLCMS_SKIP_INSTALLER_CHECK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for authentication guards used by TallCMS roles and
    | permissions. This should match your Filament panel's guard.
    |
    */
    'auth' => [
        // Guard name for roles and permissions (should match Filament panel guard)
        'guard' => env('TALLCMS_AUTH_GUARD', 'web'),

        // Login route for preview authentication redirect
        // Can be a route name (e.g., 'filament.admin.auth.login') or URL
        // Leave null to auto-detect Filament's login route
        'login_route' => env('TALLCMS_LOGIN_ROUTE'),
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
        // Panel ID for route generation in notifications
        // Used for constructing admin panel URLs like filament.{panel_id}.resources.*
        'panel_id' => env('TALLCMS_PANEL_ID', 'admin'),

        // Panel path for URL construction and middleware exclusions
        'panel_path' => env('TALLCMS_PANEL_PATH', 'admin'),

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
    | Plugin System
    |--------------------------------------------------------------------------
    |
    | Configuration for the TallCMS plugin system including license management.
    | The Plugin Manager UI is always available, but local plugin loading
    | requires explicit opt-in via plugin_mode.plugins_enabled.
    |
    */
    'plugins' => [
        // Path where plugins are stored
        'path' => env('TALLCMS_PLUGINS_PATH', base_path('plugins')),

        // Allow ZIP-based plugin uploads through admin UI
        'allow_uploads' => env('TALLCMS_PLUGIN_ALLOW_UPLOADS', true),

        // Maximum upload size for plugin ZIP files (bytes). Default: 50MB
        'max_upload_size' => env('TALLCMS_PLUGIN_MAX_UPLOAD_SIZE', 50 * 1024 * 1024),

        // Plugin discovery caching
        'cache_enabled' => env('TALLCMS_PLUGIN_CACHE_ENABLED', true),
        'cache_ttl' => 3600, // 1 hour

        // Automatically run plugin migrations on install
        'auto_migrate' => env('TALLCMS_PLUGIN_AUTO_MIGRATE', true),

        // License management settings
        'license' => [
            // License proxy URL for official TallCMS plugins
            'proxy_url' => env('TALLCMS_LICENSE_PROXY_URL', 'https://tallcms.com'),

            // Cache TTL for license validation results (seconds). Default: 6 hours
            'cache_ttl' => 21600,

            // Grace period when license server unreachable (days). Default: 7
            'offline_grace_days' => 7,

            // Grace period after license expiration (days). Default: 14
            'renewal_grace_days' => 14,

            // How often to check for updates (seconds). Default: 24 hours
            'update_check_interval' => 86400,
        ],

        // Official plugin catalog (shown in Plugin Manager)
        'catalog' => [
            'tallcms/pro' => [
                'name' => 'TallCMS Pro',
                'slug' => 'pro',
                'vendor' => 'tallcms',
                'description' => 'Advanced blocks, analytics, and integrations for TallCMS.',
                'author' => 'TallCMS',
                'homepage' => 'https://tallcms.com/pro',
                'icon' => 'heroicon-o-sparkles',
                'category' => 'official',
                'featured' => true,
                'download_url' => 'https://anystack.sh/download/tallcms-pro-plugin',
                'purchase_url' => 'https://checkout.anystack.sh/tallcms-pro-plugin',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme System
    |--------------------------------------------------------------------------
    |
    | Configuration for the TallCMS theme system. The Theme Manager UI is
    | always available, but theme loading requires explicit opt-in via
    | plugin_mode.themes_enabled in plugin mode.
    |
    */
    'themes' => [
        // Path where themes are stored
        'path' => env('TALLCMS_THEMES_PATH', base_path('themes')),

        // Allow ZIP-based theme uploads through admin UI
        'allow_uploads' => env('TALLCMS_THEME_ALLOW_UPLOADS', true),

        // Maximum upload size for theme ZIP files (bytes). Default: 100MB
        'max_upload_size' => env('TALLCMS_THEME_MAX_UPLOAD_SIZE', 100 * 1024 * 1024),

        // Theme discovery caching
        'cache_enabled' => env('TALLCMS_THEME_CACHE_ENABLED', false),
        'cache_ttl' => 3600, // 1 hour

        // Preview session duration (minutes)
        'preview_duration' => 30,

        // Rollback availability window (hours)
        'rollback_duration' => 24,
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
