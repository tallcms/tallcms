<?php

namespace App\Http\Controllers;

use TallCms\Cms\Http\Controllers\PreviewController as BasePreviewController;

/**
 * PreviewController - extends the package's PreviewController for backwards compatibility.
 *
 * This class exists so that existing code using App\Http\Controllers\PreviewController
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PreviewController extends BasePreviewController
{
    // All functionality inherited from TallCms\Cms\Http\Controllers\PreviewController
}
