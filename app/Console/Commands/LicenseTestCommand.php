<?php

namespace App\Console\Commands;

use TallCms\Cms\Console\Commands\LicenseTestCommand as BaseLicenseTestCommand;

/**
 * LicenseTestCommand - extends the package's command for backwards compatibility.
 *
 * This class exists so that existing code referencing App\Console\Commands\LicenseTestCommand
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class LicenseTestCommand extends BaseLicenseTestCommand
{
    // All functionality inherited from TallCms\Cms\Console\Commands\LicenseTestCommand
}
