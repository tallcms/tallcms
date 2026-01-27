<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownToHtml
{
    protected MarkdownConverter $converter;

    protected array $linkMap = [];

    public function __construct(array $linkMap = [])
    {
        $this->linkMap = $linkMap;

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => '',
                'apply_id_to_heading' => true,
                'heading_class' => '',
                'fragment_prefix' => '',
                'insert' => 'after',
                'min_heading_level' => 2,
                'max_heading_level' => 4,
                'title' => 'Permalink',
                'symbol' => '#',
                'aria_hidden' => true,
            ],
            'table_of_contents' => [
                'html_class' => 'table-of-contents',
                'position' => 'top',
                'style' => 'bullet',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'normalize' => 'relative',
                'placeholder' => null,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new TableOfContentsExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Convert markdown to HTML with heading permalinks
     */
    public function convert(string $markdown): string
    {
        // Resolve internal doc links before conversion
        $markdown = $this->resolveInternalLinks($markdown);

        // Convert to HTML
        $html = $this->converter->convert($markdown)->getContent();

        return $html;
    }

    /**
     * Extract title from H1
     */
    public function extractTitle(string $markdown, string $fallbackFilename): string
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }

        $name = pathinfo($fallbackFilename, PATHINFO_FILENAME);

        return ucwords(str_replace(['_', '-'], ' ', strtolower($name)));
    }

    /**
     * Generate meta description from first paragraph
     */
    public function generateMetaDescription(string $markdown): string
    {
        if (preg_match('/^#[^\n]+\n+([^\n#][^\n]+)/m', $markdown, $matches)) {
            $desc = strip_tags($matches[1]);
            $desc = preg_replace('/\s+/', ' ', $desc);
            $desc = trim($desc);

            if (strlen($desc) > 160) {
                $desc = substr($desc, 0, 157).'...';
            }

            return $desc;
        }

        return 'TallCMS Documentation';
    }

    /**
     * Resolve internal doc links (e.g., MENUS.md → /docs/menus)
     */
    protected function resolveInternalLinks(string $markdown): string
    {
        // Match markdown links: [text](FILENAME.md) or [text](./FILENAME.md)
        return preg_replace_callback(
            '/\[([^\]]+)\]\(\.?\/?([A-Za-z0-9_-]+\.md)(#[^\)]+)?\)/',
            function ($matches) {
                $text = $matches[1];
                $filename = strtoupper($matches[2]);
                $anchor = $matches[3] ?? '';

                $slug = $this->linkMap[$filename]
                    ?? strtolower(str_replace(['_', '.MD'], ['-', ''], $filename));

                return "[{$text}](/docs/{$slug}{$anchor})";
            },
            $markdown
        );
    }
}
