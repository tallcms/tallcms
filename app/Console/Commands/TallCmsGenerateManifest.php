<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\TallCmsGenerateManifest as BaseTallCmsGenerateManifest;

/**
 * TallCmsGenerateManifest - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\TallCmsGenerateManifest
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsGenerateManifest extends BaseTallCmsGenerateManifest
{
    // All functionality inherited from TallCms\Cms\Console\Commands\TallCmsGenerateManifest
}
