<?php

namespace App\Models;

use TallCms\Cms\Models\Theme as BaseTheme;

/**
 * Theme model - extends the package's Theme model for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\Theme continues
 * to work. All functionality is provided by the tallcms/cms package.
 */
class Theme extends BaseTheme
{
    // All functionality inherited from TallCms\Cms\Models\Theme
}
