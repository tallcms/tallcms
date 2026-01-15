<?php

declare(strict_types=1);

namespace TallCms\Cms\Contracts;

interface ThemeInterface
{
    /**
     * Get the theme's unique identifier
     */
    public function getId(): string;

    /**
     * Get the theme's display name
     */
    public function getName(): string;

    /**
     * Get the theme's color palette for components
     *
     * @return array{
     *     primary: array<int, string>,
     *     secondary: array<int, string>,
     *     success: array<int, string>,
     *     warning: array<int, string>,
     *     danger: array<int, string>,
     *     neutral: array<int, string>
     * }
     */
    public function getColorPalette(): array;

    /**
     * Get preset button colors for rich editor components
     *
     * All presets must include bg, text, hover, and border properties.
     * Must include basic semantic colors and hero-specific variants.
     *
     * @return array{
     *     white: array{bg: string, text: string, hover: string, border: string},
     *     primary: array{bg: string, text: string, hover: string, border: string},
     *     secondary: array{bg: string, text: string, hover: string, border: string},
     *     success: array{bg: string, text: string, hover: string, border: string},
     *     warning: array{bg: string, text: string, hover: string, border: string},
     *     danger: array{bg: string, text: string, hover: string, border: string},
     *     neutral: array{bg: string, text: string, hover: string, border: string},
     *     dark: array{bg: string, text: string, hover: string, border: string},
     *     outline-white: array{bg: string, text: string, hover: string, border: string},
     *     outline-primary: array{bg: string, text: string, hover: string, border: string},
     *     outline-success: array{bg: string, text: string, hover: string, border: string},
     *     outline-warning: array{bg: string, text: string, hover: string, border: string},
     *     outline-danger: array{bg: string, text: string, hover: string, border: string},
     *     filled-white: array{bg: string, text: string, hover: string, border: string},
     *     filled-primary: array{bg: string, text: string, hover: string, border: string}
     * }
     */
    public function getButtonPresets(): array;

    /**
     * Get text color presets for different contrast levels
     *
     * @return array{
     *     primary: array{heading: string, description: string},
     *     secondary: array{heading: string, description: string},
     *     muted: array{heading: string, description: string},
     *     inverse: array{heading: string, description: string}
     * }
     */
    public function getTextPresets(): array;

    /**
     * Get the theme's CSS custom properties as a string
     */
    public function getCssCustomProperties(): string;

    /**
     * Get the theme's CSS file path (relative to public)
     */
    public function getCssFilePath(): string;

    /**
     * Get padding/spacing presets for components
     *
     * @return array{
     *     small: array{padding: string, classes: string},
     *     medium: array{padding: string, classes: string},
     *     large: array{padding: string, classes: string},
     *     xl: array{padding: string, classes: string}
     * }
     */
    public function getPaddingPresets(): array;

    /**
     * Check if this theme supports dark mode
     */
    public function supportsDarkMode(): bool;

    /**
     * Get theme configuration options for users
     */
    public function getConfigurationSchema(): array;
}
