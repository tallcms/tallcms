<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CmsPostFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use TallCms\Cms\Models\CmsPost as BaseCmsPost;

/**
 * App wrapper for CmsPost model.
 *
 * This allows the standalone app to extend or customize the package model
 * while maintaining backwards compatibility with the factory system.
 */
class CmsPost extends BaseCmsPost
{
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CmsPostFactory::new();
    }
}
