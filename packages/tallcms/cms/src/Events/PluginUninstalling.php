<?php

declare(strict_types=1);

namespace TallCms\Cms\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use TallCms\Cms\Models\Plugin;

class PluginUninstalling
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Plugin $plugin
    ) {}
}
