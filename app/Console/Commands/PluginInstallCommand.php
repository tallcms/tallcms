<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\PluginInstallCommand as BasePluginInstallCommand;

/**
 * PluginInstallCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\PluginInstallCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginInstallCommand extends BasePluginInstallCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\PluginInstallCommand
}
