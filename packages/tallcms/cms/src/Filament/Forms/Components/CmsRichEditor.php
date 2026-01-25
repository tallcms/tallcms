<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms\Components;

use Filament\Forms\Components\RichEditor;
use TallCms\Cms\Services\BlockCategoryRegistry;
use TallCms\Cms\Services\CustomBlockDiscoveryService;

/**
 * Custom RichEditor component for CMS pages and posts.
 *
 * Extends Filament's RichEditor with an enhanced block panel featuring:
 * - Search functionality for finding blocks quickly
 * - Blocks grouped by category (Content, Media, Social Proof, etc.)
 * - Icons displayed alongside block names
 *
 * This component is scoped to CMS editors only - other RichEditors in the
 * admin are unaffected.
 *
 * Falls back to standard RichEditor when Filament version is incompatible.
 */
class CmsRichEditor extends RichEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only use enhanced view if Filament v4.x is installed
        // Otherwise, use parent's view for full compatibility
        if (static::isFilamentCompatible()) {
            $this->view = 'tallcms::filament.forms.components.cms-rich-editor';
        }
    }

    /**
     * Get blocks grouped by category for the enhanced block panel.
     *
     * @return array<string, array<int, array{id: string, label: string, icon: string, iconHtml: string, searchable: string}>>
     */
    public function getGroupedBlocks(): array
    {
        return CustomBlockDiscoveryService::getBlocksGroupedByCategory()->toArray();
    }

    /**
     * Get the block category definitions.
     *
     * @return array<string, array{label: string, icon: string, order: int}>
     */
    public function getBlockCategories(): array
    {
        return BlockCategoryRegistry::getCategories();
    }

    /**
     * Check if the current Filament version is compatible with the enhanced panel.
     *
     * We require Filament v4.x for the enhanced block panel features.
     * If incompatible, the view will fall back to the standard panel.
     */
    public static function isFilamentCompatible(): bool
    {
        if (! class_exists(\Composer\InstalledVersions::class)) {
            return false;
        }

        try {
            $version = \Composer\InstalledVersions::getVersion('filament/forms');

            return $version
                && version_compare($version, '4.0.0', '>=')
                && version_compare($version, '5.0.0', '<');
        } catch (\Throwable) {
            return false;
        }
    }
}
