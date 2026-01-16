<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\PluginUninstallCommand as BasePluginUninstallCommand;

/**
 * PluginUninstallCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\PluginUninstallCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginUninstallCommand extends BasePluginUninstallCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\PluginUninstallCommand
}
