<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\MakeTheme as BaseMakeTheme;

/**
 * MakeTheme - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\MakeTheme
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class MakeTheme extends BaseMakeTheme
{
    // All functionality inherited from TallCms\Cms\Console\Commands\MakeTheme
}
