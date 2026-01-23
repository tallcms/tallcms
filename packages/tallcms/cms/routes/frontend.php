<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Frontend Routes (Plugin Mode)
|--------------------------------------------------------------------------
|
| These routes handle CMS page rendering for / and /{slug} paths.
| Only loaded when tallcms.plugin_mode.routes_enabled is true.
|
| WARNING: Without a prefix, this will register the / route and override
| your app's homepage. Set TALLCMS_ROUTES_PREFIX to use a different base path.
|
| When i18n is enabled with 'prefix' strategy, routes are registered as:
| - /{locale}/           (homepage for each locale)
| - /{locale}/{slug}     (pages for each locale)
|
| The default locale can be hidden (/ instead of /en/) via hide_default_locale config.
|
| NOTE: SEO routes (sitemap, robots.txt, RSS, archives) are in routes/seo.php
| and loaded separately via seo_routes_enabled (default: true).
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Livewire\CmsPageRenderer;
use TallCms\Cms\Services\LocaleRegistry;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

// Build exclusion pattern with auto-excluded panel path
$panelPath = preg_quote(config('tallcms.filament.panel_path', 'admin'), '/');
$baseExclusions = "{$panelPath}|app|api|livewire|sanctum|storage|build|vendor|health|_";

// Check i18n configuration
// For route registration, we use config directly (not database) to avoid timing issues.
// The config value is authoritative for route structure; DB overrides are for runtime behavior.
$i18nEnabled = config('tallcms.i18n.enabled', false);
$urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');
$hideDefault = config('tallcms.i18n.hide_default_locale', true);

Route::name($namePrefix)->middleware(['tallcms.maintenance', 'tallcms.set-locale'])->group(function () use ($i18nEnabled, $urlStrategy, $hideDefault, $baseExclusions) {

    if ($i18nEnabled && $urlStrategy === 'prefix') {
        // Multilingual routes with locale prefix
        $registry = app(LocaleRegistry::class);
        $locales = $registry->getLocaleCodes();  // Internal format: es_mx
        $default = $registry->getDefaultLocale();

        // Build locale exclusion pattern for non-prefixed routes
        // This prevents the default locale's {slug} from matching /zh-CN/...
        $localeExclusions = [];
        foreach ($locales as $locale) {
            if ($locale !== $default || !$hideDefault) {
                $bcp47 = LocaleRegistry::toBcp47($locale);
                $localeExclusions[] = preg_quote($bcp47, '/');
            }
        }
        $localePattern = $localeExclusions ? '|' . implode('|', $localeExclusions) : '';

        // Register non-default locale routes FIRST (more specific)
        foreach ($locales as $locale) {
            if ($locale === $default && $hideDefault) {
                continue; // Skip default locale, handle it last
            }

            $publicPrefix = LocaleRegistry::toBcp47($locale);
            $nameSuffix = ".{$locale}";
            $pattern = "^(?!{$baseExclusions}).*$";

            Route::prefix($publicPrefix)->group(function () use ($locale, $pattern, $nameSuffix) {
                Route::get('/', CmsPageRenderer::class)
                    ->defaults('slug', '/')
                    ->defaults('locale', $locale)
                    ->name('cms.home' . $nameSuffix);

                Route::get('/{slug}', CmsPageRenderer::class)
                    ->where('slug', $pattern)
                    ->defaults('locale', $locale)
                    ->name('cms.page' . $nameSuffix);
            });
        }

        // Register default locale routes LAST (catch-all, excludes other locale prefixes)
        $defaultPattern = "^(?!{$baseExclusions}{$localePattern}).*$";
        Route::get('/', CmsPageRenderer::class)
            ->defaults('slug', '/')
            ->defaults('locale', $default)
            ->name('cms.home');

        Route::get('/{slug}', CmsPageRenderer::class)
            ->where('slug', $defaultPattern)
            ->defaults('locale', $default)
            ->name('cms.page');
    } else {
        // Non-i18n routes (existing behavior) or url_strategy=none
        $pattern = config('tallcms.plugin_mode.route_exclusions', "^(?!{$baseExclusions}).*$");
        Route::get('/', CmsPageRenderer::class)
            ->defaults('slug', '/')
            ->name('cms.home');

        Route::get('/{slug}', CmsPageRenderer::class)
            ->where('slug', $pattern)
            ->name('cms.page');
    }
});
