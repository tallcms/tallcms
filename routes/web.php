<?php

use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\CmsPostController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// CMS Pages Routes
Route::get('/pages', [CmsPageController::class, 'index'])->name('cms.pages.index');
Route::get('/page/{slug}', [CmsPageController::class, 'show'])->name('cms.page');

// CMS Posts Routes
Route::get('/blog', [CmsPostController::class, 'index'])->name('cms.posts.index');
Route::get('/blog/featured', [CmsPostController::class, 'featured'])->name('cms.posts.featured');
Route::get('/blog/category/{slug}', [CmsPostController::class, 'category'])->name('cms.posts.category');
Route::get('/blog/{slug}', [CmsPostController::class, 'show'])->name('cms.post');
