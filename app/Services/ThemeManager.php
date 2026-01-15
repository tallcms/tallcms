<?php

namespace App\Services;

use TallCms\Cms\Services\ThemeManager as BaseThemeManager;

/**
 * ThemeManager - extends the package's ThemeManager for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\ThemeManager
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeManager extends BaseThemeManager
{
    // All functionality inherited from TallCms\Cms\Services\ThemeManager
}
