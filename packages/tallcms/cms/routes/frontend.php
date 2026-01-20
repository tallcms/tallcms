<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| TallCMS Frontend Routes (Plugin Mode)
|--------------------------------------------------------------------------
|
| These routes handle CMS page rendering for / and /{slug} paths.
| Only loaded when tallcms.plugin_mode.routes_enabled is true.
|
| WARNING: Without a prefix, this will register the / route and override
| your app's homepage. Set TALLCMS_ROUTES_PREFIX to use a different base path.
|
*/

use Illuminate\Support\Facades\Route;
use TallCms\Cms\Livewire\CmsPageRenderer;

// Route name prefix (defaults to 'tallcms.' in plugin mode)
$namePrefix = config('tallcms.plugin_mode.route_name_prefix', 'tallcms.');

Route::name($namePrefix)->middleware('tallcms.maintenance')->group(function () {
    // Build exclusion pattern with auto-excluded panel path
    $panelPath = preg_quote(config('tallcms.filament.panel_path', 'admin'), '/');
    $defaultExclusions = "^(?!{$panelPath}|app|api|livewire|sanctum|storage|build|vendor|health|_).*$";
    $pattern = config('tallcms.plugin_mode.route_exclusions', $defaultExclusions);

    // Homepage route - always register when routes are enabled
    // This WILL override your app's / route if no prefix is set!
    Route::get('/', CmsPageRenderer::class)
        ->defaults('slug', '/')
        ->name('cms.home');

    // Catch-all page route with exclusions
    Route::get('/{slug}', CmsPageRenderer::class)
        ->where('slug', $pattern)
        ->name('cms.page');
});
