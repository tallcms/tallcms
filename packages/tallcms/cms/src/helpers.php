<?php

declare(strict_types=1);

/**
 * TallCMS Helper Functions
 *
 * These helper functions provide convenient access to TallCMS functionality.
 * They are designed to gracefully degrade when the theme or plugin system
 * is not configured (plugin mode with disabled themes/plugins).
 *
 * This file is autoloaded via composer.json autoload.files.
 */

// URL Helper Functions

if (! function_exists('tallcms_routes_prefix')) {
    /**
     * Get the configured routes prefix for CMS frontend routes
     *
     * In plugin mode, CMS routes can be prefixed (e.g., '/cms') to avoid
     * conflicts with the host application's routes.
     *
     * @return string The routes prefix (without leading/trailing slashes), or empty string
     */
    function tallcms_routes_prefix(): string
    {
        return trim(config('tallcms.plugin_mode.routes_prefix') ?? '', '/');
    }
}

if (! function_exists('tallcms_home_url')) {
    /**
     * Get the CMS homepage URL, respecting routes prefix
     *
     * @return string The full URL to the CMS homepage
     */
    function tallcms_home_url(): string
    {
        $prefix = tallcms_routes_prefix();

        return url($prefix ? '/'.$prefix : '/');
    }
}

if (! function_exists('tallcms_page_url')) {
    /**
     * Get a CMS page URL by slug, respecting routes prefix
     *
     * @param  string  $slug  The page slug
     * @return string The full URL to the page
     */
    function tallcms_page_url(string $slug): string
    {
        $prefix = tallcms_routes_prefix();
        $path = $prefix ? '/'.$prefix.'/'.$slug : '/'.$slug;

        return url($path);
    }
}

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
        // Graceful degradation for plugin mode without themes enabled
        if (! app()->bound('theme.manager') ||
            ! config('tallcms.plugin_mode.themes_enabled', false)) {
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
     * @return \TallCms\Cms\Models\Theme|null
     */
    function active_theme(): ?\TallCms\Cms\Models\Theme
    {
        return theme_manager()->getActiveTheme();
    }
}

if (! function_exists('daisyui_dark_preset')) {
    /**
     * Get the DaisyUI preset that represents the "dark" option
     *
     * @return string|null
     */
    function daisyui_dark_preset(): ?string
    {
        return active_theme()?->getDaisyUIPrefersDark();
    }
}

if (! function_exists('daisyui_presets')) {
    /**
     * Get all available presets for theme-controller
     *
     * @return array<string>
     */
    function daisyui_presets(): array
    {
        return active_theme()?->getDaisyUIPresets() ?? ['light'];
    }
}

if (! function_exists('supports_theme_controller')) {
    /**
     * Check if theme supports runtime theme switching
     *
     * @return bool
     */
    function supports_theme_controller(): bool
    {
        return active_theme()?->supportsThemeController() ?? false;
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

// Menu Helper Functions

if (! function_exists('isMenuItemActive')) {
    /**
     * Check if a menu item URL matches the current request
     */
    function isMenuItemActive(?string $itemUrl): bool
    {
        if (empty($itemUrl)) {
            return false;
        }

        // Check if item URL is external (different host)
        $itemHost = parse_url($itemUrl, PHP_URL_HOST);
        if ($itemHost && $itemHost !== request()->getHost()) {
            return false;
        }

        $currentUrl = request()->url();
        $currentPath = request()->path();

        // Normalize the item URL
        $itemUrl = rtrim($itemUrl, '/');
        $itemPath = parse_url($itemUrl, PHP_URL_PATH) ?? '/';
        $itemPath = rtrim($itemPath, '/') ?: '/';

        // Normalize current path
        $currentPath = '/' . ltrim($currentPath, '/');
        $currentPath = rtrim($currentPath, '/') ?: '/';

        // Exact match
        if ($currentUrl === $itemUrl || $currentPath === $itemPath) {
            return true;
        }

        // Homepage special case
        if ($itemPath === '/' || $itemPath === '/home') {
            return $currentPath === '/' || $currentPath === '/home';
        }

        return false;
    }
}

if (! function_exists('buildMenuItemArray')) {
    /**
     * Recursively build menu item array with children
     */
    function buildMenuItemArray($item): array
    {
        $url = $item->getResolvedUrl();
        $children = $item->children->map(function ($child) {
            return buildMenuItemArray($child);
        })->toArray();

        // Check if this item is active
        $isActive = isMenuItemActive($url);

        // Check if any child is active (for parent highlighting)
        $hasActiveChild = collect($children)->contains(function ($child) {
            return $child['is_active'] || $child['has_active_child'];
        });

        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $url,
            'type' => $item->type,
            'target' => app('menu.url.resolver')->getTargetAttribute($item),
            'icon' => $item->icon,
            'css_class' => $item->css_class,
            'is_active' => $isActive,
            'has_active_child' => $hasActiveChild,
            'children' => $children,
        ];
    }
}

if (! function_exists('menu')) {
    /**
     * Get a menu by location with resolved URLs
     */
    function menu(string $location): ?array
    {
        $menu = \TallCms\Cms\Models\TallcmsMenu::byLocation($location);

        if (! $menu) {
            return null;
        }

        // Get all menu items for this menu and build the tree structure
        $items = $menu->allItems()
            ->where('is_active', true)
            ->with('page')
            ->defaultOrder()
            ->get()
            ->toTree();

        return $items->map(function ($item) {
            return buildMenuItemArray($item);
        })->toArray();
    }
}

if (! function_exists('render_menu')) {
    /**
     * Render a menu as HTML
     */
    function render_menu(string $location, array $options = []): string
    {
        $menu = menu($location);

        if (! $menu) {
            return '';
        }

        $ulClass = $options['ul_class'] ?? 'menu';
        $liClass = $options['li_class'] ?? 'menu-item';
        $linkClass = $options['link_class'] ?? 'menu-link';

        $html = "<ul class=\"{$ulClass}\">";

        foreach ($menu as $item) {
            $html .= render_menu_item($item, $liClass, $linkClass);
        }

        $html .= '</ul>';

        return $html;
    }
}

if (! function_exists('render_menu_item')) {
    /**
     * Render a single menu item
     */
    function render_menu_item(array $item, string $liClass = '', string $linkClass = ''): string
    {
        $hasChildren = ! empty($item['children']);
        $liClass = trim($liClass . ($hasChildren ? ' has-children' : ''));

        if ($item['css_class']) {
            $liClass = trim($liClass . ' ' . $item['css_class']);
        }

        $html = "<li class=\"{$liClass}\">";

        if ($item['url']) {
            $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
            $icon = $item['icon'] ? "<i class=\"{$item['icon']}\"></i> " : '';

            $html .= "<a href=\"{$item['url']}\" class=\"{$linkClass}\"{$target}>";
            $html .= $icon . htmlspecialchars($item['label']);
            $html .= '</a>';
        } else {
            // Header/separator items without links
            $html .= "<span class=\"{$linkClass}\">" . htmlspecialchars($item['label']) . '</span>';
        }

        if ($hasChildren) {
            $html .= '<ul class="submenu">';
            foreach ($item['children'] as $child) {
                $html .= render_menu_item($child, 'submenu-item', $linkClass);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
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

        return route('tallcms.cms.page', ['slug' => $slug]);
    }
}
