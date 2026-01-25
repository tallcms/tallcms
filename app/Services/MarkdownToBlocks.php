<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Extension\Table\TableSection;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

class MarkdownToBlocks
{
    protected array $blocks = [];

    protected array $contentBuffer = [];

    protected ?string $currentSectionTitle = null;

    protected string $currentHeadingLevel = 'h2';

    protected array $linkMap = [];

    protected array $tocEntries = [];

    protected Environment $environment;

    protected HtmlRenderer $renderer;

    /**
     * Language mapping for code blocks
     */
    protected array $languageMap = [
        'bash' => 'bash',
        'sh' => 'bash',
        'shell' => 'bash',
        'php' => 'php',
        'blade' => 'php',
        'js' => 'javascript',
        'javascript' => 'javascript',
        'json' => 'json',
        'env' => 'plaintext',
        'html' => 'markup',
        'css' => 'css',
        'sql' => 'sql',
        'yaml' => 'yaml',
        'yml' => 'yaml',
        '' => 'plaintext',
    ];

    public function __construct(array $linkMap = [])
    {
        $this->linkMap = $linkMap;

        $this->environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $this->environment->addExtension(new CommonMarkCoreExtension);
        $this->environment->addExtension(new GithubFlavoredMarkdownExtension);

        $this->renderer = new HtmlRenderer($this->environment);
    }

    /**
     * Parse markdown content and convert to CMS blocks
     */
    public function parse(string $markdown, bool $includeToc = false): array
    {
        // Reset state
        $this->blocks = [];
        $this->contentBuffer = [];
        $this->currentSectionTitle = null;
        $this->currentHeadingLevel = 'h2';
        $this->tocEntries = [];

        $parser = new MarkdownParser($this->environment);
        $document = $parser->parse($markdown);

        // Resolve internal links at AST level BEFORE conversion
        $this->resolveLinksInDocument($document);

        // Skip the first H1 (it's the page title)
        $skipFirstH1 = true;

        // Walk the AST and convert nodes to blocks
        foreach ($document->children() as $node) {
            // Skip first H1 - it's the page title
            if ($skipFirstH1 && $node instanceof Heading && $node->getLevel() === 1) {
                $skipFirstH1 = false;

                continue;
            }

            $this->processNode($node);
        }

        // Flush any remaining content
        $this->flushContentBuffer();

        // Prepend TOC if requested and we have entries
        if ($includeToc && count($this->tocEntries) > 1) {
            array_unshift($this->blocks, $this->createTocBlock());
        }

        return $this->blocks;
    }

    /**
     * Extract title from H1, with fallback to filename
     */
    public function extractTitle(string $markdown, string $fallbackFilename): string
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: convert filename to title
        $name = pathinfo($fallbackFilename, PATHINFO_FILENAME);

