<?php

namespace App\Services;

use TallCms\Cms\Services\HtmlSanitizerService as BaseHtmlSanitizerService;

/**
 * HtmlSanitizerService - extends the package's HtmlSanitizerService for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\HtmlSanitizerService
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class HtmlSanitizerService extends BaseHtmlSanitizerService
{
    // All functionality inherited from TallCms\Cms\Services\HtmlSanitizerService
}
