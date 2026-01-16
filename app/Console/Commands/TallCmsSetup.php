<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\TallCmsSetup as BaseTallCmsSetup;

/**
 * TallCmsSetup - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\TallCmsSetup
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsSetup extends BaseTallCmsSetup
{
    // All functionality inherited from TallCms\Cms\Console\Commands\TallCmsSetup
}
