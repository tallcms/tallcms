<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\ThemeInstallCommand as BaseThemeInstallCommand;

/**
 * ThemeInstallCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\ThemeInstallCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ThemeInstallCommand extends BaseThemeInstallCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\ThemeInstallCommand
}
