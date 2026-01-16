<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Frontend Routes (Plugin Mode - Optional)
|--------------------------------------------------------------------------
|
| These routes handle CMS page rendering on the frontend.
| Only loaded when tallcms.plugin_mode.routes_enabled is true.
|
| Essential routes (preview, contact API) are loaded separately
| and are always available for admin functionality.
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Livewire\CmsPageRenderer;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->group(function () {
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
