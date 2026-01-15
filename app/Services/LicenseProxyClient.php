<?php

namespace App\Services;

use TallCms\Cms\Services\LicenseProxyClient as BaseLicenseProxyClient;

/**
 * LicenseProxyClient - extends the package's LicenseProxyClient for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\LicenseProxyClient
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class LicenseProxyClient extends BaseLicenseProxyClient
{
    // All functionality inherited from TallCms\Cms\Services\LicenseProxyClient
}
