<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $this->command->info('TallCMS homepage created successfully!');
    }

    protected function createHomepage(): void
    {
        $page = CmsPage::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'meta_title' => 'TallCMS - The Modern CMS for Laravel Developers',
                'meta_description' => 'Build beautiful, content-rich websites with the TALL stack, Filament 4, and 30+ DaisyUI themes. Free and open source.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'is_homepage' => true,
                'author_id' => $this->author->id,
            ]
        );

        $page->setTranslation('content', app()->getLocale(), $this->getHomepageContent());
        $page->save();

        $this->command->info('Created homepage');
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
