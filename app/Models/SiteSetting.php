<?php

namespace App\Models;

use TallCms\Cms\Models\SiteSetting as BaseSiteSetting;

/**
 * SiteSetting - extends the package's SiteSetting for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\SiteSetting
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class SiteSetting extends BaseSiteSetting
{
    // All functionality inherited from TallCms\Cms\Models\SiteSetting
}
