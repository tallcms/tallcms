<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\TallCmsSignRelease as BaseTallCmsSignRelease;

/**
 * TallCmsSignRelease - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\TallCmsSignRelease
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsSignRelease extends BaseTallCmsSignRelease
{
    // All functionality inherited from TallCms\Cms\Console\Commands\TallCmsSignRelease
}
