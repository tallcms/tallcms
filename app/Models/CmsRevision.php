<?php

namespace App\Models;

use TallCms\Cms\Models\CmsRevision as BaseCmsRevision;

/**
 * CmsRevision - extends the package's CmsRevision for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\CmsRevision
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CmsRevision extends BaseCmsRevision
{
    // All functionality inherited from TallCms\Cms\Models\CmsRevision
}
