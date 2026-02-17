<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\TallcmsMenu;
use TallCms\Cms\Models\TallcmsMenuItem;

class TallcmsWebsiteSeeder extends Seeder
{
    protected ?User $author = null;

    public function run(): void
    {
        $this->author = User::first() ?? User::factory()->create([
            'name' => 'TallCMS',
            'email' => 'hello@tallcms.com',
        ]);

        $this->createHomepage();
        $this->createDocumentationPage();
        $this->createHeaderMenu();

        $this->command->info('TallCMS website seeded successfully!');
    }

    protected function createHomepage(): void
    {
        $page = CmsPage::withSlug('home')->first();

        if (! $page) {
            $page = CmsPage::create([
                'title' => 'Home',
                'slug' => 'home',
                'meta_title' => 'TallCMS - The Modern CMS for Laravel Developers',
                'meta_description' => 'Build beautiful, content-rich websites with the TALL stack, Filament 4, and 30+ DaisyUI themes. Free and open source.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'is_homepage' => true,
                'author_id' => $this->author->id,
            ]);
        }

        $page->setTranslation('content', app()->getLocale(), $this->getHomepageContent());
        $page->save();

        $this->command->info('Created homepage');
    }

    protected function createDocumentationPage(): void
    {
        $page = CmsPage::withSlug('documentation')->first();

        if (! $page) {
            $page = CmsPage::create([
                'title' => 'Documentation',
                'slug' => 'documentation',
                'meta_title' => 'Documentation - TallCMS',
                'meta_description' => 'Learn how to install, configure, and build with TallCMS.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'is_homepage' => false,
                'author_id' => $this->author->id,
            ]);
        }

        $page->setTranslation('content', app()->getLocale(), $this->getDocumentationContent());
        $page->save();

        $this->command->info('Created documentation page');
    }

