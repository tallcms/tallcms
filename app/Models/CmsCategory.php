<?php

namespace App\Models;

use TallCms\Cms\Models\CmsCategory as BaseCmsCategory;

/**
 * CmsCategory - extends the package's CmsCategory for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\CmsCategory
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CmsCategory extends BaseCmsCategory
{
    // All functionality inherited from TallCms\Cms\Models\CmsCategory
}
