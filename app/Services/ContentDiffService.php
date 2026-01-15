<?php

namespace App\Services;

use TallCms\Cms\Services\ContentDiffService as BaseContentDiffService;

/**
 * ContentDiffService - extends the package's ContentDiffService for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\ContentDiffService
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ContentDiffService extends BaseContentDiffService
{
    // All functionality inherited from TallCms\Cms\Services\ContentDiffService
}
