<?php

use App\Http\Controllers\CmsPageController;
use App\Livewire\CmsPageRenderer;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// CMS Pages Routes - All content is handled as pages
Route::get('/pages', [CmsPageController::class, 'index'])->name('cms.pages.index');
Route::get('/page/{slug}', CmsPageRenderer::class)->name('cms.page');

// Catch-all route for any slug (blog posts, articles, etc.)
Route::get('/{slug}', CmsPageRenderer::class)->name('cms.dynamic-page');
