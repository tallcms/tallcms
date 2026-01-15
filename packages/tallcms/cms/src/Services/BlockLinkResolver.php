<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use TallCms\Cms\Models\CmsPage;

class BlockLinkResolver
{
    /**
     * Resolve button URL based on link type and configuration
     */
    public static function resolveButtonUrl(array $config, string $prefix = 'button'): string
    {
        $linkType = $config["{$prefix}_link_type"] ?? 'custom';

        switch ($linkType) {
            case 'page':
                $pageId = $config["{$prefix}_page_id"] ?? null;
                if ($pageId) {
                    $page = CmsPage::where('id', $pageId)->where('status', 'published')->first();
                    if ($page) {
                        return $page->is_homepage ? '/' : '/'.$page->slug;
                    }
                }

                return '#';

            case 'external':
            case 'custom':
                $url = trim($config["{$prefix}_url"] ?? '');

                // Validate external URLs, allow internal paths and anchors
                if ($url && (str_starts_with(strtolower($url), 'http://') || str_starts_with(strtolower($url), 'https://'))) {
                    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '#';
                }

                // Internal paths and anchors are allowed as-is
                return $url ?: '#';

            default:
                return '#';
        }
    }

    /**
     * Check if the button should be rendered
     */
    public static function shouldRenderButton(array $config, string $prefix = 'button'): bool
    {
        $buttonText = $config["{$prefix}_text"] ?? '';
        $linkType = $config["{$prefix}_link_type"] ?? 'custom';

        if (empty($buttonText)) {
            return false;
        }

        // For page links, ensure a page is selected
        if ($linkType === 'page') {
            $pageId = $config["{$prefix}_page_id"] ?? null;

            return ! empty($pageId);
        }

        // For external/custom links, ensure URL is provided
        if (in_array($linkType, ['external', 'custom'])) {
            $url = $config["{$prefix}_url"] ?? '';

            return ! empty($url);
        }

        return true;
    }
}
