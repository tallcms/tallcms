<?php

namespace App\Models;

use TallCms\Cms\Models\TallcmsMenu as BaseTallcmsMenu;

/**
 * TallcmsMenu - extends the package's TallcmsMenu for backwards compatibility.
 *
 * This class exists so that existing code using App\Models\TallcmsMenu
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class TallcmsMenu extends BaseTallcmsMenu
{
    // All functionality inherited from TallCms\Cms\Models\TallcmsMenu
}
