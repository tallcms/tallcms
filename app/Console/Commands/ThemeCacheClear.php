<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\ThemeCacheClear as BaseThemeCacheClear;

/**
 * ThemeCacheClear - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\ThemeCacheClear
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeCacheClear extends BaseThemeCacheClear
{
    // All functionality inherited from TallCms\Cms\Console\Commands\ThemeCacheClear
}
