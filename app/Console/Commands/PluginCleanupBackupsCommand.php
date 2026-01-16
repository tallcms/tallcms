<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\PluginCleanupBackupsCommand as BasePluginCleanupBackupsCommand;

/**
 * PluginCleanupBackupsCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\PluginCleanupBackupsCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginCleanupBackupsCommand extends BasePluginCleanupBackupsCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\PluginCleanupBackupsCommand
}
