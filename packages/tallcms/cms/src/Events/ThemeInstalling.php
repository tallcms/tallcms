<?php

declare(strict_types=1);

namespace TallCms\Cms\Events;

use TallCms\Cms\Models\Theme;

class ThemeInstalling
{
    public Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }
}
