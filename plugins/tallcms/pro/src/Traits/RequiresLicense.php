<?php

namespace Tallcms\Pro\Traits;

use Tallcms\Pro\Services\LicenseService;

trait RequiresLicense
{
    /**
     * Check if the Pro license is valid (includes grace period)
     */
    protected static function isLicenseActive(): bool
    {
        try {
            $licenseService = app(LicenseService::class);

            // Use isValid() which handles caching, grace periods, etc.
            return $licenseService->isValid();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Wrap content with license watermark if unlicensed
     */
    protected static function wrapWithLicenseCheck(string $content): string
    {
        if (static::isLicenseActive()) {
            return $content;
        }

        return static::addWatermark($content);
    }

    /**
     * Add watermark overlay to unlicensed content
     */
    protected static function addWatermark(string $content): string
    {
        $watermark = <<<'HTML'
<div class="tallcms-pro-unlicensed relative">
    <div class="absolute inset-0 bg-gray-900/5 dark:bg-gray-100/5 backdrop-blur-[1px] z-10 flex items-center justify-center pointer-events-none">
        <div class="bg-amber-100 dark:bg-amber-900/50 border border-amber-300 dark:border-amber-700 rounded-lg px-4 py-2 shadow-lg transform -rotate-2">
            <span class="text-amber-800 dark:text-amber-200 text-sm font-medium">
                TallCMS Pro - Unlicensed
            </span>
        </div>
    </div>
    <div class="opacity-75">
        CONTENT_PLACEHOLDER
    </div>
</div>
HTML;

        return str_replace('CONTENT_PLACEHOLDER', $content, $watermark);
    }
}
