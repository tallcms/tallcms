<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\TallCmsUpdate as BaseTallCmsUpdate;

/**
 * TallCmsUpdate - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\TallCmsUpdate
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsUpdate extends BaseTallCmsUpdate
{
    // All functionality inherited from TallCms\Cms\Console\Commands\TallCmsUpdate
}
