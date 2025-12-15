<?php

if (!function_exists('theme')) {
    /**
     * Get the current active theme instance
     */
    function theme(): \App\Contracts\ThemeInterface
    {
        return \App\Services\ThemeResolver::getCurrentTheme();
    }
}

if (!function_exists('theme_colors')) {
    /**
     * Get the current theme's color palette
     */
    function theme_colors(): array
    {
        return theme()->getColorPalette();
    }
}

if (!function_exists('theme_button_presets')) {
    /**
     * Get the current theme's button presets
     */
    function theme_button_presets(): array
    {
        return theme()->getButtonPresets();
    }
}

if (!function_exists('theme_text_presets')) {
    /**
     * Get the current theme's text presets
     */
    function theme_text_presets(): array
    {
        return theme()->getTextPresets();
    }
}

if (!function_exists('theme_padding_presets')) {
    /**
     * Get the current theme's padding presets
     */
    function theme_padding_presets(): array
    {
        return theme()->getPaddingPresets();
    }
}