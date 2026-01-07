<?php

namespace App\Events;

use App\Models\Plugin;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PluginInstalling
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Plugin $plugin,
        public string $source = 'upload'
    ) {}
}
