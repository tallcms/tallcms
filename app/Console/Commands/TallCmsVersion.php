<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\TallCmsVersion as BaseTallCmsVersion;

/**
 * TallCmsVersion - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\TallCmsVersion
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsVersion extends BaseTallCmsVersion
{
    // All functionality inherited from TallCms\Cms\Console\Commands\TallCmsVersion
}
