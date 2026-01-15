<?php

namespace App\Models;

use TallCms\Cms\Models\CmsPost as BaseCmsPost;

/**
 * CmsPost - extends the package's CmsPost for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\CmsPost
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CmsPost extends BaseCmsPost
{
    // All functionality inherited from TallCms\Cms\Models\CmsPost
}
