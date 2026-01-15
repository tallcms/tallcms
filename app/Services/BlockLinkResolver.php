<?php

namespace App\Services;

use TallCms\Cms\Services\BlockLinkResolver as BaseBlockLinkResolver;

/**
 * BlockLinkResolver - extends the package's BlockLinkResolver for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\BlockLinkResolver
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class BlockLinkResolver extends BaseBlockLinkResolver
{
    // All functionality inherited from TallCms\Cms\Services\BlockLinkResolver
}
