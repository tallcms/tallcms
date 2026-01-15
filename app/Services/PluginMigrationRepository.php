<?php

namespace App\Services;

use TallCms\Cms\Services\PluginMigrationRepository as BasePluginMigrationRepository;

/**
 * PluginMigrationRepository - extends the package's PluginMigrationRepository for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\PluginMigrationRepository
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginMigrationRepository extends BasePluginMigrationRepository
{
    // All functionality inherited from TallCms\Cms\Services\PluginMigrationRepository
}
