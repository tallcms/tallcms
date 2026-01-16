<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\ThemeActivate as BaseThemeActivate;

/**
 * ThemeActivate - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\ThemeActivate
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeActivate extends BaseThemeActivate
{
    // All functionality inherited from TallCms\Cms\Console\Commands\ThemeActivate
}
