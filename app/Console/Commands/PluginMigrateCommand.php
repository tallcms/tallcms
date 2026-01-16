<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\PluginMigrateCommand as BasePluginMigrateCommand;

/**
 * PluginMigrateCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\PluginMigrateCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginMigrateCommand extends BasePluginMigrateCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\PluginMigrateCommand
}
