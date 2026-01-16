<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\PluginListCommand as BasePluginListCommand;

/**
 * PluginListCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\PluginListCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginListCommand extends BasePluginListCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\PluginListCommand
}
