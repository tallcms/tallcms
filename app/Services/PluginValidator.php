<?php

namespace App\Services;

use TallCms\Cms\Services\PluginValidator as BasePluginValidator;

/**
 * PluginValidator - extends the package's PluginValidator for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\PluginValidator
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginValidator extends BasePluginValidator
{
    // All functionality inherited from TallCms\Cms\Services\PluginValidator
}
