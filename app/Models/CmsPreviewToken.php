<?php

namespace App\Models;

use TallCms\Cms\Models\CmsPreviewToken as BaseCmsPreviewToken;

/**
 * CmsPreviewToken - extends the package's CmsPreviewToken for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\CmsPreviewToken
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CmsPreviewToken extends BaseCmsPreviewToken
{
    // All functionality inherited from TallCms\Cms\Models\CmsPreviewToken
}
