<?php

namespace App\Services;

use TallCms\Cms\Services\PluginMigrator as BasePluginMigrator;

/**
 * PluginMigrator - extends the package's PluginMigrator for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\PluginMigrator
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginMigrator extends BasePluginMigrator
{
    // All functionality inherited from TallCms\Cms\Services\PluginMigrator
}
