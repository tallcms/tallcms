<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CmsPageFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use TallCms\Cms\Models\CmsPage as BaseCmsPage;

/**
 * App wrapper for CmsPage model.
 *
 * This allows the standalone app to extend or customize the package model
 * while maintaining backwards compatibility with the factory system.
 */
class CmsPage extends BaseCmsPage
{
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CmsPageFactory::new();
    }
}
