<?php

declare(strict_types=1);

namespace BezhanSalleh\PluginEssentials\Concerns\Resource;

use ReflectionClass;
use ReflectionException;

trait DelegatesToPlugin
{
    /**
     * Sentinel value to distinguish between "no plugin result" and "plugin returned null"
     */
    private static $NO_PLUGIN_RESULT = '__NO_PLUGIN_RESULT__';

    protected static function delegateToPlugin(string $traitName, string $methodName, mixed $fallback = null): mixed
    {
        if (! method_exists(static::class, 'getEssentialsPlugin')) {
            return self::$NO_PLUGIN_RESULT;
        }

        try {
            $plugin = static::getEssentialsPlugin();

            if (! is_object($plugin)) {

                return self::$NO_PLUGIN_RESULT;
            }

            if (! static::pluginUsesTrait($plugin, $traitName)) {

                return self::$NO_PLUGIN_RESULT;
            }

            if (! method_exists($plugin, $methodName)) {

                return self::$NO_PLUGIN_RESULT;
            }

            return $plugin->{$methodName}(static::class);

        } catch (\Throwable) {
            return self::$NO_PLUGIN_RESULT;
        }
    }

    protected static function isNoPluginResult(mixed $result): bool
    {
        return $result === self::$NO_PLUGIN_RESULT;
    }

    public static function pluginUsesTrait(object $plugin, string $traitName): bool
    {
        try {
            $reflection = new ReflectionClass($plugin);
            $traits = $reflection->getTraitNames();

            foreach ($traits as $trait) {
                if (str_ends_with($trait, $traitName) || $trait === $traitName) {
                    return true;
                }
            }

            return false;
        } catch (ReflectionException) {
            return false;
        }
    }

    protected static function getParentResult(string $methodName): mixed
    {
        try {
            return parent::{$methodName}();
        } catch (\Throwable) {
            return null;
        }
    }
}
