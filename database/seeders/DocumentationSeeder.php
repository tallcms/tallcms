<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CmsPage;
use App\Models\User;
use App\Services\MarkdownToBlocks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocumentationSeeder extends Seeder
{
    protected MarkdownToBlocks $converter;

    protected ?CmsPage $docsParent = null;

    protected int $authorId;

    /**
     * Markdown file → URL slug mapping
     */
    protected array $slugMap = [
        'INSTALLATION.md' => 'installation',
        'BLOCK_DEVELOPMENT.md' => 'block-development',
        'CMS_RICH_EDITOR.md' => 'rich-editor',
        'MENUS.md' => 'menus',
        'THEME_DEVELOPMENT.md' => 'themes',
        'PLUGIN_DEVELOPMENT.md' => 'plugins',
        'SITE_SETTINGS.md' => 'site-settings',
        'SEO.md' => 'seo',
        'PUBLISHING_WORKFLOW.md' => 'publishing',
        'INTERNATIONALIZATION.md' => 'i18n',
        'CUSTOM_BLOCK_STYLING.md' => 'block-styling',
        'DEVELOPER_ARCHITECTURE.md' => 'architecture',
    ];

    /**
     * Processing order (for internal link resolution)
     */
    protected array $processingOrder = [
        'INSTALLATION.md',
        'CMS_RICH_EDITOR.md',
        'BLOCK_DEVELOPMENT.md',
        'CUSTOM_BLOCK_STYLING.md',
        'MENUS.md',
        'THEME_DEVELOPMENT.md',
        'PLUGIN_DEVELOPMENT.md',
        'SITE_SETTINGS.md',
        'SEO.md',
        'PUBLISHING_WORKFLOW.md',
        'INTERNATIONALIZATION.md',
        'DEVELOPER_ARCHITECTURE.md',
    ];

    public function run(): void
    {
        // Ensure at least one user exists for author_id
        $author = User::first();
        if (! $author) {
            $this->command->error('No users found. Please create a user first: php artisan make:user');

            return;
        }
        $this->authorId = $author->id;

        // Pass link map to converter for AST-level resolution
        $this->converter = new MarkdownToBlocks($this->slugMap);

        // Clean up existing documentation pages first
        $this->cleanupExistingDocs();

        // Create the docs index page first
        $this->createDocsIndexPage();

        // Process each markdown file
        foreach ($this->processingOrder as $filename) {
            $this->processMarkdownFile($filename);
        }

        $this->command->info('Documentation seeded successfully!');
    }

    /**
     * Remove all existing documentation pages before recreating
     */
    protected function cleanupExistingDocs(): void
    {
        // First find the docs parent page
        $docsParent = CmsPage::withSlug('docs')->first();

        if (! $docsParent) {
            return;
        }

        // Delete all child pages first (to avoid foreign key issues)
        $childCount = CmsPage::where('parent_id', $docsParent->id)->count();
        if ($childCount > 0) {
            CmsPage::where('parent_id', $docsParent->id)->forceDelete();
        }

        // Then delete the parent page
        $docsParent->forceDelete();

        $totalCount = $childCount + 1;
        $this->command->info("Cleaned up {$totalCount} existing documentation pages.");
    }

    /**
     * Create the /docs index page with hero and feature cards
     */
    protected function createDocsIndexPage(): void
    {
        $this->docsParent = CmsPage::create([
            'title' => 'Documentation',
            'slug' => 'docs',
            'content' => $this->getDocsIndexContent(),
            'meta_title' => 'Documentation - TallCMS',
            'meta_description' => 'Complete documentation for TallCMS. Learn how to install, configure, and customize your CMS.',
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
            'author_id' => $this->authorId,
        ]);

        $this->command->info('Created: /docs (index page)');
    }

    /**
     * Process a single markdown file into a CMS page
     */
    protected function processMarkdownFile(string $filename): void
    {
        $path = base_path("docs/{$filename}");

        if (! File::exists($path)) {
            $this->command->warn("Skipping missing file: {$filename}");

            return;
        }

        $markdown = File::get($path);
        $slug = $this->slugMap[$filename] ?? Str::slug(pathinfo($filename, PATHINFO_FILENAME));

        // Extract title with fallback to filename
        $title = $this->converter->extractTitle($markdown, $filename);

        // Convert markdown to blocks (links resolved at AST level, with TOC)
        $blocks = $this->converter->parse($markdown, includeToc: true);

        // Create child page - use full slug path for URL routing, parent_id for admin organization
        CmsPage::create([
            'title' => $title,
            'slug' => "docs/{$slug}",
            'content' => $this->wrapContent($blocks),
            'parent_id' => $this->docsParent->id,
            'meta_title' => "{$title} - TallCMS Documentation",
            'meta_description' => $this->converter->generateMetaDescription($markdown),
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
            'author_id' => $this->authorId,
        ]);

        $this->command->info("Created: /docs/{$slug}");
    }

    /**
     * Wrap content blocks in Tiptap document structure with locale key
     */
    protected function wrapContent(array $blocks): array
    {
        // Transform blocks to correct Tiptap/RichEditor format
        $transformedBlocks = array_map(function ($block) {
            if ($block['type'] === 'customBlock' && isset($block['data'])) {
                return [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => $block['data']['type'],
                        'config' => $block['data']['values'] ?? [],
                    ],
                ];
            }

            return $block;
        }, $blocks);

        // Wrap in locale key for translatable storage
        $locale = config('app.locale', 'en');

        return [
            $locale => [
                'type' => 'doc',
                'content' => $transformedBlocks,
            ],
        ];
    }

    /**
     * Get the documentation index page content
     */
    protected function getDocsIndexContent(): array
    {
        return $this->wrapContent([
            // Hero section
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => '<span data-color="text-primary">Documentation</span>',
                        'subheading' => 'Everything you need to build with TallCMS. From installation to advanced customization.',
                        'height' => 'min-h-[40vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 50,
                    ],
                ],
            ],
            // Getting Started section
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Getting Started',
                        'subheading' => 'Learn the basics and get TallCMS up and running.',
                        'features' => [
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-arrow-down-tray',
                                'title' => 'Installation',
                                'description' => 'System requirements, setup options, and configuration.',
                                'link' => '/docs/installation',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-document-text',
                                'title' => 'Rich Editor',
                                'description' => 'Learn to use the visual content editor and blocks.',
                                'link' => '/docs/rich-editor',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-bars-3',
                                'title' => 'Menu Management',
                                'description' => 'Create and configure navigation menus.',
                                'link' => '/docs/menus',
                            ],
                        ],
                        'columns' => '3',
                        'card_style' => 'card bg-base-100 border border-base-300',
                    ],
                ],
            ],
            // Content & SEO section
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Content & SEO',
                        'subheading' => 'Optimize and manage your content effectively.',
                        'features' => [
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-magnifying-glass',
                                'title' => 'SEO',
                                'description' => 'Meta tags, Open Graph, sitemaps, and more.',
                                'link' => '/docs/seo',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-clipboard-document-check',
                                'title' => 'Publishing Workflow',
                                'description' => 'Draft, review, and publish content with roles.',
                                'link' => '/docs/publishing',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-cog-6-tooth',
                                'title' => 'Site Settings',
                                'description' => 'Global configuration and SPA mode.',
                                'link' => '/docs/site-settings',
                            ],
                        ],
                        'columns' => '3',
                        'card_style' => 'card bg-base-100 border border-base-300',
                    ],
                ],
            ],
            // Customization section
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Customization',
                        'subheading' => 'Extend and customize TallCMS to fit your needs.',
                        'features' => [
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-cube',
                                'title' => 'Block Development',
                                'description' => 'Create custom content blocks for the editor.',
                                'link' => '/docs/block-development',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-paint-brush',
                                'title' => 'Block Styling',
                                'description' => 'CSS architecture and styling patterns.',
                                'link' => '/docs/block-styling',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-swatch',
                                'title' => 'Theme Development',
                                'description' => 'Build custom themes with Tailwind and DaisyUI.',
                                'link' => '/docs/themes',
                            ],
                        ],
                        'columns' => '3',
                        'card_style' => 'card bg-base-100 border border-base-300',
                    ],
                ],
            ],
            // Advanced section
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Advanced',
                        'subheading' => 'For developers building on top of TallCMS.',
                        'features' => [
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-puzzle-piece',
                                'title' => 'Plugin Development',
                                'description' => 'Create plugins to extend functionality.',
                                'link' => '/docs/plugins',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-language',
                                'title' => 'Internationalization',
                                'description' => 'Multi-language support and translation.',
                                'link' => '/docs/i18n',
                            ],
                            [
                                'icon_type' => 'heroicon',
                                'icon' => 'heroicon-o-server-stack',
                                'title' => 'Architecture',
                                'description' => 'Technical architecture and internals.',
                                'link' => '/docs/architecture',
                            ],
                        ],
                        'columns' => '3',
                        'card_style' => 'card bg-base-100 border border-base-300',
                    ],
                ],
            ],
            // CTA
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'Need Help?',
                        'description' => 'Join our Discord community or open an issue on GitHub.',
                        'button_text' => 'Join Discord',
                        'button_link_type' => 'external',
                        'button_url' => 'https://discord.gg/tallcms',
                        'button_variant' => 'btn-primary',
                        'secondary_button_text' => 'GitHub Issues',
                        'secondary_button_link_type' => 'external',
                        'secondary_button_url' => 'https://github.com/tallcms/tallcms/issues',
                        'secondary_button_variant' => 'btn-outline btn-primary',
                        'background' => 'bg-base-200',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
        ]);
    }
}
