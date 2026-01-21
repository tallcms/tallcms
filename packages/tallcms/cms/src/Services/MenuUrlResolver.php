<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\TallcmsMenuItem;

class MenuUrlResolver
{
    public function resolve(TallcmsMenuItem $item): ?string
    {
        return match ($item->type) {
            'page' => $this->resolvePageUrl($item),
            'external' => $item->url,
            'custom' => $this->resolveCustomUrl($item),
            'separator', 'header' => null,
            default => null,
        };
    }

    protected function resolvePageUrl(TallcmsMenuItem $item): ?string
    {
        if (! $item->page) {
            return null;
        }

        $siteType = SiteSetting::get('site_type', 'multi-page');
        $page = $item->page;
        $prefix = $this->getRoutesPrefix();

        if ($siteType === 'single-page') {
            // In SPA mode, everything links to anchors on homepage
            return $page->is_homepage ? '#top' : '#'.tallcms_slug_to_anchor($page->slug, $page->id);
        }

        // Multi-page mode - homepage goes to prefix root, others use prefix + slug
        if ($page->is_homepage) {
            return $prefix ? '/'.$prefix : '/';
        }

        return $prefix ? '/'.$prefix.'/'.$page->slug : '/'.$page->slug;
    }

    /**
     * Resolve custom URL, applying routes prefix for relative paths
     */
    protected function resolveCustomUrl(TallcmsMenuItem $item): ?string
    {
        $url = $item->url;

        if (! $url) {
            return null;
        }

        // Don't modify external URLs or anchors
        if (str_starts_with($url, 'http') || str_starts_with($url, '#') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        // Apply routes prefix to relative URLs (both /about and about formats)
        $prefix = $this->getRoutesPrefix();
        if ($prefix) {
            // Normalize URL to have leading slash
            $normalizedUrl = '/'.ltrim($url, '/');

            // Avoid double prefixing - check if already has prefix with trailing slash
            if (! str_starts_with($normalizedUrl, '/'.$prefix.'/') && $normalizedUrl !== '/'.$prefix) {
                return '/'.$prefix.$normalizedUrl;
            }

            return $normalizedUrl;
        }

        return $url;
    }

    /**
     * Get the configured routes prefix for plugin mode
     */
    protected function getRoutesPrefix(): string
    {
        return trim(config('tallcms.plugin_mode.routes_prefix') ?? '', '/');
    }

    public function shouldOpenInNewTab(TallcmsMenuItem $item): bool
    {
        // External links default to new tab
        if ($item->type === 'external') {
            return $item->meta['open_in_new_tab'] ?? true;
        }

        return $item->meta['open_in_new_tab'] ?? false;
    }

    public function getTargetAttribute(TallcmsMenuItem $item): string
    {
        return $this->shouldOpenInNewTab($item) ? '_blank' : '_self';
    }
}
