<?php

namespace App\Services;

use TallCms\Cms\Services\ThemeResolver as BaseThemeResolver;

/**
 * ThemeResolver - extends the package's ThemeResolver for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\ThemeResolver
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeResolver extends BaseThemeResolver
{
    // All functionality inherited from TallCms\Cms\Services\ThemeResolver
}
