<?php

namespace TallCms\Cms\Filament\Blocks\Concerns;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Provides reusable daisyUI-compatible form schema components for blocks.
 *
 * All blocks should use these standardized options to ensure consistent
 * styling that works with daisyUI theme switching.
 */
trait HasDaisyUIOptions
{
    /**
     * Get button variant options (maps to daisyUI btn-* classes)
     */
    protected static function getButtonVariantOptions(): array
    {
        return [
            'btn-primary' => 'Primary',
            'btn-secondary' => 'Secondary',
            'btn-accent' => 'Accent',
            'btn-neutral' => 'Neutral',
            'btn-info' => 'Info',
            'btn-success' => 'Success',
            'btn-warning' => 'Warning',
            'btn-error' => 'Error',
        ];
    }

    /**
     * Get secondary/outline button variant options
     */
    protected static function getSecondaryButtonVariantOptions(): array
    {
        return [
            'btn-outline btn-primary' => 'Primary Outline',
            'btn-outline btn-secondary' => 'Secondary Outline',
            'btn-outline btn-accent' => 'Accent Outline',
            'btn-outline btn-neutral' => 'Neutral Outline',
            'btn-ghost' => 'Ghost',
            'btn-link' => 'Link',
        ];
    }

    /**
     * Get button size options
     */
    protected static function getButtonSizeOptions(): array
    {
        return [
            'btn-xs' => 'Extra Small',
            'btn-sm' => 'Small',
            'btn-md' => 'Medium',
            'btn-lg' => 'Large',
        ];
    }

    /**
     * Get background color options (semantic daisyUI colors)
     */
    protected static function getBackgroundOptions(): array
    {
        return [
            'bg-base-100' => 'Base (Default)',
            'bg-base-200' => 'Base Subtle',
            'bg-base-300' => 'Base Strong',
            'bg-primary' => 'Primary',
            'bg-secondary' => 'Secondary',
            'bg-accent' => 'Accent',
            'bg-neutral' => 'Neutral',
            'bg-info' => 'Info',
            'bg-success' => 'Success',
            'bg-warning' => 'Warning',
            'bg-error' => 'Error',
        ];
    }

    /**
     * Get card style options
     */
    protected static function getCardStyleOptions(): array
    {
        return [
            'card bg-base-100' => 'Default Card',
            'card bg-base-100 shadow-md' => 'Card with Shadow',
            'card bg-base-100 shadow-xl' => 'Card with Large Shadow',
            'card bg-base-200' => 'Subtle Card',
            'card bordered' => 'Bordered Card',
            'card bg-primary text-primary-content' => 'Primary Card',
            'card bg-secondary text-secondary-content' => 'Secondary Card',
        ];
    }

    /**
     * Get text alignment options
     */
    protected static function getTextAlignmentOptions(): array
    {
        return [
            'text-left' => 'Left',
            'text-center' => 'Center',
            'text-right' => 'Right',
        ];
    }

    /**
     * Get text color options (semantic daisyUI colors)
     */
    protected static function getTextColorOptions(): array
    {
        return [
            'text-base-content' => 'Base (Default)',
            'text-primary' => 'Primary',
            'text-secondary' => 'Secondary',
            'text-accent' => 'Accent',
            'text-neutral' => 'Neutral',
            'text-info' => 'Info',
            'text-success' => 'Success',
            'text-warning' => 'Warning',
            'text-error' => 'Error',
            'text-white' => 'White',
            'text-white/90' => 'White (90%)',
            'text-white/80' => 'White (80%)',
            'text-white/70' => 'White (70%)',
            'text-primary-content' => 'Primary Content',
            'text-secondary-content' => 'Secondary Content',
            'text-accent-content' => 'Accent Content',
            'text-neutral-content' => 'Neutral Content',
        ];
    }

    /**
     * Get padding/spacing options
     */
    protected static function getPaddingOptions(): array
    {
        return [
            'py-8' => 'Small',
            'py-12' => 'Medium',
            'py-16' => 'Large',
            'py-24' => 'Extra Large',
        ];
    }

    /**
     * Get a reusable button styling section for block schemas
     */
    protected static function getButtonStyleSection(
        string $prefix = '',
        string $label = 'Primary Button',
        string $defaultVariant = 'btn-primary',
        string $defaultSize = 'btn-md',
        bool $showSize = true
    ): Section {
        $variantField = $prefix ? "{$prefix}_variant" : 'button_variant';
        $sizeField = $prefix ? "{$prefix}_size" : 'button_size';

        $schema = [
            Select::make($variantField)
                ->label('Button Style')
                ->options(static::getButtonVariantOptions())
                ->default($defaultVariant),
        ];

        if ($showSize) {
            $schema[] = Select::make($sizeField)
                ->label('Button Size')
                ->options(static::getButtonSizeOptions())
                ->default($defaultSize);
        }

        return Section::make($label)
            ->schema($schema)
            ->columns(2)
            ->compact();
    }

    /**
     * Get a reusable secondary button styling section
     */
    protected static function getSecondaryButtonStyleSection(
        string $prefix = 'secondary_button',
        string $label = 'Secondary Button',
        string $defaultVariant = 'btn-outline btn-primary'
    ): Section {
        return Section::make($label)
            ->schema([
                Select::make("{$prefix}_variant")
                    ->label('Button Style')
                    ->options(static::getSecondaryButtonVariantOptions())
                    ->default($defaultVariant),
            ])
            ->compact();
    }

    /**
     * Get a reusable background styling section
     */
    protected static function getBackgroundSection(
        string $defaultBg = 'bg-base-200',
        bool $showGradient = false
    ): Section {
        $schema = [
            Select::make('background')
                ->label('Background Color')
                ->options(static::getBackgroundOptions())
                ->default($defaultBg),
        ];

        if ($showGradient) {
            $schema[] = Toggle::make('use_gradient')
                ->label('Use Gradient')
                ->default(false);
        }

        return Section::make('Background')
            ->schema($schema)
            ->compact();
    }

    /**
     * Get a reusable layout section
     */
    protected static function getLayoutSection(
        string $defaultAlignment = 'text-center',
        string $defaultPadding = 'py-12'
    ): Section {
        return Section::make('Layout')
            ->schema([
                Select::make('text_alignment')
                    ->label('Text Alignment')
                    ->options(static::getTextAlignmentOptions())
                    ->default($defaultAlignment),

                Select::make('padding')
                    ->label('Section Padding')
                    ->options(static::getPaddingOptions())
                    ->default($defaultPadding),
            ])
            ->columns(2)
            ->compact();
    }

    /**
     * Build button CSS classes from config
     */
    public static function buildButtonClasses(array $config, string $prefix = ''): string
    {
        $variantKey = $prefix ? "{$prefix}_variant" : 'button_variant';
        $sizeKey = $prefix ? "{$prefix}_size" : 'button_size';

        $variant = $config[$variantKey] ?? 'btn-primary';
        $size = $config[$sizeKey] ?? '';

        return trim("btn {$variant} {$size}");
    }

    /**
     * Build background CSS classes from config
     */
    public static function buildBackgroundClasses(array $config): string
    {
        return $config['background'] ?? 'bg-base-200';
    }

    /**
     * Build layout CSS classes from config
     */
    public static function buildLayoutClasses(array $config): string
    {
        $alignment = $config['text_alignment'] ?? 'text-center';
        $padding = $config['padding'] ?? 'py-12';

        return "{$alignment} {$padding}";
    }
}
