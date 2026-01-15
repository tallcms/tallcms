<?php

namespace App\Http\Middleware;

use TallCms\Cms\Http\Middleware\ThemePreviewMiddleware as BaseThemePreviewMiddleware;

/**
 * ThemePreviewMiddleware - extends the package's middleware for backwards compatibility.
 *
 * This class exists so that existing code using App\Http\Middleware\ThemePreviewMiddleware
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemePreviewMiddleware extends BaseThemePreviewMiddleware
{
    // All functionality inherited from TallCms\Cms\Http\Middleware\ThemePreviewMiddleware
}
