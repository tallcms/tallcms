<?php

namespace App\Services;

use TallCms\Cms\Services\CustomBlockDiscoveryService as BaseCustomBlockDiscoveryService;

/**
 * CustomBlockDiscoveryService - extends the package's CustomBlockDiscoveryService for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\CustomBlockDiscoveryService
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class CustomBlockDiscoveryService extends BaseCustomBlockDiscoveryService
{
    // All functionality inherited from TallCms\Cms\Services\CustomBlockDiscoveryService
}
