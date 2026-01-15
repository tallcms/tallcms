<?php

declare(strict_types=1);

/**
 * TallCMS Helper Functions
 *
 * These helper functions provide convenient access to TallCMS functionality.
 * They are designed to gracefully degrade when the theme or plugin system
 * is not configured (plugin mode with null paths).
 *
 * NOTE: This file is NOT autoloaded yet. It will be added to composer.json
 * autoload.files in Phase 2 after the models and services it depends on
 * (ThemeResolver, ThemeManager, CmsPost, etc.) are extracted to the package.
 *
 * Until then, the app's existing helpers.php takes precedence.
 */

// Theme Helper Functions

if (! function_exists('theme')) {
    /**
     * Get the current active theme instance
     *
     * @return \TallCms\Cms\Contracts\ThemeInterface
     */
    function theme(): \TallCms\Cms\Contracts\ThemeInterface
    {
        return \TallCms\Cms\Services\ThemeResolver::getCurrentTheme();
    }
}

if (! function_exists('theme_colors')) {
    /**
     * Get the current theme's color palette
     *
     * @return array<string, mixed>
     */
    function theme_colors(): array
    {
        return theme()->getColorPalette();
    }
}

if (! function_exists('theme_button_presets')) {
    /**
     * Get the current theme's button presets
     *
     * @return array<string, mixed>
     */
    function theme_button_presets(): array
    {
        return theme()->getButtonPresets();
    }
}

if (! function_exists('theme_text_presets')) {
    /**
     * Get the current theme's text presets
     *
     * Returns sensible defaults if no theme is active (plugin mode).
     *
     * @return array<string, mixed>
     */
    function theme_text_presets(): array
    {
        // Graceful degradation for plugin mode without themes
        if (! app()->bound('theme.manager') ||
            config('tallcms.plugin_mode.themes_path') === null) {
            return [
                'primary' => [
                    'heading' => '#111827',
                    'description' => '#4b5563',
                    'link' => '#2563eb',
                    'link_hover' => '#1d4ed8',
                ],
            ];
        }

        return theme()->getTextPresets();
    }
}

if (! function_exists('theme_padding_presets')) {
    /**
     * Get the current theme's padding presets
     *
     * @return array<string, mixed>
     */
    function theme_padding_presets(): array
    {
        return theme()->getPaddingPresets();
    }
}

// Multi-Theme System Helper Functions

if (! function_exists('theme_manager')) {
    /**
     * Get the theme manager instance
     *
     * @return \TallCms\Cms\Services\ThemeManager
     */
    function theme_manager(): \TallCms\Cms\Services\ThemeManager
    {
        return app(\TallCms\Cms\Services\ThemeManager::class);
    }
}

if (! function_exists('active_theme')) {
    /**
     * Get the active theme instance
     *
     * @return \TallCms\Cms\Models\Theme
     */
    function active_theme(): \TallCms\Cms\Models\Theme
    {
        return theme_manager()->getActiveTheme();
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Get theme asset URL with fallback
     *
     * @param  string  $path  Path to the asset relative to theme's public directory
     * @return string The URL to the asset
     */
    function theme_asset(string $path): string
    {
        return theme_manager()->themeAsset($path);
    }
}

if (! function_exists('theme_vite_assets')) {
    /**
     * Get theme Vite assets from manifest
     *
     * @param  array<string>  $entrypoints  List of Vite entrypoints
     * @return array<string, string> Resolved asset URLs
     */
    function theme_vite_assets(array $entrypoints): array
    {
        return theme_manager()->getThemeViteAssets($entrypoints);
    }
}

if (! function_exists('has_theme_override')) {
    /**
     * Check if current theme has override for specific view
     *
     * @param  string  $viewPath  Path to the view to check
     * @return bool True if theme has an override
     */
    function has_theme_override(string $viewPath): bool
    {
        return active_theme()->hasViewOverride($viewPath);
    }
}

// AWS / Storage Helper Functions

if (! function_exists('cms_media_disk')) {
    /**
     * Get the disk name for CMS media uploads
     *
     * Returns 's3' if S3-compatible storage is configured, otherwise 'public'.
     * Supports multiple configuration scenarios:
     * - Explicit FILESYSTEM_DISK=s3 in environment
     * - AWS S3 with static credentials
     * - IAM roles / instance profiles (no static keys)
     * - S3-compatible providers with custom endpoints
     *
     * @return string The disk name ('s3' or 'public')
     */
    function cms_media_disk(): string
    {
        // First, check if FILESYSTEM_DISK is explicitly set to s3
        $defaultDisk = config('filesystems.default');
        if ($defaultDisk === 's3') {
            return 's3';
        }

        // Fallback: check if S3 bucket is configured (supports IAM roles)
        $bucket = config('filesystems.disks.s3.bucket');
        if (! empty($bucket)) {
            return 's3';
        }

        return 'public';
    }
}

if (! function_exists('cms_media_visibility')) {
    /**
     * Get the visibility setting for CMS media uploads
     *
     * @return string The visibility setting
     */
    function cms_media_visibility(): string
    {
        return 'public';
    }
}

if (! function_exists('cms_uses_s3')) {
    /**
     * Check if CMS is configured to use S3 for media storage
     *
     * @return bool True if using S3 storage
     */
    function cms_uses_s3(): bool
    {
        return cms_media_disk() === 's3';
    }
}

// CMS Content Helper Functions

if (! function_exists('cms_post_url')) {
    /**
     * Generate URL for a post within a parent page context
     *
     * @param  \TallCms\Cms\Models\CmsPost  $post  The post to generate URL for
     * @param  string  $parentSlug  The parent page slug (e.g., 'blog')
     * @return string The full URL to the post
     */
    function cms_post_url(\TallCms\Cms\Models\CmsPost $post, string $parentSlug): string
    {
        $slug = trim($parentSlug, '/') . '/' . $post->slug;

        return route('cms.page', ['slug' => $slug]);
    }
}
