<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms\Components\Plugins;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Support\Facades\FilamentAsset;

/**
 * Adds per-block chrome (drag handle, move up/down, duplicate) to custom
 * blocks rendered inside CmsRichEditor.
 *
 * Wires a TipTap extension that:
 *  - registers `moveCustomBlockUp`, `moveCustomBlockDown`, and
 *    `duplicateCustomBlock` commands operating on top-level customBlock nodes
 *  - injects the chrome into each block's existing header via a ProseMirror
 *    plugin view, so DOM mutations stay anchored to the editor lifecycle
 */
class BlockChromePlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getEditorTools(): array
    {
        return [];
    }

    public function getEditorActions(): array
    {
        return [];
    }

    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('block-chrome', 'tallcms/cms'),
        ];
    }
}
