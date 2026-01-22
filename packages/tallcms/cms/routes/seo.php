<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS SEO Routes
|--------------------------------------------------------------------------
|
| These routes handle SEO-related functionality: sitemap, robots.txt,
| RSS feeds, and archive pages. They are loaded when seo_routes_enabled
| is true (default), regardless of the main routes_enabled setting.
|
| These routes don't conflict with your app since they use standard
| SEO paths (/sitemap.xml, /robots.txt) and dedicated archive prefixes.
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\AuthorArchiveController;
use TallCms\Cms\Http\Controllers\CategoryArchiveController;
use TallCms\Cms\Http\Controllers\RobotsController;
use TallCms\Cms\Http\Controllers\RssFeedController;
use TallCms\Cms\Http\Controllers\SitemapController;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // Sitemap Routes
    Route::get('/robots.txt', [RobotsController::class, 'index'])->name('seo.robots');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('seo.sitemap');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('seo.sitemap.pages');
    Route::get('/sitemap-posts-{page}.xml', [SitemapController::class, 'posts'])->name('seo.sitemap.posts')->where('page', '[0-9]+');
    Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('seo.sitemap.categories');
    Route::get('/sitemap-authors.xml', [SitemapController::class, 'authors'])->name('seo.sitemap.authors');

    // RSS Feed Routes
    Route::get('/feed', [RssFeedController::class, 'index'])->name('feed');
    Route::get('/feed/category/{slug}', [RssFeedController::class, 'category'])->name('feed.category');

    // Archive Routes
    Route::get('/category/{slug}', [CategoryArchiveController::class, 'show'])->name('category.show');
    Route::get('/author/{authorSlug}', [AuthorArchiveController::class, 'show'])->name('author.show');
});
