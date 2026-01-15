<?php

namespace App\Models;

use TallCms\Cms\Models\PluginLicense as BasePluginLicense;

/**
 * PluginLicense - extends the package's PluginLicense for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\PluginLicense
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginLicense extends BasePluginLicense
{
    // All functionality inherited from TallCms\Cms\Models\PluginLicense
}
