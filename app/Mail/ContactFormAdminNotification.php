<?php

namespace App\Mail;

use TallCms\Cms\Mail\ContactFormAdminNotification as BaseContactFormAdminNotification;

/**
 * ContactFormAdminNotification - extends the package's class for backwards compatibility.
 *
 * This class exists so that existing code using App\Mail\ContactFormAdminNotification
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ContactFormAdminNotification extends BaseContactFormAdminNotification
{
    // All functionality inherited from TallCms\Cms\Mail\ContactFormAdminNotification
}
