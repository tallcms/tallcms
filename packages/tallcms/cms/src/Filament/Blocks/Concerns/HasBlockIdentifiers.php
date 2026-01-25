<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Blocks\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

/**
 * Provides anchor ID and custom CSS class fields for blocks.
 *
 * This trait enables blocks to have:
 * - An anchor ID for deep linking and table of contents navigation
 * - Custom CSS classes for additional styling
 */
trait HasBlockIdentifiers
{
    /**
     * Get the Advanced Settings section with anchor ID and CSS classes.
     *
     * Add this section to the block's schema to enable identifiers.
     */
    public static function getIdentifiersSection(): Section
    {
        return Section::make('Advanced Settings')
            ->schema([
                TextInput::make('anchor_id')
                    ->label('Anchor ID')
                    ->placeholder('e.g., my-section')
                    ->helperText('Used for anchor links (e.g., #my-section). Auto-generated from title if empty.')
                    ->maxLength(100)
                    ->regex('/^[a-z0-9-]*$/')
                    ->validationMessages([
                        'regex' => 'Anchor ID must contain only lowercase letters, numbers, and hyphens.',
                    ]),

                TextInput::make('css_classes')
                    ->label('CSS Classes')
                    ->placeholder('e.g., my-custom-class another-class')
                    ->helperText('Additional CSS classes to add to the block wrapper.')
                    ->maxLength(255),
            ])
            ->columns(2)
            ->collapsed()
            ->collapsible();
    }

    /**
     * Get the anchor ID from config, with optional auto-generation from title.
     */
    public static function getAnchorId(array $config, ?string $fallbackTitle = null): ?string
    {
        // Use explicitly set anchor ID first
        if (! empty($config['anchor_id'])) {
            return $config['anchor_id'];
        }

        // Auto-generate from title if available
        if ($fallbackTitle) {
            return Str::slug($fallbackTitle);
        }

        return null;
    }

    /**
     * Get additional CSS classes from config.
     */
    public static function getCssClasses(array $config): string
    {
        return $config['css_classes'] ?? '';
    }

    /**
     * Build the HTML attributes string for the block wrapper.
     */
    public static function buildBlockAttributes(array $config, ?string $fallbackTitle = null, string $baseClasses = ''): string
    {
        $attributes = [];

        // Add ID if available
        $anchorId = static::getAnchorId($config, $fallbackTitle);
        if ($anchorId) {
            $attributes[] = 'id="' . e($anchorId) . '"';
        }

        // Combine base classes with custom classes
        $customClasses = static::getCssClasses($config);
        $allClasses = trim($baseClasses . ' ' . $customClasses);
        if ($allClasses) {
            $attributes[] = 'class="' . e($allClasses) . '"';
        }

        return implode(' ', $attributes);
    }
}
