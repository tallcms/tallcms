<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\TallcmsMenuItem;
use TallCms\Cms\Services\LocaleRegistry;

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

        if ($siteType === 'single-page') {
            // In SPA mode, everything links to anchors on homepage
            return $page->is_homepage ? '#top' : '#'.tallcms_slug_to_anchor($page->slug, $page->id);
        }

        // Multi-page mode - use localized URL helper (includes routes prefix and locale)
        // Get the localized slug for the current locale with fallback
        $slug = tallcms_i18n_enabled()
            ? ($page->getTranslation('slug', app()->getLocale(), false) ?? $page->getTranslation('slug', app(LocaleRegistry::class)->getDefaultLocale()))
            : $page->slug;

        if ($page->is_homepage) {
            return tallcms_localized_url('/');
        }

        return tallcms_localized_url($slug);
    }

    /**
     * Resolve custom URL, applying routes prefix and locale for relative paths.
     * Already-prefixed absolute paths are returned as-is to avoid double-prefixing.
     */
    protected function resolveCustomUrl(TallcmsMenuItem $item): ?string
    {
        $url = $item->url;

        if (! $url) {
            return null;
        }

        // Don't modify external URLs, anchors, or special protocols
        if (str_starts_with($url, 'http') || str_starts_with($url, '#') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        // Use helper that handles both clean slugs and already-prefixed paths
        return tallcms_resolve_custom_url($url);
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
