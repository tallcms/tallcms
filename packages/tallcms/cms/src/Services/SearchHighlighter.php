<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Str;

class SearchHighlighter
{
    protected const MAX_QUERY_WORDS = 10;

    protected const MAX_WORD_LENGTH = 50;

    public function highlight(string $text, string $query, int $contextLength = 150): string
    {
        if (empty($text)) {
            return '';
        }

        // Strip HTML tags and decode entities to prevent XSS
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $words = $this->sanitizeQuery($query);
        if (empty($words)) {
            return e(Str::limit($text, $contextLength));
        }

        $escapedWords = array_map(fn ($w) => preg_quote($w, '/'), $words);
        $pattern = '/('.implode('|', $escapedWords).')/iu';

        if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1];
            $start = max(0, $pos - (int) ($contextLength / 2));
            $excerpt = Str::substr($text, $start, $contextLength);

            if ($start > 0) {
                $excerpt = '...'.ltrim($excerpt);
            }
            if (Str::length($text) > $start + $contextLength) {
                $excerpt = rtrim($excerpt).'...';
            }

            // Escape the excerpt first, then add highlight marks
            $excerpt = e($excerpt);

            // Escape the pattern for use after HTML escaping
            $escapedWordsForHtml = array_map(fn ($w) => preg_quote(e($w), '/'), $words);
            $patternForHtml = '/('.implode('|', $escapedWordsForHtml).')/iu';

            return preg_replace(
                $patternForHtml,
                '<mark class="bg-warning text-warning-content px-0.5 rounded">$1</mark>',
                $excerpt
            );
        }

        return e(Str::limit($text, $contextLength)).'...';
    }

    protected function sanitizeQuery(string $query): array
    {
        $words = preg_split('/\s+/', trim($query));
        $words = array_filter($words, fn ($w) => strlen($w) >= 2 && strlen($w) <= self::MAX_WORD_LENGTH);

        return array_slice($words, 0, self::MAX_QUERY_WORDS);
    }
}
