<?php

namespace App\Services;

use TallCms\Cms\Services\PluginLicenseService as BasePluginLicenseService;

/**
 * PluginLicenseService - extends the package's PluginLicenseService for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\PluginLicenseService
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PluginLicenseService extends BasePluginLicenseService
{
    // All functionality inherited from TallCms\Cms\Services\PluginLicenseService
}
