<?php

declare(strict_types=1);

namespace TallCms\Cms\Events;

use TallCms\Cms\Models\Theme;

class ThemeInstalled
{
    public Theme $theme;

    public bool $success;

    public function __construct(Theme $theme, bool $success = true)
    {
        $this->theme = $theme;
        $this->success = $success;
    }
}
