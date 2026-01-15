<?php

if (! function_exists('theme')) {
    /**
     * Get the current active theme instance
     */
    function theme(): \TallCms\Cms\Contracts\ThemeInterface
    {
        return \TallCms\Cms\Services\ThemeResolver::getCurrentTheme();
    }
}

if (! function_exists('theme_colors')) {
    /**
     * Get the current theme's color palette
     */
    function theme_colors(): array
    {
        return theme()->getColorPalette();
    }
}

if (! function_exists('theme_button_presets')) {
    /**
     * Get the current theme's button presets
     */
    function theme_button_presets(): array
    {
        return theme()->getButtonPresets();
    }
}

if (! function_exists('theme_text_presets')) {
    /**
     * Get the current theme's text presets
     */
    function theme_text_presets(): array
    {
        return theme()->getTextPresets();
    }
}

if (! function_exists('theme_padding_presets')) {
    /**
     * Get the current theme's padding presets
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
     */
    function theme_manager(): \TallCms\Cms\Services\ThemeManager
    {
        return app(\TallCms\Cms\Services\ThemeManager::class);
    }
}

if (! function_exists('active_theme')) {
    /**
     * Get the active theme instance
     */
    function active_theme(): \TallCms\Cms\Models\Theme
    {
        return theme_manager()->getActiveTheme();
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Get theme asset URL with fallback
     */
    function theme_asset(string $path): string
    {
        return theme_manager()->themeAsset($path);
    }
}

if (! function_exists('theme_vite_assets')) {
    /**
     * Get theme Vite assets from manifest
     */
    function theme_vite_assets(array $entrypoints): array
    {
        return theme_manager()->getThemeViteAssets($entrypoints);
    }
}

if (! function_exists('has_theme_override')) {
    /**
     * Check if current theme has override for specific view
     */
    function has_theme_override(string $viewPath): bool
    {
        return active_theme()->hasViewOverride($viewPath);
    }
}

// DaisyUI Theme Helper Functions

if (! function_exists('daisyui_preset')) {
    /**
     * Get the active daisyUI preset name
     */
    function daisyui_preset(): string
    {
        return active_theme()?->getDaisyUIPreset() ?? 'light';
    }
}

if (! function_exists('daisyui_dark_preset')) {
    /**
     * Get the dark mode preset name
     */
    function daisyui_dark_preset(): ?string
    {
        return active_theme()?->getDaisyUIPrefersDark();
    }
}

if (! function_exists('daisyui_presets')) {
    /**
     * Get all available presets for theme-controller
     */
    function daisyui_presets(): array
    {
        return active_theme()?->getDaisyUIPresets() ?? ['light'];
    }
}

if (! function_exists('supports_theme_controller')) {
    /**
     * Check if theme supports runtime theme switching
     */
    function supports_theme_controller(): bool
    {
        return active_theme()?->supportsThemeController() ?? false;
    }
}

// AWS / Storage Helper Functions

if (! function_exists('cms_media_disk')) {
    /**
     * Get the disk name for CMS media uploads
     * Returns 's3' if S3-compatible storage is configured, otherwise 'public'
     *
     * Supports multiple configuration scenarios:
     * - Explicit FILESYSTEM_DISK=s3 in environment
     * - AWS S3 with static credentials
     * - IAM roles / instance profiles (no static keys)
     * - S3-compatible providers with custom endpoints
     */
    function cms_media_disk(): string
    {
        // First, check if FILESYSTEM_DISK is explicitly set to s3
        $defaultDisk = config('filesystems.default');
        if ($defaultDisk === 's3') {
            return 's3';
        }

        // Fallback: check if S3 bucket is configured (supports IAM roles without static keys)
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
     */
    function cms_media_visibility(): string
    {
        return 'public';
    }
}

if (! function_exists('cms_uses_s3')) {
    /**
     * Check if CMS is configured to use S3 for media storage
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
        $slug = trim($parentSlug, '/').'/'.$post->slug;

        return route('cms.page', ['slug' => $slug]);
    }
}
