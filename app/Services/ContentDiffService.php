<?php

namespace App\Services;

use App\Services\CustomBlockDiscoveryService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

class ContentDiffService
{
    /**
     * Compare two content values and return old/new HTML
     */
    public function diff($oldContent, $newContent): array
    {
        $oldHtml = $this->contentToHtml($oldContent);
        $newHtml = $this->contentToHtml($newContent);

        return [
            'has_changes' => $oldHtml !== $newHtml,
            'old_html' => $oldHtml,
            'new_html' => $newHtml,
        ];
    }

    /**
     * Convert content to HTML using the same renderer as the CMS
     */
    protected function contentToHtml($content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        // Convert to JSON string if array
        if (is_array($content)) {
            $content = json_encode($content);
        }

        if (! is_string($content)) {
            return '';
        }

        // Check if it's valid JSON (tiptap format)
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Already HTML string
            return $content;
        }

        // Use the same renderer as the CMS frontend with decoded array
        try {
            return RichContentRenderer::make($decoded)
                ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                ->toHtml();
        } catch (\Exception) {
            // Fallback to raw content on error
            return $content;
        }
    }
}
