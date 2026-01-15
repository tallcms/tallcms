<?php

namespace App\Livewire;

use TallCms\Cms\Livewire\CmsPageRenderer as BaseCmsPageRenderer;

/**
 * CmsPageRenderer - extends the package's CmsPageRenderer for backwards compatibility.
 *
 * This class exists so that existing code using App\Livewire\CmsPageRenderer
 * continues to work. All functionality is provided by the tallcms/cms package.
 *
 * Note: In standalone mode, views fallback to app views (livewire.page, layouts.app)
 * when package views are not found.
 */
class CmsPageRenderer extends BaseCmsPageRenderer
{
    /**
     * Override render to use app views in standalone mode for full customization support.
     */
    public function render()
    {
        // Determine metadata based on whether we're showing a post or page
        if ($this->post) {
            $title = $this->post->meta_title ?: $this->post->title;
            $description = $this->post->meta_description ?: $this->post->excerpt;
            $featuredImage = $this->post->featured_image;
        } else {
            $title = $this->page->meta_title ?: $this->page->title;
            $description = $this->page->meta_description;
            $featuredImage = $this->page->featured_image;
        }

        // Use app views directly in standalone mode for full customization
        return view('livewire.page')
            ->layout('layouts.app', [
                'title' => $title,
                'description' => $description,
                'featuredImage' => $featuredImage,
            ]);
    }
}