    protected function createHeaderMenu(): void
    {
        $menu = TallcmsMenu::firstOrCreate(
            ['location' => 'header'],
            [
                'name' => 'Header',
                'is_active' => true,
            ]
        );

        // Skip if menu already has items
        if ($menu->allItems()->count() > 0) {
            $this->command->info('Header menu already has items, skipping');

            return;
        }

        $homePage = CmsPage::withSlug('home')->first();
        $docsPage = CmsPage::withSlug('documentation')->first();

        TallcmsMenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => 'page',
            'page_id' => $homePage?->id,
            'is_active' => true,
        ]);

        TallcmsMenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Documentation',
            'type' => 'page',
            'page_id' => $docsPage?->id,
            'is_active' => true,
        ]);

        $this->command->info('Created header menu');
    }

    /**
     * Wrap content blocks in Tiptap document structure
     */
    protected function wrapContent(array $blocks): array
    {
        $transformedBlocks = array_map(function ($block) {
            if ($block['type'] === 'customBlock' && isset($block['data'])) {
                return [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => $block['data']['type'],
                        'config' => $block['data']['values'] ?? [],
                    ],
                    'content' => [],
                ];
            }
            return $block;
        }, $blocks);

        return [
            'type' => 'doc',
            'content' => $transformedBlocks,
        ];
    }

    protected function getDocumentationContent(): array
    {
        // Look up the "Getting Started" category by slug
        $category = CmsCategory::withSlug('getting-started')->first();
        $categoryIds = $category ? [$category->id] : [];

        $blocks = [
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [
                    ['type' => 'text', 'text' => 'Getting Started'],
                ],
            ],
            [
                'type' => 'customBlock',
                'attrs' => [
                    'id' => 'posts',
                    'config' => [
                        'block_uuid' => (string) Str::uuid(),
                        'categories' => $categoryIds,
                        'featured_only' => false,
                        'posts_count' => 6,
                        'offset' => 0,
                        'sort_by' => 'oldest',
                        'show_image' => true,
                        'show_excerpt' => true,
                        'show_date' => true,
                        'show_author' => false,
                        'show_categories' => true,
                        'show_read_more' => true,
                        'empty_message' => null,
                        'layout' => 'grid',
                        'columns' => 3,
                        'enable_pagination' => false,
                        'show_featured_badge' => false,
                        'featured_card_style' => 'default',
                        'content_width' => 'inherit',
                        'background' => 'bg-base-100',
                        'padding' => 'py-16',
                        'first_section' => false,
                        'animation_type' => null,
                        'animation_duration' => 'anim-duration-700',
                        'anchor_id' => null,
                        'css_classes' => null,
                    ],
                ],
                'content' => [],
            ],
        ];

        return [
            'type' => 'doc',
            'content' => $blocks,
        ];
    }

    protected function getHomepageContent(): array
    {
        return $this->wrapContent([
            // Hero Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => 'The Modern CMS for <span data-color="text-primary">Laravel</span> Developers',
                        'subheading' => 'Build beautiful, content-rich websites with the TALL stack, Filament 4, and 30+ DaisyUI themes. Free and open source.',
                        'button_text' => 'Get Started Free',
                        'button_link_type' => 'page',
                        'button_url' => '/docs',
                        'button_variant' => 'btn-primary',
                        'button_size' => 'btn-lg',
                        'secondary_button_text' => 'View Blocks',
                        'secondary_button_link_type' => 'custom',
                        'secondary_button_url' => '/blocks',
                        'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                        'height' => 'min-h-[90vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 50,
                    ],
                ],
            ],
            // Logos Block - Tech Stack
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'logos',
                    'values' => [
                        'heading' => 'Built with Modern Technologies',
                        'subheading' => 'Powered by the best tools in the Laravel ecosystem',
                        'logo_source' => 'manual',
                        'logos' => [
                            ['name' => 'Laravel', 'url' => 'https://laravel.com'],
                            ['name' => 'Livewire', 'url' => 'https://livewire.laravel.com'],
                            ['name' => 'Alpine.js', 'url' => 'https://alpinejs.dev'],
                            ['name' => 'Tailwind CSS', 'url' => 'https://tailwindcss.com'],
                            ['name' => 'Filament', 'url' => 'https://filamentphp.com'],
                            ['name' => 'DaisyUI', 'url' => 'https://daisyui.com'],
                        ],
                        'layout' => 'inline',
                        'grayscale' => true,
                        'columns' => 6,
                        'logo_size' => 'medium',
                    ],
                ],
            ],
            // Features Block - Core Capabilities
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Everything You Need',
                        'subheading' => 'A complete CMS solution with all the features you need to build amazing websites',
                        'features' => [
                            [
                                'title' => '24 Content Blocks',
                                'description' => 'From hero sections to pricing tables, testimonials to contact forms. Build any layout without code.',
                                'icon' => 'squares-2x2',
                            ],
                            [
                                'title' => 'Visual Page Builder',
                                'description' => 'Drag-and-drop interface with live preview. See exactly what your visitors will see.',
                                'icon' => 'cursor-arrow-rays',
                            ],
                            [
                                'title' => '30+ DaisyUI Themes',
                                'description' => 'Switch between beautiful themes instantly. Light, dark, and everything in between.',
                                'icon' => 'swatch',
                            ],
                            [
                                'title' => 'Multi-Theme System',
                                'description' => 'Create custom themes with your own branding. Full Tailwind CSS support.',
                                'icon' => 'paint-brush',
                            ],
                            [
                                'title' => 'Plugin Architecture',
                                'description' => 'Extend functionality with plugins. Build your own or use community plugins.',
                                'icon' => 'puzzle-piece',
                            ],
                            [
                                'title' => 'Role-Based Access',
                                'description' => 'Fine-grained permissions with Filament Shield. Control who can do what.',
                                'icon' => 'shield-check',
                            ],
                        ],
                        'columns' => 3,
                        'card_style' => 'shadow',
                        'icon_position' => 'top',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
            // Stats Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'stats',
                    'values' => [
                        'heading' => 'By the Numbers',
                        'stats' => [
                            ['value' => '24', 'label' => 'Content Blocks', 'icon' => 'squares-2x2'],
                            ['value' => '30', 'suffix' => '+', 'label' => 'DaisyUI Themes', 'icon' => 'swatch'],
                            ['value' => '8', 'label' => 'Pro Blocks', 'icon' => 'star'],
                            ['value' => '100', 'suffix' => '%', 'label' => 'Open Source', 'icon' => 'code-bracket'],
                        ],
                        'columns' => 4,
                        'style' => 'cards',
                        'animate' => true,
                    ],
                ],
            ],
            // Call to Action
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'Ready to Build Something Amazing?',
                        'description' => 'Get started with TallCMS today. It\'s free, open source, and built for Laravel developers.',
                        'button_text' => 'Start Building',
                        'button_link_type' => 'custom',
                        'button_url' => '/docs',
                        'button_variant' => 'btn-primary',
                        'button_size' => 'btn-lg',
                        'secondary_button_text' => 'View on GitHub',
                        'secondary_button_link_type' => 'external',
                        'secondary_button_url' => 'https://github.com/tallcms/tallcms',
                        'secondary_button_variant' => 'btn-outline btn-primary',
                        'background' => 'bg-base-200',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
        ]);
    }
}
