<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\ThemeList as BaseThemeList;

/**
 * ThemeList - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\ThemeList
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeList extends BaseThemeList
{
    // All functionality inherited from TallCms\Cms\Console\Commands\ThemeList
}
