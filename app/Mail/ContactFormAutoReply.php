<?php

namespace App\Mail;

use TallCms\Cms\Mail\ContactFormAutoReply as BaseContactFormAutoReply;

/**
 * ContactFormAutoReply - extends the package's class for backwards compatibility.
 *
 * This class exists so that existing code using App\Mail\ContactFormAutoReply
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class ContactFormAutoReply extends BaseContactFormAutoReply
{
    // All functionality inherited from TallCms\Cms\Mail\ContactFormAutoReply
}
