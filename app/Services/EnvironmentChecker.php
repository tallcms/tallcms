<?php

namespace App\Services;

use TallCms\Cms\Services\EnvironmentChecker as BaseEnvironmentChecker;

/**
 * EnvironmentChecker - extends the package's EnvironmentChecker for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\EnvironmentChecker
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class EnvironmentChecker extends BaseEnvironmentChecker
{
    // All functionality inherited from TallCms\Cms\Services\EnvironmentChecker
}
