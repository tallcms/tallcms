<?php

declare(strict_types=1);

namespace TallCms\Cms\Events;

use TallCms\Cms\Models\Theme;

class ThemeActivated
{
    public Theme $theme;

    public ?Theme $previousTheme;

    public function __construct(Theme $theme, ?Theme $previousTheme = null)
    {
        $this->theme = $theme;
        $this->previousTheme = $previousTheme;
    }
}
