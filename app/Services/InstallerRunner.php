<?php

namespace App\Services;

use TallCms\Cms\Services\InstallerRunner as BaseInstallerRunner;

/**
 * InstallerRunner - extends the package's InstallerRunner for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\InstallerRunner
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class InstallerRunner extends BaseInstallerRunner
{
    // All functionality inherited from TallCms\Cms\Services\InstallerRunner
}
