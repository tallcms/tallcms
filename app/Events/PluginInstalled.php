<?php

namespace App\Events;

use App\Models\Plugin;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PluginInstalled
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Plugin $plugin,
        public array $migrationsRan = [],
        public string $source = 'upload'
    ) {}
}
