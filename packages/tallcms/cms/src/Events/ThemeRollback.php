<?php

declare(strict_types=1);

namespace TallCms\Cms\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TallCms\Cms\Models\Theme;

class ThemeRollback
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Theme $theme
    ) {}
}
