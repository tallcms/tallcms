<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Archive Routes
|--------------------------------------------------------------------------
|
| These routes handle blog archive functionality: RSS feeds, category
| archives, and author archives. Unlike core SEO routes (sitemap/robots),
| these can potentially conflict with host app routes.
|
| Controlled by: tallcms.plugin_mode.archive_routes_enabled (default: false)
| Optional prefix: tallcms.plugin_mode.archive_routes_prefix (default: '')
|
| Routes registered:
|   /feed                  - Main RSS feed
|   /feed/category/{slug}  - Category RSS feed
|   /category/{slug}       - Category archive page
|   /author/{slug}         - Author archive page
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\AuthorArchiveController;
use TallCms\Cms\Http\Controllers\CategoryArchiveController;
use TallCms\Cms\Http\Controllers\RssFeedController;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // RSS Feed Routes
    Route::get('/feed', [RssFeedController::class, 'index'])->name('feed');
    Route::get('/feed/category/{slug}', [RssFeedController::class, 'category'])->name('feed.category');

    // Archive Routes
    Route::get('/category/{slug}', [CategoryArchiveController::class, 'show'])->name('category.show');
    Route::get('/author/{authorSlug}', [AuthorArchiveController::class, 'show'])->name('author.show');
});
