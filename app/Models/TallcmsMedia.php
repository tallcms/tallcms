<?php

namespace App\Models;

use TallCms\Cms\Models\TallcmsMedia as BaseTallcmsMedia;

/**
 * TallcmsMedia - extends the package's TallcmsMedia for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\TallcmsMedia
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallcmsMedia extends BaseTallcmsMedia
{
    // All functionality inherited from TallCms\Cms\Models\TallcmsMedia
}