        return ucwords(str_replace(['_', '-'], ' ', strtolower($name)));
    }

    /**
     * Generate meta description from first paragraph
     */
    public function generateMetaDescription(string $markdown): string
    {
        // Find first paragraph after H1
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
     * Resolve internal doc links at AST level (before HTML conversion)
     */
    protected function resolveLinksInDocument(Document $document): void
    {
        $walker = $document->walker();

        while ($event = $walker->next()) {
            $node = $event->getNode();

            if ($event->isEntering() && $node instanceof \League\CommonMark\Extension\CommonMark\Node\Inline\Link) {
                $url = $node->getUrl();

                // Strip leading ./ for relative paths
                $cleanUrl = preg_replace('/^\.\//', '', $url);

                // Match internal doc links: any .md file with optional anchor
                if (preg_match('/^([A-Za-z0-9_-]+\.md)(#.*)?$/i', $cleanUrl, $matches)) {
                    // Normalize filename to uppercase for consistent lookup
                    $normalizedFilename = strtoupper($matches[1]);
                    $anchor = $matches[2] ?? '';

                    // Use map lookup or derive from filename
                    $slug = $this->linkMap[$normalizedFilename]
                        ?? strtolower(str_replace(['_', '.MD'], ['-', ''], $normalizedFilename));

                    $node->setUrl("/docs/{$slug}{$anchor}");
                }
            }
        }
    }

    /**
     * Process a single AST node
     */
    protected function processNode(Node $node): void
    {
        // Handle H2 headings - start new section
        if ($node instanceof Heading && $node->getLevel() === 2) {
            $this->flushContentBuffer();
            $this->currentSectionTitle = $this->getNodeText($node);
            $this->currentHeadingLevel = 'h2';

            // Collect for TOC
            $this->tocEntries[] = [
                'title' => $this->currentSectionTitle,
                'anchor' => \Illuminate\Support\Str::slug($this->currentSectionTitle),
            ];

            return;
        }

        // Handle H3/H4 headings - add to current content buffer as HTML
        if ($node instanceof Heading && in_array($node->getLevel(), [3, 4])) {
            $level = $node->getLevel();
            $text = $this->getNodeText($node);
            $this->contentBuffer[] = "<h{$level}>{$text}</h{$level}>";

            return;
        }

        // Handle code blocks - flush and create dedicated block
        if ($node instanceof FencedCode) {
            $this->flushContentBuffer();
            $this->blocks[] = $this->createCodeBlock(
                $node->getLiteral(),
                $node->getInfo() ?? ''
            );

            return;
        }

        // Handle tables - flush and create dedicated block
        if ($node instanceof Table) {
            $this->flushContentBuffer();
            $this->blocks[] = $this->createTableBlock($node);

            return;
        }

        // Handle thematic breaks (---) - just skip, sections have natural spacing
        if ($node instanceof ThematicBreak) {
            return;
        }

        // Handle HTML blocks - skip them (we strip raw HTML)
        if ($node instanceof HtmlBlock) {
            return;
        }

        // Handle paragraphs and lists - render to HTML and add to buffer
        if ($node instanceof Paragraph || $node instanceof ListBlock) {
            $html = $this->renderer->renderNodes([$node]);
            $this->contentBuffer[] = trim((string) $html);

            return;
        }
    }

    /**
     * Flush content buffer to a ContentBlock
     */
    protected function flushContentBuffer(): void
    {
        if (empty($this->contentBuffer) && $this->currentSectionTitle === null) {
            return;
        }

        $body = implode("\n", $this->contentBuffer);

        // Only create block if there's actual content
        if (! empty($body) || $this->currentSectionTitle !== null) {
            $this->blocks[] = $this->createContentBlock(
                $this->currentSectionTitle ?? '',
                $body,
                $this->currentHeadingLevel
            );
        }

        // Reset buffer
        $this->contentBuffer = [];
        $this->currentSectionTitle = null;
    }

    /**
     * Get text content from a node (recursively)
     */
    protected function getNodeText(Node $node): string
    {
        $text = '';

        $walker = $node->walker();
        while ($event = $walker->next()) {
            $current = $event->getNode();
            if ($event->isEntering() && $current instanceof Text) {
                $text .= $current->getLiteral();
            }
        }

        return $text;
    }

    /**
     * Create a ContentBlock
     */
    protected function createContentBlock(string $title, string $body, string $level): array
    {
        // Generate anchor ID from title
        $anchorId = ! empty($title) ? \Illuminate\Support\Str::slug($title) : null;

        return [
            'type' => 'customBlock',
            'data' => [
                'type' => 'content_block',
                'values' => [
                    'title' => $title,
                    'body' => $body,
                    'heading_level' => $level,
                    'content_width' => 'normal',
                    'background' => 'bg-base-100',
                    'padding' => 'py-4',
                    'anchor_id' => $anchorId,
                ],
            ],
        ];
    }

    /**
     * Create code block using Pro CodeSnippetBlock
     */
    protected function createCodeBlock(string $code, string $language): array
    {
        $mappedLang = $this->languageMap[$language] ?? $language ?: 'plaintext';

        return [
            'type' => 'customBlock',
            'data' => [
                'type' => 'pro-code-snippet',
                'values' => [
                    'language' => $mappedLang,
                    'code' => rtrim($code),
                    'line_prefix' => 'numbers',
                    'show_line_numbers' => true,
                    'show_copy_button' => true,
                    'show_language_badge' => true,
                    'max_height' => 'lg',
                ],
            ],
        ];
    }

    /**
     * Create table block using Pro TableBlock
     */
    protected function createTableBlock(Table $table): array
    {
        $headers = [];
        $rows = [];

        foreach ($table->children() as $section) {
            if (! $section instanceof TableSection) {
                continue;
            }

            $isHeader = $section->isHead();

            foreach ($section->children() as $row) {
                if (! $row instanceof TableRow) {
                    continue;
                }

                $rowData = [];
                foreach ($row->children() as $cell) {
                    if ($cell instanceof TableCell) {
                        $cellContent = $this->renderer->renderNodes($cell->children());
                        $rowData[] = trim(strip_tags((string) $cellContent));
                    }
                }

                if ($isHeader) {
                    // Headers need to be array of objects with 'label' and 'align' keys
                    $headers = array_map(fn ($label) => [
                        'label' => $label,
                        'align' => 'left',
                    ], $rowData);
                } else {
                    // Rows need to be array of objects with 'cells' and 'highlight' keys
                    // Cells need to be array of objects with 'value' key
                    $rows[] = [
                        'cells' => array_map(fn ($value) => ['value' => $value], $rowData),
                        'highlight' => false,
                    ];
                }
            }
        }

        return [
            'type' => 'customBlock',
            'data' => [
                'type' => 'pro-table',
                'values' => [
                    'headers' => $headers,
                    'rows' => $rows,
                    'table_size' => 'md',
                    'striped' => true,
                    'bordered' => true,
                    'hover' => true,
                    'responsive' => true,
                ],
            ],
        ];
    }

    /**
     * Create table of contents block using Pro TableBlock with anchor links
     */
    protected function createTocBlock(): array
    {
        $rows = [];

        foreach ($this->tocEntries as $entry) {
            $rows[] = [
                'cells' => [
                    ['value' => '<a href="#'.$entry['anchor'].'" class="link link-primary">'.$entry['title'].'</a>'],
                ],
                'highlight' => false,
            ];
        }

        return [
            'type' => 'customBlock',
            'data' => [
                'type' => 'pro-table',
                'values' => [
                    'heading' => 'Table of Contents',
                    'headers' => [
                        ['label' => 'Section', 'align' => 'left'],
                    ],
                    'rows' => $rows,
                    'table_size' => 'md',
                    'striped' => false,
                    'bordered' => true,
                    'hover' => true,
                    'responsive' => true,
                ],
            ],
        ];
    }
}
