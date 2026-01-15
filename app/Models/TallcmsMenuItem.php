<?php

namespace App\Models;

use TallCms\Cms\Models\TallcmsMenuItem as BaseTallcmsMenuItem;

/**
 * TallcmsMenuItem - extends the package's TallcmsMenuItem for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\TallcmsMenuItem
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallcmsMenuItem extends BaseTallcmsMenuItem
{
    // All functionality inherited from TallCms\Cms\Models\TallcmsMenuItem
}
