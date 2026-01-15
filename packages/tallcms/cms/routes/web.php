<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Package Routes (Plugin Mode Only)
|--------------------------------------------------------------------------
|
| These routes are loaded ONLY in plugin mode when routes_enabled is true.
| In standalone mode, routes are defined in the app's routes/web.php
| using App wrapper classes for full customization.
|
| Plugin mode routes use the tallcms.* prefix by default to avoid conflicts.
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Http\Controllers\ContactFormController;
use TallCms\Cms\Http\Controllers\PreviewController;
use TallCms\Cms\Livewire\CmsPageRenderer;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->group(function () {
    // Contact API
    if (config('tallcms.plugin_mode.api_routes_enabled', true)) {
        Route::post('/api/tallcms/contact', [ContactFormController::class, 'submit'])
            ->name('contact.submit');
    }

    // Preview routes
    if (config('tallcms.plugin_mode.preview_routes_enabled', true)) {
        Route::get('/preview/share/{token}', [PreviewController::class, 'tokenPreview'])
            ->middleware('throttle:60,1')
            ->name('preview.token');

        Route::middleware('auth')->group(function () {
            Route::get('/preview/page/{page}', [PreviewController::class, 'page'])
                ->name('preview.page');
            Route::get('/preview/post/{post}', [PreviewController::class, 'post'])
                ->name('preview.post');
        });
    }

    // Catch-all CMS routes (disabled by default in plugin mode)
    if (config('tallcms.plugin_mode.catch_all_enabled', false)) {
        Route::middleware('tallcms.maintenance')->group(function () {
            Route::get('/', CmsPageRenderer::class)
                ->defaults('slug', '/')
                ->name('cms.home');

            $pattern = config('tallcms.plugin_mode.route_exclusions', '.*');

            Route::get('/{slug}', CmsPageRenderer::class)
                ->where('slug', $pattern)
                ->name('cms.page');
        });
    }
});
