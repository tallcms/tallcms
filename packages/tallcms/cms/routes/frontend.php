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
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\AuthorArchiveController;
use TallCms\Cms\Http\Controllers\CategoryArchiveController;
use TallCms\Cms\Http\Controllers\RobotsController;
use TallCms\Cms\Http\Controllers\RssFeedController;
use TallCms\Cms\Http\Controllers\SitemapController;
use TallCms\Cms\Livewire\CmsPageRenderer;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // Build exclusion pattern with auto-excluded panel path
    $panelPath = preg_quote(config('tallcms.filament.panel_path', 'admin'), '/');
    $defaultExclusions = "^(?!{$panelPath}|app|api|livewire|sanctum|storage|build|vendor|health|_).*$";
    $pattern = config('tallcms.plugin_mode.route_exclusions', $defaultExclusions);

    // SEO Routes (before catch-all)
    Route::get('/robots.txt', [RobotsController::class, 'index'])->name('seo.robots');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('seo.sitemap');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('seo.sitemap.pages');
    Route::get('/sitemap-posts-{page}.xml', [SitemapController::class, 'posts'])->name('seo.sitemap.posts')->where('page', '[0-9]+');
    Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('seo.sitemap.categories');
    Route::get('/sitemap-authors.xml', [SitemapController::class, 'authors'])->name('seo.sitemap.authors');

    // RSS Feed Routes (before catch-all)
    Route::get('/feed', [RssFeedController::class, 'index'])->name('feed');
    Route::get('/feed/category/{slug}', [RssFeedController::class, 'category'])->name('feed.category');

    // Archive Routes (before catch-all)
    Route::get('/category/{slug}', [CategoryArchiveController::class, 'show'])->name('category.show');
    Route::get('/author/{authorSlug}', [AuthorArchiveController::class, 'show'])->name('author.show');

    // Homepage route - always register when routes are enabled
    // This WILL override your app's / route if no prefix is set!
    Route::get('/', CmsPageRenderer::class)
        ->defaults('slug', '/')
        ->name('cms.home');

    // Catch-all page route with exclusions
    Route::get('/{slug}', CmsPageRenderer::class)
        ->where('slug', $pattern)
        ->name('cms.page');
});
