<?php

namespace App\Services;

use Tiptap\Editor;

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
     * Convert content to HTML
     */
    protected function contentToHtml($content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        // String (HTML or JSON)
        if (is_string($content)) {
            // Try to decode as tiptap JSON
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->tiptapToHtml($decoded);
            }
            // Already HTML
            return $content;
        }

        // Array (tiptap format)
        if (is_array($content)) {
            return $this->tiptapToHtml($content);
        }

        return '';
    }

    /**
     * Convert tiptap document to HTML
     */
    protected function tiptapToHtml(array $content): string
    {
        if (isset($content['type']) && $content['type'] === 'doc') {
            try {
                $editor = new Editor;
                $editor->setContent($content);
                return $editor->getHTML();
            } catch (\Exception) {
                return '';
            }
        }

        return '';
    }
}
