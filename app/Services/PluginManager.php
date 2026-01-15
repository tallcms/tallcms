<?php

namespace App\Services;

use TallCms\Cms\Services\PluginManager as BasePluginManager;

/**
 * PluginManager - extends the package's PluginManager for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\PluginManager
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginManager extends BasePluginManager
{
    // All functionality inherited from TallCms\Cms\Services\PluginManager
}
