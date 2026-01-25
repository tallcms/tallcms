<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Blocks\Concerns;

/**
 * Provides optional metadata methods for custom blocks.
 *
 * This trait enables blocks to define category, icon, description, and keywords
 * for enhanced block discovery in the Rich Editor panel.
 */
trait HasBlockMetadata
{
    /**
     * Get the category this block belongs to.
     *
     * Available categories: content, media, social-proof, dynamic, forms, other
     */
    public static function getCategory(): string
    {
        return 'content';
    }

    /**
     * Get the Heroicon name for this block.
     *
     * Should be a valid heroicon name like 'heroicon-o-document-text'.
     * Invalid icons will fall back to a default cube icon.
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    /**
     * Get a brief description of what this block does.
     *
     * This is used in search and may be shown as a tooltip.
     */
    public static function getDescription(): string
    {
        return '';
    }

    /**
     * Get additional keywords for search.
     *
     * These help users find the block when searching.
     *
     * @return array<string>
     */
    public static function getKeywords(): array
    {
        return [];
    }

    /**
     * Get the sort priority within the category.
     *
     * Lower numbers appear first. Default is 50.
     */
    public static function getSortPriority(): int
    {
        return 50;
    }
}
