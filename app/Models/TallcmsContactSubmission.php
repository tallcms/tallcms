<?php

namespace App\Models;

use TallCms\Cms\Models\TallcmsContactSubmission as BaseTallcmsContactSubmission;

/**
 * TallcmsContactSubmission - extends the package's TallcmsContactSubmission for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\TallcmsContactSubmission
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallcmsContactSubmission extends BaseTallcmsContactSubmission
{
    // All functionality inherited from TallCms\Cms\Models\TallcmsContactSubmission
}
