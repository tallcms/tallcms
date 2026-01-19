<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Frontend Routes (Plugin Mode)
|--------------------------------------------------------------------------
|
| These routes handle CMS page rendering for /{slug} paths.
| Only loaded when tallcms.plugin_mode.routes_enabled is true.
|
| NOTE: The homepage (/) is NOT registered here. To let the CMS handle /,
| add this to your routes/web.php:
|
|     use TallCms\Cms\Livewire\CmsPageRenderer;
|
|     if (config('tallcms.plugin_mode.routes_enabled')) {
|         Route::get('/', CmsPageRenderer::class)->defaults('slug', '/');
|     } else {
|         Route::get('/', fn () => view('welcome'));
|     }
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Livewire\CmsPageRenderer;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // Page routes - exclude common app paths to avoid conflicts
    // This regex excludes: admin paths, api paths, livewire, sanctum, etc.
    $defaultExclusions = '^(?!admin|app|api|livewire|sanctum|_).*$';
    $pattern = config('tallcms.plugin_mode.route_exclusions', $defaultExclusions);

    Route::get('/{slug}', CmsPageRenderer::class)
        ->where('slug', $pattern)
        ->name('cms.page');
});
