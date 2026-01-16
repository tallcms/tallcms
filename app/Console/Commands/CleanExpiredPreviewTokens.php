<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\CleanExpiredPreviewTokens as BaseCleanExpiredPreviewTokens;

/**
 * CleanExpiredPreviewTokens - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\CleanExpiredPreviewTokens
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CleanExpiredPreviewTokens extends BaseCleanExpiredPreviewTokens
{
    // All functionality inherited from TallCms\Cms\Console\Commands\CleanExpiredPreviewTokens
}
