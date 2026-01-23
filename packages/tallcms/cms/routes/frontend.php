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

// Custom route exclusions from config (plugin mode customization)
// This can be either:
// 1. A negative lookahead pattern like ^(?!foo|bar).*$ - will be merged with base exclusions
// 2. Any other regex - will be used as-is (replaces the default pattern entirely)
$customExclusions = config('tallcms.plugin_mode.route_exclusions');
$customExclusionsIsStandard = false;

if ($customExclusions && preg_match('/^\^\(\?!(.+)\)\.\*\$$/', $customExclusions, $matches)) {
    // Standard negative lookahead format - extract and merge with base exclusions
    $customList = $matches[1];
    if (! str_contains($baseExclusions, $customList)) {
        $baseExclusions = "{$baseExclusions}|{$customList}";
    }
    $customExclusionsIsStandard = true;
}

// Check i18n configuration
// For route registration, we use config directly (not database) to avoid timing issues.
// The config value is authoritative for route structure; DB overrides are for runtime behavior.
$i18nEnabled = config('tallcms.i18n.enabled', false);
$urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');
$hideDefault = config('tallcms.i18n.hide_default_locale', true);

Route::name($namePrefix)->middleware(['tallcms.maintenance', 'tallcms.set-locale'])->group(function () use ($i18nEnabled, $urlStrategy, $hideDefault, $baseExclusions, $customExclusions, $customExclusionsIsStandard) {

    if ($i18nEnabled && $urlStrategy === 'prefix') {
        // Multilingual routes with locale prefix
        $registry = app(LocaleRegistry::class);
        $locales = $registry->getLocaleCodes();  // Internal format: es_mx
        $default = $registry->getDefaultLocale();

        // Build locale exclusion pattern for the catch-all route (when hideDefault=true)
        $localeExclusions = [];
        foreach ($locales as $locale) {
            if ($locale !== $default) {
                $bcp47 = LocaleRegistry::toBcp47($locale);
                $localeExclusions[] = preg_quote($bcp47, '/');
            }
        }
        $localePattern = $localeExclusions ? '|' . implode('|', $localeExclusions) : '';

        // Register ALL locale routes with prefixes
        foreach ($locales as $locale) {
            $publicPrefix = LocaleRegistry::toBcp47($locale);
            $isDefault = ($locale === $default);

            // When hideDefault=true and this is default locale, use empty prefix
            // When hideDefault=false, ALL locales get prefixes (no unprefixed routes)
            $prefix = ($hideDefault && $isDefault) ? '' : $publicPrefix;

            // Route name suffix: default locale without prefix gets no suffix
            $nameSuffix = ($hideDefault && $isDefault) ? '' : ".{$locale}";

            $pattern = "^(?!{$baseExclusions}).*$";

            Route::prefix($prefix)->group(function () use ($locale, $pattern, $nameSuffix, $hideDefault, $isDefault, $localePattern, $baseExclusions) {
                // For unprefixed default locale routes, exclude other locale prefixes
                $routePattern = ($hideDefault && $isDefault)
                    ? "^(?!{$baseExclusions}{$localePattern}).*$"
                    : $pattern;

                Route::get('/', CmsPageRenderer::class)
                    ->defaults('slug', '/')
                    ->defaults('locale', $locale)
                    ->name('cms.home' . $nameSuffix);

                Route::get('/{slug}', CmsPageRenderer::class)
                    ->where('slug', $routePattern)
                    ->defaults('locale', $locale)
                    ->name('cms.page' . $nameSuffix);
            });
        }
    } else {
        // Non-i18n routes (existing behavior) or url_strategy=none
        // Use custom regex as-is if provided and not standard format, otherwise build from base exclusions
        $pattern = ($customExclusions && ! $customExclusionsIsStandard)
            ? $customExclusions
            : "^(?!{$baseExclusions}).*$";

        Route::get('/', CmsPageRenderer::class)
            ->defaults('slug', '/')
            ->name('cms.home');

        Route::get('/{slug}', CmsPageRenderer::class)
            ->where('slug', $pattern)
            ->name('cms.page');
    }
});
