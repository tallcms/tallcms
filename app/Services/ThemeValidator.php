<?php

namespace App\Services;

use TallCms\Cms\Services\ThemeValidator as BaseThemeValidator;

/**
 * ThemeValidator - extends the package's ThemeValidator for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\ThemeValidator
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeValidator extends BaseThemeValidator
{
    // All functionality inherited from TallCms\Cms\Services\ThemeValidator
}
