<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;
use TallCms\Cms\Support\InstallationStatus;

/**
 * Redirect bare / to the locale-prefixed homepage when configured.
 *
 * Only applies when i18n uses prefixed URLs with the default locale visible
 * (hide_default_locale=false) and redirect_root_to_locale is enabled.
 */
class RedirectRootToLocaleMiddleware
{
    public function __construct(protected LocaleRegistry $registry) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        // Cheap early exit for non-root paths before any install/DB checks.
        if ($request->getPathInfo() !== '/') {
            return $next($request);
        }

        if (InstallationStatus::isIncomplete()) {
            return $next($request);
        }

        if (! tallcms_i18n_config('enabled', false)) {
            return $next($request);
        }

        if (config('tallcms.i18n.url_strategy', 'prefix') !== 'prefix') {
            return $next($request);
        }

        if (tallcms_i18n_config('hide_default_locale', true)) {
            return $next($request);
        }

        if (! (bool) SiteSetting::get(
            'redirect_root_to_locale',
            config('tallcms.i18n.redirect_root_to_locale', false)
        )) {
            return $next($request);
        }

        $locale = $this->resolveRedirectLocale($request);
        $target = tallcms_localized_url('/', $locale);

        if ($query = $request->getQueryString()) {
            $target .= (str_contains($target, '?') ? '&' : '?').$query;
        }

        return redirect()->to($target, 301);
    }

    protected function resolveRedirectLocale(Request $request): string
    {
        $siteLocale = $this->resolveSiteLocale($request);

        if ($siteLocale && $this->registry->isValidLocale($siteLocale)) {
            return $siteLocale;
        }

        return $this->registry->getDefaultLocale();
    }

    protected function resolveSiteLocale(Request $request): ?string
    {
        if (app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');

                if (! $resolver->isResolved()) {
                    $resolver->resolve($request);
                }

                $site = $resolver->get();

                if ($site && ! empty($site->locale)) {
                    return LocaleRegistry::normalizeLocaleCode((string) $site->locale);
                }
            } catch (\Throwable) {
                // Fall through to standalone lookup
            }
        }

        try {
            $site = Site::getDefault() ?? Site::query()->first();

            if ($site && ! empty($site->locale)) {
                return LocaleRegistry::normalizeLocaleCode((string) $site->locale);
            }
        } catch (\Throwable) {
            // Table may not exist yet
        }

        return null;
    }
}
