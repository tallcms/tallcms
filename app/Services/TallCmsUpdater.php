<?php

namespace App\Services;

use TallCms\Cms\Services\TallCmsUpdater as BaseTallCmsUpdater;

/**
 * TallCmsUpdater - extends the package's TallCmsUpdater for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\TallCmsUpdater
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsUpdater extends BaseTallCmsUpdater
{
    // All functionality inherited from TallCms\Cms\Services\TallCmsUpdater
}
