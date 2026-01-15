<?php

namespace App\Services;

use TallCms\Cms\Services\FileBasedTheme as BaseFileBasedTheme;

/**
 * FileBasedTheme - extends the package's FileBasedTheme for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\FileBasedTheme
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class FileBasedTheme extends BaseFileBasedTheme
{
    // All functionality inherited from TallCms\Cms\Services\FileBasedTheme
}
