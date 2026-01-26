<?php

declare(strict_types=1);

namespace TallCms\Cms\View\Components;

use Illuminate\View\Component;
use TallCms\Cms\Models\TallcmsMedia;

class ResponsiveImage extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public TallcmsMedia $media,
        public string $size = 'medium',
        public string $class = '',
        public bool $lazy = true,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('tallcms::components.responsive-image');
    }
}
