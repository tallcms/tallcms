<?php

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\ContactFormController;
use TallCms\Cms\Http\Controllers\PreviewController;
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

// Clean CMS routing - all pages handled by one route with maintenance mode check
// Maintenance middleware now handles installation checks internally
Route::middleware('tallcms.maintenance')->group(function () {
    Route::get('/', CmsPageRenderer::class)->defaults('slug', '/')->name('tallcms.cms.home');
    // Exclude preview, admin, livewire, storage, api, and install routes
    // Supports nested slugs for posts (e.g., /blog/my-post)
    Route::get('/{slug}', CmsPageRenderer::class)
        ->where('slug', '^(?!preview|admin|livewire|storage|api|install).*')
        ->name('tallcms.cms.page');
});

