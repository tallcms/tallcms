<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;

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
                        $siteType = SiteSetting::get('site_type', 'multi-page');
                        if ($siteType === 'single-page') {
                            return $page->is_homepage ? '#top' : '#'.tallcms_slug_to_anchor($page->slug, $page->id);
                        }

                        // Use localized URL helper which handles routes prefix and locale
                        $slug = tallcms_i18n_enabled()
                            ? ($page->getTranslation('slug', app()->getLocale(), false) ?? $page->getTranslation('slug', app(LocaleRegistry::class)->getDefaultLocale()))
                            : $page->slug;

                        return $page->is_homepage ? tallcms_localized_url('/') : tallcms_localized_url($slug);
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

                // Anchors are allowed as-is
                if (str_starts_with($url, '#')) {
                    return $url;
                }

                // Internal paths go through localized URL helper
                return $url ? tallcms_localized_url($url) : '#';

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
