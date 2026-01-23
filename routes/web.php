<?php

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\AuthorArchiveController;
use TallCms\Cms\Http\Controllers\CategoryArchiveController;
use TallCms\Cms\Http\Controllers\ContactFormController;
use TallCms\Cms\Http\Controllers\PreviewController;
use TallCms\Cms\Http\Controllers\RobotsController;
use TallCms\Cms\Http\Controllers\RssFeedController;
use TallCms\Cms\Http\Controllers\SitemapController;
use TallCms\Cms\Livewire\CmsPageRenderer;
use TallCms\Cms\Services\LocaleRegistry;

/*
|--------------------------------------------------------------------------
| TallCMS Routes (Standalone Mode)
|--------------------------------------------------------------------------
|
| All route names use tallcms.* prefix for consistency with plugin mode.
|
*/

// Contact form submission (AJAX endpoint)
Route::post('/api/tallcms/contact', [ContactFormController::class, 'submit'])->name('tallcms.contact.submit');

// Token-based preview route (public, for sharing with external users) - MUST be before catch-all
Route::get('/preview/share/{token}', [PreviewController::class, 'tokenPreview'])
    ->middleware('throttle:60,1')
    ->name('tallcms.preview.token');

// Preview routes (admin only, can view drafts) - MUST be defined before catch-all route
// Uses tallcms.preview-auth middleware for proper redirect to Filament login
Route::middleware(['tallcms.preview-auth'])->group(function () {
    Route::get('/preview/page/{page:id}', [PreviewController::class, 'page'])->name('tallcms.preview.page');
    Route::get('/preview/post/{post:id}', [PreviewController::class, 'post'])->name('tallcms.preview.post');
});

// Core SEO routes - MUST be at root level for search engine discovery
Route::middleware('tallcms.maintenance')->group(function () {
    Route::get('/robots.txt', [RobotsController::class, 'index'])->name('tallcms.seo.robots');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('tallcms.seo.sitemap');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('tallcms.seo.sitemap.pages');
    Route::get('/sitemap-posts-{page}.xml', [SitemapController::class, 'posts'])->name('tallcms.seo.sitemap.posts')->where('page', '[0-9]+');
    Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('tallcms.seo.sitemap.categories');
    Route::get('/sitemap-authors.xml', [SitemapController::class, 'authors'])->name('tallcms.seo.sitemap.authors');
});

// Archive routes (RSS feeds, category/author pages) - MUST be before catch-all
// In plugin mode these are opt-in via archive_routes_enabled config
Route::middleware('tallcms.maintenance')->group(function () {
    Route::get('/feed', [RssFeedController::class, 'index'])->name('tallcms.feed');
    Route::get('/feed/category/{slug}', [RssFeedController::class, 'category'])->name('tallcms.feed.category');
    Route::get('/category/{slug}', [CategoryArchiveController::class, 'show'])->name('tallcms.category.show');
    Route::get('/author/{authorSlug}', [AuthorArchiveController::class, 'show'])->name('tallcms.author.show');
});

// Clean CMS routing - all pages handled by one route with maintenance mode check
// Maintenance middleware now handles installation checks internally
// When i18n is enabled with 'prefix' strategy, routes are registered for each locale
$i18nEnabled = config('tallcms.i18n.enabled', false);
$urlStrategy = config('tallcms.i18n.url_strategy', 'prefix');
$hideDefault = config('tallcms.i18n.hide_default_locale', true);
$baseExclusions = 'preview|admin|livewire|storage|api|install|feed|sitemap|category|author|robots\.txt';

Route::middleware(['tallcms.maintenance', 'tallcms.set-locale'])->group(function () use ($i18nEnabled, $urlStrategy, $hideDefault, $baseExclusions) {
    if ($i18nEnabled && $urlStrategy === 'prefix') {
        // Multilingual routes with locale prefix
        $registry = app(LocaleRegistry::class);
        $locales = $registry->getLocaleCodes();
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

            $pattern = "^(?!{$baseExclusions}).*";

            Route::prefix($prefix)->group(function () use ($locale, $pattern, $nameSuffix, $hideDefault, $isDefault, $localePattern, $baseExclusions) {
                // For unprefixed default locale routes, exclude other locale prefixes
                $routePattern = ($hideDefault && $isDefault)
                    ? "^(?!{$baseExclusions}{$localePattern}).*"
                    : $pattern;

                Route::get('/', CmsPageRenderer::class)
                    ->defaults('slug', '/')
                    ->defaults('locale', $locale)
                    ->name('tallcms.cms.home' . $nameSuffix);

                Route::get('/{slug}', CmsPageRenderer::class)
                    ->where('slug', $routePattern)
                    ->defaults('locale', $locale)
                    ->name('tallcms.cms.page' . $nameSuffix);
            });
        }
    } else {
        // Non-i18n routes (existing behavior)
        $pattern = "^(?!{$baseExclusions}).*";
        Route::get('/', CmsPageRenderer::class)->defaults('slug', '/')->name('tallcms.cms.home');
        Route::get('/{slug}', CmsPageRenderer::class)
            ->where('slug', $pattern)
            ->name('tallcms.cms.page');
    }
});

