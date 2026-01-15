<?php

namespace App\Services;

use TallCms\Cms\Services\MenuUrlResolver as BaseMenuUrlResolver;

/**
 * MenuUrlResolver - extends the package's MenuUrlResolver for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\MenuUrlResolver
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class MenuUrlResolver extends BaseMenuUrlResolver
{
    // All functionality inherited from TallCms\Cms\Services\MenuUrlResolver
}
