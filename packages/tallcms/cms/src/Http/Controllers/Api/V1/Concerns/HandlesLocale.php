<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

trait HandlesLocale
{
    /**
     * Get the locale from request (query param or header).
     */
    protected function getLocale(Request $request): ?string
    {
        return $request->input('locale') ?? $request->header('X-Locale');
    }

    /**
     * Check if translations should be included in response.
     */
    protected function shouldIncludeTranslations(Request $request): bool
    {
        return filter_var($request->input('with_translations', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the effective locale for the request.
     * Falls back to default locale if none specified.
     */
    protected function getEffectiveLocale(Request $request): string
    {
        return $this->getLocale($request)
            ?? config('tallcms.i18n.default_locale', 'en');
    }
}
