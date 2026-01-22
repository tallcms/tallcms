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
Route::middleware('tallcms.maintenance')->group(function () {
    Route::get('/', CmsPageRenderer::class)->defaults('slug', '/')->name('tallcms.cms.home');
    // Exclude preview, admin, livewire, storage, api, install, feed, sitemap, category, author routes
    // Supports nested slugs for posts (e.g., /blog/my-post)
    Route::get('/{slug}', CmsPageRenderer::class)
        ->where('slug', '^(?!preview|admin|livewire|storage|api|install|feed|sitemap|category|author|robots\.txt).*')
        ->name('tallcms.cms.page');
});

