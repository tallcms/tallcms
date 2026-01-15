<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

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
            $decoded = $content;
        } elseif (is_string($content)) {
            // Try to decode JSON
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Already HTML string, return as-is
                return $content;
            }
        } else {
            return '';
        }

        // Verify it's a valid tiptap document shape (must have type === 'doc')
        if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'doc') {
            // Not a tiptap document, return as raw HTML/text
            return is_string($content) ? $content : json_encode($content);
        }

        // Use the same renderer as the CMS frontend
        try {
            $html = RichContentRenderer::make($decoded)
                ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                ->toHtml();

            // If renderer returns empty but content exists, fall back to readable format
            if (empty($html) && ! empty($decoded['content'])) {
                // Return original string content if available, otherwise pretty-print JSON
                return is_string($content) ? $content : json_encode($decoded, JSON_PRETTY_PRINT);
            }

            return $html;
        } catch (\Exception) {
            // Fallback to readable format on error
            return is_string($content) ? $content : json_encode($decoded, JSON_PRETTY_PRINT);
        }
    }
}
