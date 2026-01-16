<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\ThemeBuild as BaseThemeBuild;

/**
 * ThemeBuild - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\ThemeBuild
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeBuild extends BaseThemeBuild
{
    // All functionality inherited from TallCms\Cms\Console\Commands\ThemeBuild
}
