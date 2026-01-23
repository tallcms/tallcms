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
$defaultExclusions = "^(?!{$panelPath}|app|api|livewire|sanctum|storage|build|vendor|health|_).*$";
$pattern = config('tallcms.plugin_mode.route_exclusions', $defaultExclusions);

// Check i18n configuration
$i18nEnabled = tallcms_i18n_config('enabled', false);
$urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');
$hideDefault = tallcms_i18n_config('hide_default_locale', true);

Route::name($namePrefix)->middleware(['tallcms.maintenance', 'tallcms.set-locale'])->group(function () use ($i18nEnabled, $urlStrategy, $hideDefault, $pattern) {

    if ($i18nEnabled && $urlStrategy === 'prefix') {
        // Multilingual routes with locale prefix
        $registry = app(LocaleRegistry::class);
        $locales = $registry->getLocaleCodes();  // Internal format: es_mx
        $default = $registry->getDefaultLocale();

        foreach ($locales as $locale) {
            // Convert internal format to BCP-47 for public URLs
            // es_mx → es-MX (user-friendly, SEO-friendly)
            $publicPrefix = LocaleRegistry::toBcp47($locale);

            // Determine prefix: hide default locale if configured
            $prefix = ($hideDefault && $locale === $default) ? '' : $publicPrefix;

            // Generate route name suffix (skip for default locale when hidden)
            $nameSuffix = ($locale !== $default || ! $hideDefault) ? ".{$locale}" : '';

            Route::prefix($prefix)->group(function () use ($locale, $pattern, $nameSuffix) {
                // Homepage
                Route::get('/', CmsPageRenderer::class)
                    ->defaults('slug', '/')
                    ->defaults('locale', $locale)  // Internal format passed to controller
                    ->name('cms.home' . $nameSuffix);

                // Pages (exclude locale codes from slug to prevent collision)
                Route::get('/{slug}', CmsPageRenderer::class)
                    ->where('slug', $pattern)
                    ->defaults('locale', $locale)  // Internal format passed to controller
                    ->name('cms.page' . $nameSuffix);
            });
        }
    } else {
        // Non-i18n routes (existing behavior) or url_strategy=none
        Route::get('/', CmsPageRenderer::class)
            ->defaults('slug', '/')
            ->name('cms.home');

        Route::get('/{slug}', CmsPageRenderer::class)
            ->where('slug', $pattern)
            ->name('cms.page');
    }
});
