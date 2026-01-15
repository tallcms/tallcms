<?php

namespace App\Services;

use TallCms\Cms\Services\MergeTagService as BaseMergeTagService;

/**
 * MergeTagService - extends the package's MergeTagService for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\MergeTagService
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class MergeTagService extends BaseMergeTagService
{
    // All functionality inherited from TallCms\Cms\Services\MergeTagService
}
