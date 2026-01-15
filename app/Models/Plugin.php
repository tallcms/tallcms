<?php

namespace App\Models;

use TallCms\Cms\Models\Plugin as BasePlugin;

/**
 * Plugin - extends the package's Plugin for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\Plugin
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class Plugin extends BasePlugin
{
    // All functionality inherited from TallCms\Cms\Models\Plugin
}
