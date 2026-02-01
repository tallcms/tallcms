<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Contracts\Auth\Authenticatable;

class WidgetRegistry
{
    protected array $widgets = [
        'recent-posts' => [
            'label' => 'Recent Posts',
            'description' => 'Display latest blog posts',
            'component' => 'tallcms::widgets.recent-posts',
            'settings_schema' => [
                'limit' => ['type' => 'number', 'default' => 5, 'label' => 'Number of posts'],
                'show_image' => ['type' => 'boolean', 'default' => true, 'label' => 'Show thumbnails'],
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'description' => 'List of post categories',
            'component' => 'tallcms::widgets.categories',
            'settings_schema' => [
                'show_count' => ['type' => 'boolean', 'default' => true, 'label' => 'Show post count'],
            ],
        ],
        'toc' => [
            'label' => 'Table of Contents',
            'description' => 'Auto-generated from page headings',
            'component' => 'tallcms::widgets.table-of-contents',
            'settings_schema' => [
                'max_depth' => ['type' => 'number', 'default' => 3, 'label' => 'Max heading depth (2-4)'],
            ],
        ],
        'search' => [
            'label' => 'Search',
            'description' => 'Site search box',
            'component' => 'tallcms::widgets.search',
            'settings_schema' => [],
        ],
        'custom-html' => [
            'label' => 'Custom HTML',
            'description' => 'Custom HTML/embed content (admin only)',
            'component' => 'tallcms::widgets.custom-html',
            'settings_schema' => [
                'content' => ['type' => 'textarea', 'default' => '', 'label' => 'HTML Content'],
            ],
            'requires_permission' => 'ManageSettings',
        ],
    ];

    /**
     * Get all available widgets, optionally filtered by user permissions.
     */
    public function getAvailableWidgets(?Authenticatable $user = null): array
    {
        if ($user === null) {
            // Return all widgets without permission checks
            return $this->widgets;
        }

        return collect($this->widgets)->filter(function ($widget) use ($user) {
            // If no permission required, allow
            if (! isset($widget['requires_permission'])) {
                return true;
            }

            // Check if user has the required permission
            return $user->can($widget['requires_permission']);
        })->toArray();
    }

    /**
     * Get widget options for use in a Select field.
     */
    public function getWidgetOptions(?Authenticatable $user = null): array
    {
        $widgets = $this->getAvailableWidgets($user);

        return collect($widgets)->mapWithKeys(function ($config, $slug) {
            return [$slug => $config['label']];
        })->toArray();
    }

    /**
     * Get a single widget configuration.
     */
    public function getWidget(string $slug): ?array
    {
        return $this->widgets[$slug] ?? null;
    }

    /**
     * Get the settings schema for a widget.
     */
    public function getSettingsSchema(string $slug): array
    {
        $widget = $this->getWidget($slug);

        return $widget['settings_schema'] ?? [];
    }

    /**
     * Get default settings for a widget.
     */
    public function getDefaultSettings(string $slug): array
    {
        $schema = $this->getSettingsSchema($slug);

        return collect($schema)->mapWithKeys(function ($config, $key) {
            return [$key => $config['default'] ?? null];
        })->toArray();
    }

    /**
     * Register a custom widget.
     */
    public function register(string $slug, array $config): void
    {
        $this->widgets[$slug] = $config;
    }

    /**
     * Check if a widget exists.
     */
    public function has(string $slug): bool
    {
        return isset($this->widgets[$slug]);
    }
}
