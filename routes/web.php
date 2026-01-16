<?php

use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\PreviewController;
use App\Livewire\CmsPageRenderer;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TallCMS Routes (Standalone Mode)
|--------------------------------------------------------------------------
|
| Route names use tallcms.* prefix for consistency with plugin mode.
| Legacy aliases (preview.*, contact.submit) are registered below
| for backwards compatibility with existing code and bookmarks.
|
*/

// Contact form submission (AJAX endpoint)
Route::post('/api/tallcms/contact', [ContactFormController::class, 'submit'])->name('tallcms.contact.submit');

// Token-based preview route (public, for sharing with external users) - MUST be before catch-all
Route::get('/preview/share/{token}', [PreviewController::class, 'tokenPreview'])
    ->middleware('throttle:60,1')
    ->name('tallcms.preview.token');

// Preview routes (admin only, can view drafts) - MUST be defined before catch-all route
Route::middleware(['auth'])->group(function () {
    Route::get('/preview/page/{page}', [PreviewController::class, 'page'])->name('tallcms.preview.page');
    Route::get('/preview/post/{post}', [PreviewController::class, 'post'])->name('tallcms.preview.post');
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

/*
|--------------------------------------------------------------------------
| Legacy Route Aliases (Backwards Compatibility)
|--------------------------------------------------------------------------
|
| These aliases ensure existing code using old route names continues to work.
| They can be removed in a future major version.
|
*/

// Legacy contact form endpoint (also register at old URL for backwards compat)
Route::post('/api/contact', [ContactFormController::class, 'submit'])->name('contact.submit');

// Legacy preview route aliases
Route::get('/preview/share/{token}', [PreviewController::class, 'tokenPreview'])
    ->middleware('throttle:60,1')
    ->name('preview.token');

Route::middleware(['auth'])->group(function () {
    Route::get('/preview/page/{page}', [PreviewController::class, 'page'])->name('preview.page');
    Route::get('/preview/post/{post}', [PreviewController::class, 'post'])->name('preview.post');
});
