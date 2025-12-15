<?php

namespace App\Services;

use App\Contracts\ThemeInterface;
use App\Support\ThemeColors;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ThemeResolver
{
    /**
     * Get the currently active theme instance
     */
    public static function getCurrentTheme(): ThemeInterface
    {
        // Try to resolve from container first
        if (App::bound(ThemeInterface::class)) {
            return App::make(ThemeInterface::class);
        }
        
        // Fallback to default theme if no theme is bound
        return new ThemeColors();
    }
    
    /**
     * Get the active theme name from configuration
     */
    public static function getActiveThemeName(): string
    {
        return Config::get('theme.active', 'default');
    }
    
    /**
     * Check if a custom theme is active (not the default)
     */
    public static function isCustomThemeActive(): bool
    {
        return static::getActiveThemeName() !== 'default';
    }
    
    /**
     * Get available themes from configuration
     */
    public static function getAvailableThemes(): array
    {
        return Config::get('theme.available', [
            'default' => ThemeColors::class,
        ]);
    }
    
    /**
     * Bind a specific theme to the container
     */
    public static function bindTheme(string $themeName): void
    {
        $themes = static::getAvailableThemes();
        
        if (!isset($themes[$themeName])) {
            throw new \InvalidArgumentException("Theme '{$themeName}' not found in available themes.");
        }
        
        $themeClass = $themes[$themeName];
        
        if (!class_exists($themeClass)) {
            throw new \InvalidArgumentException("Theme class '{$themeClass}' does not exist.");
        }
        
        if (!is_subclass_of($themeClass, ThemeInterface::class)) {
            throw new \InvalidArgumentException("Theme class '{$themeClass}' must implement ThemeInterface.");
        }
        
        App::bind(ThemeInterface::class, $themeClass);
    }
}