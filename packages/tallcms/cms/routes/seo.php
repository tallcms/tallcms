<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Core SEO Routes
|--------------------------------------------------------------------------
|
| These routes handle essential SEO functionality: sitemap and robots.txt.
| They are ALWAYS registered at the root level (no prefix) since search
| engines expect these files at standard locations.
|
| Controlled by: tallcms.plugin_mode.seo_routes_enabled (default: true)
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\RobotsController;
use TallCms\Cms\Http\Controllers\SitemapController;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // Core SEO routes - must be at root for search engine discovery
    Route::get('/robots.txt', [RobotsController::class, 'index'])->name('seo.robots');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('seo.sitemap');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('seo.sitemap.pages');
    Route::get('/sitemap-posts-{page}.xml', [SitemapController::class, 'posts'])->name('seo.sitemap.posts')->where('page', '[0-9]+');
    Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('seo.sitemap.categories');
    Route::get('/sitemap-authors.xml', [SitemapController::class, 'authors'])->name('seo.sitemap.authors');
});
