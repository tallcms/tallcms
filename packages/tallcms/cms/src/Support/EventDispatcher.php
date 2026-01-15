<?php

declare(strict_types=1);

namespace TallCms\Cms\Support;

use Illuminate\Support\Facades\Event;

/**
 * Helper for dispatching events that resolves to App wrapper classes when they exist.
 *
 * This ensures that listeners registered for App\Events\* will fire when events
 * are dispatched from the package, maintaining backward compatibility in standalone mode.
 */
class EventDispatcher
{
    /**
     * Map of package event classes to their App wrapper counterparts.
     */
    protected static array $eventMap = [
        \TallCms\Cms\Events\PluginInstalling::class => 'App\\Events\\PluginInstalling',
        \TallCms\Cms\Events\PluginInstalled::class => 'App\\Events\\PluginInstalled',
        \TallCms\Cms\Events\PluginUninstalling::class => 'App\\Events\\PluginUninstalling',
        \TallCms\Cms\Events\PluginUninstalled::class => 'App\\Events\\PluginUninstalled',
        \TallCms\Cms\Events\ThemeActivating::class => 'App\\Events\\ThemeActivating',
        \TallCms\Cms\Events\ThemeActivated::class => 'App\\Events\\ThemeActivated',
        \TallCms\Cms\Events\ThemeInstalling::class => 'App\\Events\\ThemeInstalling',
        \TallCms\Cms\Events\ThemeInstalled::class => 'App\\Events\\ThemeInstalled',
        \TallCms\Cms\Events\ThemeRollback::class => 'App\\Events\\ThemeRollback',
    ];

    /**
     * Dispatch an event, using App wrapper class if it exists.
     *
     * @param  object  $event  The event instance (package class)
     * @return void
     */
    public static function dispatch(object $event): void
    {
        $packageClass = get_class($event);
        $appClass = static::$eventMap[$packageClass] ?? null;

        // If App wrapper exists and is a subclass, re-instantiate as App class
        if ($appClass && class_exists($appClass) && is_subclass_of($appClass, $packageClass)) {
            $event = static::recreateAsAppClass($event, $appClass);
        }

        Event::dispatch($event);
    }

    /**
     * Recreate an event instance as its App wrapper class.
     *
     * Uses reflection to access properties regardless of visibility,
     * supporting public, protected, and private promoted properties.
     */
    protected static function recreateAsAppClass(object $event, string $appClass): object
    {
        $reflection = new \ReflectionClass($event);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new $appClass;
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            // Use reflection to access properties regardless of visibility
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                $property->setAccessible(true);
                $args[] = $property->getValue($event);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
        }

        return new $appClass(...$args);
    }
}
