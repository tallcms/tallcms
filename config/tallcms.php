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
    'version' => '1.0.0',

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
];
