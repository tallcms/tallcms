<?php

namespace App\Models;

use TallCms\Cms\Models\MediaCollection as BaseMediaCollection;

/**
 * MediaCollection - extends the package's MediaCollection for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\MediaCollection
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class MediaCollection extends BaseMediaCollection
{
    // All functionality inherited from TallCms\Cms\Models\MediaCollection
}
