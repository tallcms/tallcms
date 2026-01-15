<?php

namespace App\Services;

use TallCms\Cms\Services\EnvWriter as BaseEnvWriter;

/**
 * EnvWriter - extends the package's EnvWriter for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\EnvWriter
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class EnvWriter extends BaseEnvWriter
{
    // All functionality inherited from TallCms\Cms\Services\EnvWriter
}
