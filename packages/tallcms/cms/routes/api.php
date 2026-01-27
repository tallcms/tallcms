<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\Api\V1\AuthController;
use TallCms\Cms\Http\Controllers\Api\V1\CategoryController;
use TallCms\Cms\Http\Controllers\Api\V1\MediaCollectionController;
use TallCms\Cms\Http\Controllers\Api\V1\MediaController;
use TallCms\Cms\Http\Controllers\Api\V1\PageController;
use TallCms\Cms\Http\Controllers\Api\V1\PostController;
use TallCms\Cms\Http\Controllers\Api\V1\WebhookController;

/*
|--------------------------------------------------------------------------
| TallCMS REST API Routes
|--------------------------------------------------------------------------
|
| These routes provide full CRUD operations for Pages, Posts, Categories,
| and Media. Authentication is handled via Laravel Sanctum tokens.
|
*/

// Authentication (public, throttled)
Route::post('/auth/token', [AuthController::class, 'store'])
    ->name('auth.token.store');

// Authenticated routes
Route::middleware(['auth:sanctum', 'tallcms.token-expiry'])->group(function () {
    // Auth management
    Route::delete('/auth/token', [AuthController::class, 'destroy'])
        ->name('auth.token.destroy');
    Route::get('/auth/user', [AuthController::class, 'user'])
        ->name('auth.user');

    // Pages (with soft-delete support)
    Route::middleware('tallcms.abilities:pages:read')->group(function () {
        Route::get('/pages', [PageController::class, 'index'])
            ->name('pages.index');
        Route::get('/pages/{page}', [PageController::class, 'show'])
            ->name('pages.show');
        Route::get('/pages/{page}/revisions', [PageController::class, 'revisions'])
            ->name('pages.revisions');
    });

    Route::middleware('tallcms.abilities:pages:write')->group(function () {
        Route::post('/pages', [PageController::class, 'store'])
            ->name('pages.store');
        Route::put('/pages/{page}', [PageController::class, 'update'])
            ->name('pages.update');
        Route::post('/pages/{page}/restore', [PageController::class, 'restore'])
            ->name('pages.restore');
        Route::post('/pages/{page}/publish', [PageController::class, 'publish'])
            ->name('pages.publish');
        Route::post('/pages/{page}/unpublish', [PageController::class, 'unpublish'])
            ->name('pages.unpublish');
        Route::post('/pages/{page}/approve', [PageController::class, 'approve'])
            ->name('pages.approve');
        Route::post('/pages/{page}/reject', [PageController::class, 'reject'])
            ->name('pages.reject');
        Route::post('/pages/{page}/submit-for-review', [PageController::class, 'submitForReview'])
            ->name('pages.submit-for-review');
        Route::post('/pages/{page}/revisions/{revision}/restore', [PageController::class, 'restoreRevision'])
            ->name('pages.revisions.restore');
    });

    Route::middleware('tallcms.abilities:pages:delete')->group(function () {
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])
            ->name('pages.destroy');
        Route::delete('/pages/{page}/force', [PageController::class, 'forceDestroy'])
            ->name('pages.force-destroy');
    });

    // Posts (with soft-delete support)
    Route::middleware('tallcms.abilities:posts:read')->group(function () {
        Route::get('/posts', [PostController::class, 'index'])
            ->name('posts.index');
        Route::get('/posts/{post}', [PostController::class, 'show'])
            ->name('posts.show');
        Route::get('/posts/{post}/revisions', [PostController::class, 'revisions'])
            ->name('posts.revisions');
    });

    Route::middleware('tallcms.abilities:posts:write')->group(function () {
        Route::post('/posts', [PostController::class, 'store'])
            ->name('posts.store');
        Route::put('/posts/{post}', [PostController::class, 'update'])
            ->name('posts.update');
        Route::post('/posts/{post}/restore', [PostController::class, 'restore'])
            ->name('posts.restore');
        Route::post('/posts/{post}/publish', [PostController::class, 'publish'])
            ->name('posts.publish');
        Route::post('/posts/{post}/unpublish', [PostController::class, 'unpublish'])
            ->name('posts.unpublish');
        Route::post('/posts/{post}/approve', [PostController::class, 'approve'])
            ->name('posts.approve');
        Route::post('/posts/{post}/reject', [PostController::class, 'reject'])
            ->name('posts.reject');
        Route::post('/posts/{post}/submit-for-review', [PostController::class, 'submitForReview'])
            ->name('posts.submit-for-review');
        Route::post('/posts/{post}/revisions/{revision}/restore', [PostController::class, 'restoreRevision'])
            ->name('posts.revisions.restore');
    });

    Route::middleware('tallcms.abilities:posts:delete')->group(function () {
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])
            ->name('posts.destroy');
        Route::delete('/posts/{post}/force', [PostController::class, 'forceDestroy'])
            ->name('posts.force-destroy');
    });

    // Categories (no soft-delete)
    Route::middleware('tallcms.abilities:categories:read')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])
            ->name('categories.index');
        Route::get('/categories/{category}', [CategoryController::class, 'show'])
            ->name('categories.show');
        Route::get('/categories/{category}/posts', [CategoryController::class, 'posts'])
            ->name('categories.posts');
    });

    Route::middleware('tallcms.abilities:categories:write')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->name('categories.update');
    });

    Route::middleware('tallcms.abilities:categories:delete')->group(function () {
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy');
    });

    // Media Collections (must be defined BEFORE /media/{media} to avoid route collision)
    Route::middleware('tallcms.abilities:media:read')->group(function () {
        Route::get('/media/collections', [MediaCollectionController::class, 'index'])
            ->name('media-collections.index');
        Route::get('/media/collections/{collection}', [MediaCollectionController::class, 'show'])
            ->name('media-collections.show');
    });

    Route::middleware('tallcms.abilities:media:write')->group(function () {
        Route::post('/media/collections', [MediaCollectionController::class, 'store'])
            ->name('media-collections.store');
        Route::put('/media/collections/{collection}', [MediaCollectionController::class, 'update'])
            ->name('media-collections.update');
    });

    Route::middleware('tallcms.abilities:media:delete')->group(function () {
        Route::delete('/media/collections/{collection}', [MediaCollectionController::class, 'destroy'])
            ->name('media-collections.destroy');
    });

    // Media (no soft-delete)
    Route::middleware('tallcms.abilities:media:read')->group(function () {
        Route::get('/media', [MediaController::class, 'index'])
            ->name('media.index');
        Route::get('/media/{media}', [MediaController::class, 'show'])
            ->name('media.show');
    });

    Route::middleware('tallcms.abilities:media:write')->group(function () {
        Route::post('/media', [MediaController::class, 'store'])
            ->name('media.store');
        Route::put('/media/{media}', [MediaController::class, 'update'])
            ->name('media.update');
    });

    Route::middleware('tallcms.abilities:media:delete')->group(function () {
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])
            ->name('media.destroy');
    });

    // Webhooks
    Route::middleware('tallcms.abilities:webhooks:manage')->group(function () {
        Route::get('/webhooks', [WebhookController::class, 'index'])
            ->name('webhooks.index');
        Route::post('/webhooks', [WebhookController::class, 'store'])
            ->name('webhooks.store');
        Route::get('/webhooks/{webhook}', [WebhookController::class, 'show'])
            ->name('webhooks.show');
        Route::put('/webhooks/{webhook}', [WebhookController::class, 'update'])
            ->name('webhooks.update');
        Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])
            ->name('webhooks.destroy');
        Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test'])
            ->name('webhooks.test');
    });
});
