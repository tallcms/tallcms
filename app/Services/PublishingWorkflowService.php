<?php

namespace App\Services;

use TallCms\Cms\Services\PublishingWorkflowService as BasePublishingWorkflowService;

/**
 * PublishingWorkflowService - extends the package's PublishingWorkflowService for backwards compatibility.
 *
 * This class exists so that existing code using App\Services\PublishingWorkflowService
 * continues to work. All functionality is provided by the tallcms/cms package.
 */
class PublishingWorkflowService extends BasePublishingWorkflowService
{
    // All functionality inherited from TallCms\Cms\Services\PublishingWorkflowService
}
