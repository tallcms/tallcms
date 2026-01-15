<?php

namespace App\Models;

use TallCms\Cms\Models\CmsPage as BaseCmsPage;

/**
 * CmsPage - extends the package's CmsPage for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\CmsPage
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CmsPage extends BaseCmsPage
{
    // All functionality inherited from TallCms\Cms\Models\CmsPage
}
