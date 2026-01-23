<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TallCms\Cms\Services\LocaleRegistry;

/**
 * Middleware to detect and set the application locale based on URL, session, or browser.
 *
 * Detection priority:
 * 1. URL prefix (for 'prefix' strategy): /es/about
 * 2. Query parameter (for 'none' strategy): ?lang=es
 * 3. Session (if remember_locale enabled)
 * 4. Accept-Language header
 * 5. Default locale
 */
class SetLocaleMiddleware
{
    public function __construct(protected LocaleRegistry $registry) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use helper to check DB settings first, then config
        if (! tallcms_i18n_config('enabled', false)) {
            return $next($request);
        }

        $locale = $this->detectLocale($request);

        app()->setLocale($locale);

        // Store in session if enabled (this one stays in config - not admin-editable)
        if (config('tallcms.i18n.remember_locale', true)) {
            session(['tallcms_locale' => $locale]);
        }

        return $next($request);
    }

    /**
     * Detect locale from various sources.
     */
    protected function detectLocale(Request $request): string
    {
        $validLocales = $this->registry->getLocaleCodes();
        $default = $this->registry->getDefaultLocale();
        // url_strategy stays in config (structural, not admin-editable)
        $urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');

        // 1. Check URL prefix (only for 'prefix' strategy)
        // Normalize to handle both /es-MX and /es_mx formats
        if ($urlStrategy === 'prefix') {
            $firstSegment = $request->segment(1) ?? '';
            if ($firstSegment) {
                $normalized = LocaleRegistry::normalizeLocaleCode($firstSegment);
                if (in_array($normalized, $validLocales, true)) {
                    return $normalized;
                }
            }
            // In prefix mode, fall through to session/browser detection
            // Do NOT check ?lang - URL is authoritative in prefix mode
        }

        // 2. Check query param (ONLY for 'none' strategy)
        // Normalize to handle both ?lang=es-MX and ?lang=es_mx formats
        if ($urlStrategy === 'none') {
            $queryLocale = $request->query('lang', '');
            if ($queryLocale) {
                $normalized = LocaleRegistry::normalizeLocaleCode($queryLocale);
                if (in_array($normalized, $validLocales, true)) {
                    return $normalized;
                }
            }
        }

        // 3. Check session (normalize in case old format stored)
        if (config('tallcms.i18n.remember_locale') && session()->has('tallcms_locale')) {
            $sessionLocale = LocaleRegistry::normalizeLocaleCode(session('tallcms_locale'));
            if (in_array($sessionLocale, $validLocales, true)) {
                return $sessionLocale;
            }
        }

        // 4. Check Accept-Language header (normalized)
        $browserLocale = $this->parseAcceptLanguage($request, $validLocales);
        if ($browserLocale) {
            return $browserLocale;
        }

        return $default;
    }

    /**
     * Parse Accept-Language header with normalization.
     * Handles: en-US → en, es-419 → es, zh-Hans → zh
     */
    protected function parseAcceptLanguage(Request $request, array $validLocales): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        if (! $acceptLanguage) {
            return null;
        }

        // Parse and sort by quality
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $part = trim($part);
            if (str_contains($part, ';q=')) {
                [$lang, $q] = explode(';q=', $part);
                $quality = (float) $q;
            } else {
                $lang = $part;
                $quality = 1.0;
            }
            $languages[$lang] = $quality;
        }
        arsort($languages);

        // Try to match with normalization
        foreach (array_keys($languages) as $lang) {
            // Normalize to Laravel format (underscore, lowercase): en-US → en_us
            $normalized = LocaleRegistry::normalizeLocaleCode($lang);

            // Exact match with normalized code
            if (in_array($normalized, $validLocales, true)) {
                return $normalized;
            }

            // Try base language only: en_us → en
            $base = explode('_', $normalized)[0];
            if (in_array($base, $validLocales, true)) {
                return $base;
            }
        }

        return null;
    }
}
