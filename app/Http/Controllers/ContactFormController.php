<?php

namespace App\Http\Controllers;

use TallCms\Cms\Http\Controllers\ContactFormController as BaseContactFormController;

/**
 * ContactFormController - extends the package's ContactFormController for backwards compatibility.
 *
 * This class exists so that existing code using App\Http\Controllers\ContactFormController
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ContactFormController extends BaseContactFormController
{
    // All functionality inherited from TallCms\Cms\Http\Controllers\ContactFormController
}
