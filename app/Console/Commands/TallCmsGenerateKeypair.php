<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\TallCmsGenerateKeypair as BaseTallCmsGenerateKeypair;

/**
 * TallCmsGenerateKeypair - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\TallCmsGenerateKeypair
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallCmsGenerateKeypair extends BaseTallCmsGenerateKeypair
{
    // All functionality inherited from TallCms\Cms\Console\Commands\TallCmsGenerateKeypair
}
