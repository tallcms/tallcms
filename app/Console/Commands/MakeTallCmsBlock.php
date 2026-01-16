<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\MakeTallCmsBlock as BaseMakeTallCmsBlock;

/**
 * MakeTallCmsBlock - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\MakeTallCmsBlock
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class MakeTallCmsBlock extends BaseMakeTallCmsBlock
{
    // All functionality inherited from TallCms\Cms\Console\Commands\MakeTallCmsBlock
}
