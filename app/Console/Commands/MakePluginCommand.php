<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\MakePluginCommand as BaseMakePluginCommand;

/**
 * MakePluginCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\MakePluginCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class MakePluginCommand extends BaseMakePluginCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\MakePluginCommand
}
