<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Frontend Routes (Plugin Mode)
|--------------------------------------------------------------------------
|
| These routes handle CMS page rendering on the frontend.
| Only loaded when tallcms.plugin_mode.routes_enabled is true.
|
| Set TALLCMS_ROUTES_PREFIX in .env to add a prefix (e.g., /cms/about).
| Leave it empty for root-level routes (e.g., /about).
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Livewire\CmsPageRenderer;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // Homepage route
    Route::get('/', CmsPageRenderer::class)
        ->defaults('slug', '/')
        ->name('cms.home');

    // Page routes - exclude common app paths to avoid conflicts
    // This regex excludes: admin paths, api paths, livewire, sanctum, etc.
    $defaultExclusions = '^(?!admin|app|api|livewire|sanctum|_).*$';
    $pattern = config('tallcms.plugin_mode.route_exclusions', $defaultExclusions);

    Route::get('/{slug}', CmsPageRenderer::class)
        ->where('slug', $pattern)
        ->name('cms.page');
});
