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
        // Maximum number of revisions to keep per content item
        // Set to null for unlimited revisions
        'revision_limit' => env('CMS_REVISION_LIMIT', 50),

        // Notification channels for workflow events
        // Available: 'mail', 'database'
        // Set to empty array to disable all notifications
        'notification_channels' => explode(',', env('CMS_NOTIFICATION_CHANNELS', 'mail,database')),

        // Default preview token expiry in hours
        'default_preview_expiry_hours' => 24,
    ],
];
