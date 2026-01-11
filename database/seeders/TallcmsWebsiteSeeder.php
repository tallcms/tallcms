<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\TallcmsMenu;
use App\Models\TallcmsMenuItem;
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

        $this->createPages();
        $this->createBlogPosts();
        $this->createMenus();

        $this->command->info('TallCMS website content created successfully!');
    }

    protected function createPages(): void
    {
        // Homepage
        $homepage = CmsPage::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'content' => $this->getHomepageContent(),
                'meta_title' => 'TallCMS - The Modern CMS for Laravel Developers',
                'meta_description' => 'Build beautiful, content-rich websites with the TALL stack, Filament 4, and 30+ DaisyUI themes. Free and open source.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'is_homepage' => true,
                'author_id' => $this->author->id,
            ]
        );

        // Features Page
        CmsPage::updateOrCreate(
            ['slug' => 'features'],
            [
                'title' => 'Features',
                'content' => $this->getFeaturesContent(),
                'meta_title' => 'Features - TallCMS',
                'meta_description' => 'Discover all the powerful features of TallCMS: 24 content blocks, visual page builder, multi-theme system, and more.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        // Blocks Showcase Page
        CmsPage::updateOrCreate(
            ['slug' => 'blocks'],
            [
                'title' => 'Block Showcase',
                'content' => $this->getBlocksShowcaseContent(),
                'meta_title' => 'Block Showcase - TallCMS',
                'meta_description' => 'See all 24 TallCMS blocks in action. From hero sections to pricing tables, testimonials to contact forms.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        // Pricing Page
        CmsPage::updateOrCreate(
            ['slug' => 'pricing'],
            [
                'title' => 'Pricing',
                'content' => $this->getPricingContent(),
                'meta_title' => 'Pricing - TallCMS',
                'meta_description' => 'Simple, transparent pricing. TallCMS Core is free forever. Upgrade to Pro for advanced blocks and priority support.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        // Documentation Page
        CmsPage::updateOrCreate(
            ['slug' => 'docs'],
            [
                'title' => 'Documentation',
                'content' => $this->getDocsContent(),
                'meta_title' => 'Documentation - TallCMS',
                'meta_description' => 'Get started with TallCMS. Installation guides, configuration, and tutorials.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        // About Page
        CmsPage::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About',
                'content' => $this->getAboutContent(),
                'meta_title' => 'About - TallCMS',
                'meta_description' => 'Learn about TallCMS and the modern tech stack powering it: Laravel, Livewire, Alpine.js, Tailwind CSS, Filament, and DaisyUI.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        // Contact Page
        CmsPage::updateOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contact',
                'content' => $this->getContactContent(),
                'meta_title' => 'Contact - TallCMS',
                'meta_description' => 'Get in touch with the TallCMS team. We\'re here to help.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        // Blog Page
        CmsPage::updateOrCreate(
            ['slug' => 'blog'],
            [
                'title' => 'Blog',
                'content' => $this->getBlogContent(),
                'meta_title' => 'Blog - TallCMS',
                'meta_description' => 'News, tutorials, and updates from the TallCMS team.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'author_id' => $this->author->id,
            ]
        );

        $this->command->info('Created 8 pages');
    }

    /**
     * Wrap content blocks in Tiptap document structure
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
            // Pricing Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'pricing',
                    'values' => [
                        'heading' => 'Simple, Transparent Pricing',
                        'subheading' => 'Start free, upgrade when you need more',
                        'plans' => [
                            [
                                'name' => 'Core',
                                'description' => 'Everything you need to get started',
                                'price' => '0',
                                'currency' => '$',
                                'billing_period' => 'forever',
                                'is_popular' => false,
                                'features' => [
                                    ['text' => '16 Content Blocks', 'included' => true],
                                    ['text' => 'Unlimited Sites', 'included' => true],
                                    ['text' => 'Multi-Theme System', 'included' => true],
                                    ['text' => '30+ DaisyUI Themes', 'included' => true],
                                    ['text' => 'Community Support', 'included' => true],
                                    ['text' => 'MIT License', 'included' => true],
                                    ['text' => 'Pro Blocks', 'included' => false],
                                    ['text' => 'Priority Support', 'included' => false],
                                ],
                                'button_text' => 'Get Started',
                                'button_url' => '/docs',
                            ],
                            [
                                'name' => 'Pro',
                                'description' => 'Advanced blocks and priority support',
                                'price' => '99',
                                'currency' => '$',
                                'billing_period' => '/year',
                                'is_popular' => true,
                                'features' => [
                                    ['text' => 'Everything in Core', 'included' => true],
                                    ['text' => '+8 Pro Blocks', 'included' => true],
                                    ['text' => 'Accordion & Tabs', 'included' => true],
                                    ['text' => 'Video Embeds', 'included' => true],
                                    ['text' => 'Code Snippets', 'included' => true],
                                    ['text' => 'Before/After Slider', 'included' => true],
                                    ['text' => 'Priority Email Support', 'included' => true],
                                    ['text' => 'Pro Block Updates', 'included' => true],
                                ],
                                'button_text' => 'Get Pro',
                                'button_url' => '/pricing',
                            ],
                        ],
                        'columns' => 2,
                        'card_style' => 'shadow',
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

    protected function getFeaturesContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => 'Powerful Features for <span data-color="text-primary">Modern</span> Websites',
                        'subheading' => 'Everything you need to build beautiful, content-rich websites with Laravel.',
                        'height' => 'min-h-[50vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Content Management Features
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Content Management',
                        'subheading' => 'Powerful tools to manage your content',
                        'features' => [
                            [
                                'title' => 'Rich Content Editor',
                                'description' => 'A powerful block-based editor with live preview. Add text, images, videos, and custom blocks.',
                                'icon' => 'document-text',
                            ],
                            [
                                'title' => 'Pages & Posts',
                                'description' => 'Manage static pages and blog posts with categories, tags, and SEO settings.',
                                'icon' => 'document-duplicate',
                            ],
                            [
                                'title' => 'Media Library',
                                'description' => 'Upload and manage images, videos, and files. Supports local storage and S3.',
                                'icon' => 'photo',
                            ],
                            [
                                'title' => 'Menu Builder',
                                'description' => 'Create and manage navigation menus with drag-and-drop. Support for nested menus.',
                                'icon' => 'bars-3',
                            ],
                        ],
                        'columns' => 4,
                        'card_style' => 'bordered',
                        'icon_position' => 'top',
                    ],
                ],
            ],
            // Developer Features
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Developer Experience',
                        'subheading' => 'Built by developers, for developers',
                        'features' => [
                            [
                                'title' => 'Filament 4 Admin',
                                'description' => 'Beautiful admin panel built on Filament. Customize everything with familiar Laravel patterns.',
                                'icon' => 'cog-6-tooth',
                            ],
                            [
                                'title' => 'Theme System',
                                'description' => 'Create custom themes with Blade templates and Tailwind CSS. Full control over your design.',
                                'icon' => 'paint-brush',
                            ],
                            [
                                'title' => 'Plugin Architecture',
                                'description' => 'Extend functionality with plugins. Well-documented API for building your own.',
                                'icon' => 'puzzle-piece',
                            ],
                            [
                                'title' => 'Merge Tags',
                                'description' => 'Dynamic content with merge tags like {{site_name}}, {{current_year}}, and custom tags.',
                                'icon' => 'code-bracket',
                            ],
                        ],
                        'columns' => 4,
                        'card_style' => 'bordered',
                        'icon_position' => 'top',
                        'background' => 'bg-base-200',
                    ],
                ],
            ],
            // CTA
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'See All Blocks in Action',
                        'description' => 'Visit our block showcase to see every block with live examples.',
                        'button_text' => 'View Block Showcase',
                        'button_link_type' => 'custom',
                        'button_url' => '/blocks',
                        'button_variant' => 'btn-primary',
                        'button_size' => 'btn-lg',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
        ]);
    }

    protected function getBlocksShowcaseContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => 'Block <span data-color="text-primary">Showcase</span>',
                        'subheading' => 'See all 24 TallCMS blocks in action. Each block is fully customizable with the visual editor.',
                        'height' => 'min-h-[50vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Content Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'content_block',
                    'values' => [
                        'title' => 'Content Block',
                        'subtitle' => 'Rich text content with formatting options',
                        'content' => '<p>The Content Block is your go-to for adding formatted text to your pages. It supports <strong>bold</strong>, <em>italic</em>, links, lists, and more.</p><p>Use it for articles, descriptions, or any text content that needs formatting.</p>',
                        'heading_level' => 'h2',
                        'width' => 'normal',
                    ],
                ],
            ],
            // Divider
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'divider',
                    'values' => [
                        'style' => 'line',
                        'height' => 'medium',
                        'line_style' => 'solid',
                        'line_width' => 'wide',
                    ],
                ],
            ],
            // Features Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Features Block',
                        'subheading' => 'Showcase features with icons and descriptions',
                        'features' => [
                            ['title' => 'Easy to Use', 'description' => 'Intuitive interface that anyone can use', 'icon' => 'cursor-arrow-rays'],
                            ['title' => 'Customizable', 'description' => 'Adjust colors, layouts, and styles', 'icon' => 'adjustments-horizontal'],
                            ['title' => 'Responsive', 'description' => 'Looks great on all devices', 'icon' => 'device-phone-mobile'],
                        ],
                        'columns' => 3,
                        'card_style' => 'shadow',
                    ],
                ],
            ],
            // Stats Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'stats',
                    'values' => [
                        'heading' => 'Stats Block',
                        'subheading' => 'Display impressive numbers with animated counters',
                        'stats' => [
                            ['value' => '1000', 'suffix' => '+', 'label' => 'Happy Users'],
                            ['value' => '50', 'label' => 'Countries'],
                            ['value' => '99.9', 'suffix' => '%', 'label' => 'Uptime'],
                            ['value' => '24', 'suffix' => '/7', 'label' => 'Support'],
                        ],
                        'columns' => 4,
                        'animate' => true,
                        'style' => 'cards',
                    ],
                ],
            ],
            // Testimonials Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'testimonials',
                    'values' => [
                        'heading' => 'Testimonials Block',
                        'subheading' => 'Show what your customers are saying',
                        'testimonials' => [
                            [
                                'quote' => 'TallCMS made building our company website a breeze. The block system is incredibly intuitive.',
                                'author_name' => 'Sarah Johnson',
                                'author_title' => 'CEO, TechStart',
                                'rating' => 5,
                            ],
                            [
                                'quote' => 'As a Laravel developer, I love how TallCMS feels like a natural extension of my workflow.',
                                'author_name' => 'Mike Chen',
                                'author_title' => 'Senior Developer',
                                'rating' => 5,
                            ],
                            [
                                'quote' => 'The DaisyUI integration means our sites look professional without fighting with CSS.',
                                'author_name' => 'Emily Rodriguez',
                                'author_title' => 'Agency Owner',
                                'rating' => 5,
                            ],
                        ],
                        'layout' => 'grid',
                        'columns' => 3,
                        'show_rating' => true,
                    ],
                ],
            ],
            // FAQ Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'faq',
                    'values' => [
                        'heading' => 'FAQ Block',
                        'subheading' => 'Answer common questions with an accordion or list',
                        'items' => [
                            ['question' => 'How do I install TallCMS?', 'answer' => 'Install via Composer with `composer require tallcms/tallcms`, then run the install command.'],
                            ['question' => 'Is TallCMS free?', 'answer' => 'Yes! TallCMS Core is 100% free and open source under the MIT license.'],
                            ['question' => 'Can I use it for client projects?', 'answer' => 'Absolutely. Use TallCMS for unlimited personal and client projects.'],
                        ],
                        'style' => 'accordion',
                        'allow_multiple' => true,
                        'first_open' => true,
                    ],
                ],
            ],
            // Timeline Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'timeline',
                    'values' => [
                        'heading' => 'Timeline Block',
                        'subheading' => 'Show a chronological sequence of events',
                        'items' => [
                            ['title' => 'Project Kickoff', 'description' => 'Initial planning and requirements gathering', 'date' => 'Week 1', 'icon' => 'rocket-launch'],
                            ['title' => 'Development', 'description' => 'Building the core features', 'date' => 'Week 2-4', 'icon' => 'code-bracket'],
                            ['title' => 'Testing', 'description' => 'Quality assurance and bug fixes', 'date' => 'Week 5', 'icon' => 'bug-ant'],
                            ['title' => 'Launch', 'description' => 'Go live and celebrate!', 'date' => 'Week 6', 'icon' => 'sparkles'],
                        ],
                        'layout' => 'vertical',
                        'alternating' => true,
                        'show_line' => true,
                    ],
                ],
            ],
            // Pricing Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'pricing',
                    'values' => [
                        'heading' => 'Pricing Block',
                        'subheading' => 'Display your pricing plans beautifully',
                        'plans' => [
                            [
                                'name' => 'Starter',
                                'price' => '9',
                                'currency' => '$',
                                'billing_period' => '/month',
                                'features' => [
                                    ['text' => '5 Projects', 'included' => true],
                                    ['text' => '10GB Storage', 'included' => true],
                                    ['text' => 'Priority Support', 'included' => false],
                                ],
                                'button_text' => 'Get Started',
                            ],
                            [
                                'name' => 'Professional',
                                'price' => '29',
                                'currency' => '$',
                                'billing_period' => '/month',
                                'is_popular' => true,
                                'features' => [
                                    ['text' => 'Unlimited Projects', 'included' => true],
                                    ['text' => '100GB Storage', 'included' => true],
                                    ['text' => 'Priority Support', 'included' => true],
                                ],
                                'button_text' => 'Get Started',
                            ],
                            [
                                'name' => 'Enterprise',
                                'price' => '99',
                                'currency' => '$',
                                'billing_period' => '/month',
                                'features' => [
                                    ['text' => 'Unlimited Everything', 'included' => true],
                                    ['text' => 'Dedicated Support', 'included' => true],
                                    ['text' => 'Custom Integrations', 'included' => true],
                                ],
                                'button_text' => 'Contact Sales',
                            ],
                        ],
                        'columns' => 3,
                    ],
                ],
            ],
            // Contact Form Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'contact_form',
                    'values' => [
                        'title' => 'Contact Form Block',
                        'description' => 'Collect inquiries with a customizable contact form',
                        'fields' => [
                            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'placeholder' => 'Your name'],
                            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'placeholder' => 'your@email.com'],
                            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'placeholder' => 'How can we help?'],
                        ],
                        'button_text' => 'Send Message',
                        'success_message' => 'Thanks for reaching out! We\'ll get back to you soon.',
                    ],
                ],
            ],
            // Call to Action
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'Call to Action Block',
                        'description' => 'Drive conversions with compelling CTAs. Customize the text, buttons, and background.',
                        'button_text' => 'Primary Action',
                        'button_variant' => 'btn-primary',
                        'secondary_button_text' => 'Secondary Action',
                        'secondary_button_variant' => 'btn-outline btn-primary',
                        'background' => 'bg-base-200',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
            // Divider with text about Pro blocks
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'content_block',
                    'values' => [
                        'title' => 'Pro Blocks',
                        'subtitle' => 'Advanced blocks available with TallCMS Pro',
                        'content' => '<p>The following blocks are available with a TallCMS Pro license. They provide advanced functionality for professional websites.</p>',
                        'heading_level' => 'h2',
                        'width' => 'normal',
                        'background' => 'bg-primary',
                        'text_color' => 'text-primary-content',
                    ],
                ],
            ],
            // CTA for Pro
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'Want to See Pro Blocks?',
                        'description' => 'Upgrade to TallCMS Pro to unlock 8 additional blocks including Accordion, Tabs, Video, Code Snippets, and more.',
                        'button_text' => 'Get TallCMS Pro',
                        'button_link_type' => 'custom',
                        'button_url' => '/pricing',
                        'button_variant' => 'btn-primary',
                        'button_size' => 'btn-lg',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
        ]);
    }

    protected function getPricingContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => 'Simple, <span data-color="text-primary">Transparent</span> Pricing',
                        'subheading' => 'Start free, upgrade when you need more. No hidden fees, no surprises.',
                        'height' => 'min-h-[50vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Pricing Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'pricing',
                    'values' => [
                        'plans' => [
                            [
                                'name' => 'Core',
                                'description' => 'Everything you need to get started',
                                'price' => '0',
                                'currency' => '$',
                                'billing_period' => 'forever free',
                                'is_popular' => false,
                                'features' => [
                                    ['text' => '16 Content Blocks', 'included' => true],
                                    ['text' => 'Unlimited Sites', 'included' => true],
                                    ['text' => 'Multi-Theme System', 'included' => true],
                                    ['text' => '30+ DaisyUI Themes', 'included' => true],
                                    ['text' => 'Filament 4 Admin Panel', 'included' => true],
                                    ['text' => 'Role-Based Access Control', 'included' => true],
                                    ['text' => 'Media Library', 'included' => true],
                                    ['text' => 'SEO Settings', 'included' => true],
                                    ['text' => 'Community Support', 'included' => true],
                                    ['text' => 'MIT License', 'included' => true],
                                    ['text' => 'Pro Blocks (8 blocks)', 'included' => false],
                                    ['text' => 'Priority Email Support', 'included' => false],
                                ],
                                'button_text' => 'Download Free',
                                'button_url' => 'https://github.com/tallcms/tallcms',
                            ],
                            [
                                'name' => 'Pro',
                                'description' => 'Advanced blocks and priority support',
                                'price' => '99',
                                'currency' => '$',
                                'billing_period' => '/year',
                                'is_popular' => true,
                                'features' => [
                                    ['text' => 'Everything in Core', 'included' => true],
                                    ['text' => '+8 Pro Blocks', 'included' => true],
                                    ['text' => 'Accordion Block', 'included' => true],
                                    ['text' => 'Tabs Block', 'included' => true],
                                    ['text' => 'Video Block', 'included' => true],
                                    ['text' => 'Table Block', 'included' => true],
                                    ['text' => 'Code Snippet Block', 'included' => true],
                                    ['text' => 'Before/After Block', 'included' => true],
                                    ['text' => 'Comparison Block', 'included' => true],
                                    ['text' => 'Counter Block', 'included' => true],
                                    ['text' => 'Priority Email Support', 'included' => true],
                                    ['text' => '1 Year of Updates', 'included' => true],
                                ],
                                'button_text' => 'Get Pro License',
                                'button_url' => '#',
                            ],
                        ],
                        'columns' => 2,
                        'card_style' => 'shadow',
                    ],
                ],
            ],
            // FAQ Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'faq',
                    'values' => [
                        'heading' => 'Frequently Asked Questions',
                        'items' => [
                            [
                                'question' => 'Is TallCMS Core really free?',
                                'answer' => 'Yes! TallCMS Core is 100% free and open source under the MIT license. You can use it for unlimited personal and commercial projects.',
                            ],
                            [
                                'question' => 'Can I use Core for client projects?',
                                'answer' => 'Absolutely. The MIT license allows you to use TallCMS Core for any purpose, including client projects, without any attribution required.',
                            ],
                            [
                                'question' => 'What\'s included in Pro?',
                                'answer' => 'Pro includes 8 additional blocks (Accordion, Tabs, Video, Table, Code Snippet, Before/After, Comparison, Counter), plus priority email support and one year of updates.',
                            ],
                            [
                                'question' => 'Do I need Pro for every site?',
                                'answer' => 'No. One Pro license covers unlimited sites for you or your organization. Buy once, use everywhere.',
                            ],
                            [
                                'question' => 'What happens after my Pro year expires?',
                                'answer' => 'You keep using the Pro blocks forever. The subscription only covers updates and support. Renew to get new features and continued support.',
                            ],
                            [
                                'question' => 'Is there a refund policy?',
                                'answer' => 'Yes, we offer a 30-day money-back guarantee. If Pro isn\'t right for you, just let us know for a full refund.',
                            ],
                        ],
                        'style' => 'accordion',
                        'allow_multiple' => true,
                        'first_open' => true,
                    ],
                ],
            ],
            // CTA
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'Ready to Get Started?',
                        'description' => 'Download TallCMS Core for free or upgrade to Pro for advanced blocks.',
                        'button_text' => 'Get Started Free',
                        'button_link_type' => 'custom',
                        'button_url' => '/docs',
                        'button_variant' => 'btn-primary',
                        'button_size' => 'btn-lg',
                        'secondary_button_text' => 'Get Pro',
                        'secondary_button_link_type' => 'custom',
                        'secondary_button_url' => '#',
                        'secondary_button_variant' => 'btn-outline btn-primary',
                        'background' => 'bg-base-200',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
        ]);
    }

    protected function getDocsContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => '<span data-color="text-primary">Documentation</span>',
                        'subheading' => 'Everything you need to get started with TallCMS.',
                        'height' => 'min-h-[40vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Quick Start
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'content_block',
                    'values' => [
                        'title' => 'Quick Start',
                        'content' => '<h3>Requirements</h3><ul><li>PHP 8.2 or higher</li><li>Laravel 11 or 12</li><li>Node.js 18+ and NPM</li><li>Composer</li></ul><h3>Installation</h3><p>Install TallCMS via Composer:</p><pre><code>composer require tallcms/tallcms</code></pre><p>Run the installation command:</p><pre><code>php artisan tallcms:install</code></pre><p>Build assets:</p><pre><code>npm install && npm run build</code></pre><p>That\'s it! Visit <code>/admin</code> to access the admin panel.</p>',
                        'heading_level' => 'h2',
                        'width' => 'normal',
                    ],
                ],
            ],
            // Features Grid
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Documentation Sections',
                        'features' => [
                            [
                                'title' => 'Installation',
                                'description' => 'Step-by-step guide to installing TallCMS in your Laravel project.',
                                'icon' => 'arrow-down-tray',
                            ],
                            [
                                'title' => 'Configuration',
                                'description' => 'Configure themes, storage, email, and other settings.',
                                'icon' => 'cog-6-tooth',
                            ],
                            [
                                'title' => 'Creating Pages',
                                'description' => 'Learn how to create and manage pages with the visual editor.',
                                'icon' => 'document-plus',
                            ],
                            [
                                'title' => 'Using Blocks',
                                'description' => 'Master all 24 content blocks and their options.',
                                'icon' => 'squares-2x2',
                            ],
                            [
                                'title' => 'Theme Development',
                                'description' => 'Create custom themes with Blade and Tailwind CSS.',
                                'icon' => 'paint-brush',
                            ],
                            [
                                'title' => 'Plugin Development',
                                'description' => 'Extend TallCMS with custom plugins and blocks.',
                                'icon' => 'puzzle-piece',
                            ],
                        ],
                        'columns' => 3,
                        'card_style' => 'bordered',
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

    protected function getAboutContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => 'Built for <span data-color="text-primary">Modern</span> Laravel Development',
                        'subheading' => 'TallCMS is a content management system built on the best tools in the Laravel ecosystem.',
                        'height' => 'min-h-[50vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Story
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'content_block',
                    'values' => [
                        'title' => 'The TallCMS Story',
                        'content' => '<p>TallCMS was born from a simple idea: Laravel developers deserve a CMS that feels like Laravel.</p><p>We were tired of fighting with bloated CMS platforms that didn\'t integrate well with our Laravel projects. We wanted something that embraced the TALL stack, used Filament for the admin panel, and let us customize everything without fighting the framework.</p><p>So we built TallCMS. A modern, block-based CMS that\'s 100% Laravel, 100% open source, and 100% developer-friendly.</p>',
                        'heading_level' => 'h2',
                        'width' => 'narrow',
                    ],
                ],
            ],
            // Tech Stack
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'The Tech Stack',
                        'subheading' => 'Built on battle-tested, modern technologies',
                        'features' => [
                            [
                                'title' => 'Laravel 12',
                                'description' => 'The PHP framework for web artisans. Clean, elegant, and powerful.',
                                'icon' => 'server',
                            ],
                            [
                                'title' => 'Livewire 3',
                                'description' => 'Full-stack framework for Laravel. Build dynamic interfaces without leaving PHP.',
                                'icon' => 'bolt',
                            ],
                            [
                                'title' => 'Alpine.js',
                                'description' => 'Lightweight JavaScript framework. Perfect for adding interactivity.',
                                'icon' => 'sparkles',
                            ],
                            [
                                'title' => 'Tailwind CSS 4',
                                'description' => 'Utility-first CSS framework. Build any design without leaving your HTML.',
                                'icon' => 'paint-brush',
                            ],
                            [
                                'title' => 'Filament 4',
                                'description' => 'The most beautiful admin panel for Laravel. Forms, tables, and more.',
                                'icon' => 'squares-2x2',
                            ],
                            [
                                'title' => 'DaisyUI 5',
                                'description' => 'Component library for Tailwind. 30+ themes, semantic classes.',
                                'icon' => 'swatch',
                            ],
                        ],
                        'columns' => 3,
                        'card_style' => 'shadow',
                    ],
                ],
            ],
            // Timeline
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'timeline',
                    'values' => [
                        'heading' => 'Our Journey',
                        'items' => [
                            [
                                'title' => 'The Idea',
                                'description' => 'Frustrated with existing CMS options, we started sketching out what a Laravel-native CMS could look like.',
                                'date' => '2024',
                                'icon' => 'light-bulb',
                            ],
                            [
                                'title' => 'First Release',
                                'description' => 'TallCMS 1.0 launched with 16 core blocks and full Filament integration.',
                                'date' => 'Jan 2025',
                                'icon' => 'rocket-launch',
                            ],
                            [
                                'title' => 'DaisyUI Integration',
                                'description' => 'Version 1.1 brought full DaisyUI support with 30+ themes and semantic styling.',
                                'date' => 'Jan 2025',
                                'icon' => 'swatch',
                            ],
                            [
                                'title' => 'The Future',
                                'description' => 'More blocks, more themes, more integrations. The journey continues.',
                                'date' => 'Beyond',
                                'icon' => 'arrow-trending-up',
                            ],
                        ],
                        'layout' => 'vertical',
                        'alternating' => true,
                    ],
                ],
            ],
            // CTA
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'call_to_action',
                    'values' => [
                        'title' => 'Join the Community',
                        'description' => 'TallCMS is open source and community-driven. Contribute on GitHub or join our Discord.',
                        'button_text' => 'Star on GitHub',
                        'button_link_type' => 'external',
                        'button_url' => 'https://github.com/tallcms/tallcms',
                        'button_variant' => 'btn-primary',
                        'button_size' => 'btn-lg',
                        'secondary_button_text' => 'Join Discord',
                        'secondary_button_link_type' => 'external',
                        'secondary_button_url' => 'https://discord.gg/tallcms',
                        'secondary_button_variant' => 'btn-outline btn-primary',
                        'background' => 'bg-base-200',
                        'text_alignment' => 'text-center',
                    ],
                ],
            ],
        ]);
    }

    protected function getContactContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => 'Get in <span data-color="text-primary">Touch</span>',
                        'subheading' => 'Have a question? We\'d love to hear from you.',
                        'height' => 'min-h-[40vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Contact Options
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'features',
                    'values' => [
                        'heading' => 'Ways to Reach Us',
                        'features' => [
                            [
                                'title' => 'Email',
                                'description' => 'For general inquiries and support questions.',
                                'icon' => 'envelope',
                            ],
                            [
                                'title' => 'GitHub',
                                'description' => 'Report bugs or request features on our repository.',
                                'icon' => 'code-bracket',
                                'link' => 'https://github.com/tallcms/tallcms/issues',
                            ],
                            [
                                'title' => 'Discord',
                                'description' => 'Join our community for real-time chat and support.',
                                'icon' => 'chat-bubble-left-right',
                                'link' => 'https://discord.gg/tallcms',
                            ],
                        ],
                        'columns' => 3,
                        'card_style' => 'bordered',
                    ],
                ],
            ],
            // Contact Form
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'contact_form',
                    'values' => [
                        'title' => 'Send us a Message',
                        'description' => 'Fill out the form below and we\'ll get back to you as soon as possible.',
                        'fields' => [
                            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'placeholder' => 'Your name'],
                            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'placeholder' => 'your@email.com'],
                            ['name' => 'subject', 'type' => 'dropdown', 'label' => 'Subject', 'required' => true, 'options' => "General Inquiry\nTechnical Support\nPro License\nPartnership\nOther"],
                            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'placeholder' => 'How can we help you?'],
                        ],
                        'button_text' => 'Send Message',
                        'button_variant' => 'btn-primary',
                        'success_message' => 'Thanks for reaching out! We\'ll get back to you within 24-48 hours.',
                    ],
                ],
            ],
        ]);
    }

    protected function getBlogContent(): array
    {
        return $this->wrapContent([
            // Hero
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'hero',
                    'values' => [
                        'heading' => '<span data-color="text-primary">Blog</span>',
                        'subheading' => 'News, tutorials, and updates from the TallCMS team.',
                        'height' => 'min-h-[40vh]',
                        'text_alignment' => 'text-center',
                        'overlay_opacity' => 60,
                    ],
                ],
            ],
            // Posts Block
            [
                'type' => 'customBlock',
                'data' => [
                    'type' => 'posts',
                    'values' => [
                        'layout' => 'grid',
                        'columns' => 3,
                        'count' => 9,
                        'show_image' => true,
                        'show_excerpt' => true,
                        'show_date' => true,
                        'show_author' => true,
                        'show_categories' => true,
                        'show_read_more' => true,
                        'empty_message' => 'No blog posts yet. Check back soon!',
                    ],
                ],
            ],
        ]);
    }

    protected function createBlogPosts(): void
    {
        // Introductory blog post
        CmsPost::updateOrCreate(
            ['slug' => 'introducing-tallcms-1-1-daisyui-integration'],
            [
                'title' => 'Introducing TallCMS 1.1: DaisyUI Integration',
                'excerpt' => 'We\'re excited to announce TallCMS 1.1, featuring full DaisyUI integration with 30+ themes and semantic styling across all blocks.',
                'content' => $this->wrapContent([
                    [
                        'type' => 'customBlock',
                        'data' => [
                            'type' => 'content_block',
                            'values' => [
                                'content' => '<p>We\'re thrilled to announce TallCMS 1.1, our biggest update yet! This release brings full DaisyUI 5 integration, giving you access to 30+ beautiful themes and semantic styling across all blocks.</p><h3>What\'s New</h3><ul><li><strong>30+ DaisyUI Themes</strong> - Switch between light, dark, and colorful themes instantly</li><li><strong>Semantic Styling</strong> - All blocks now use DaisyUI\'s semantic classes for consistent theming</li><li><strong>Theme Switcher</strong> - Let your visitors choose their preferred theme</li><li><strong>Improved Blocks</strong> - Before/After block now uses the native DaisyUI diff component</li></ul><h3>Upgrading</h3><p>Upgrading to 1.1 is simple. Just update via Composer and rebuild your assets:</p><pre><code>composer update tallcms/tallcms\nnpm run build</code></pre><p>That\'s it! Your existing content will automatically use the new DaisyUI styling.</p><h3>What\'s Next</h3><p>We\'re already working on TallCMS 1.2, which will bring even more blocks and theme customization options. Stay tuned!</p>',
                                'heading_level' => 'h2',
                                'width' => 'normal',
                            ],
                        ],
                    ],
                ]),
                'meta_title' => 'Introducing TallCMS 1.1: DaisyUI Integration',
                'meta_description' => 'TallCMS 1.1 brings full DaisyUI integration with 30+ themes and semantic styling.',
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
                'is_featured' => true,
                'author_id' => $this->author->id,
            ]
        );

        // Getting Started post
        CmsPost::updateOrCreate(
            ['slug' => 'getting-started-with-tallcms'],
            [
                'title' => 'Getting Started with TallCMS in 5 Minutes',
                'excerpt' => 'Learn how to install TallCMS and create your first page in just 5 minutes.',
                'content' => $this->wrapContent([
                    [
                        'type' => 'customBlock',
                        'data' => [
                            'type' => 'content_block',
                            'values' => [
                                'content' => '<p>Ready to build your first TallCMS site? Let\'s get you up and running in just 5 minutes.</p><h3>Prerequisites</h3><ul><li>PHP 8.2 or higher</li><li>Laravel 11 or 12</li><li>Node.js 18+ and NPM</li><li>Composer</li></ul><h3>Step 1: Install TallCMS</h3><pre><code>composer require tallcms/tallcms</code></pre><h3>Step 2: Run the Installer</h3><pre><code>php artisan tallcms:install</code></pre><h3>Step 3: Build Assets</h3><pre><code>npm install && npm run build</code></pre><h3>Step 4: Create Your First Page</h3><p>Visit <code>/admin</code> to access the admin panel. From there, you can create pages, add blocks, and customize your site.</p><h3>That\'s It!</h3><p>You now have a fully functional CMS. Explore the block library and start building!</p>',
                                'heading_level' => 'h2',
                                'width' => 'normal',
                            ],
                        ],
                    ],
                ]),
                'meta_title' => 'Getting Started with TallCMS in 5 Minutes',
                'meta_description' => 'A quick-start guide to installing TallCMS and creating your first page.',
                'status' => ContentStatus::Published->value,
                'published_at' => now()->subDays(2),
                'author_id' => $this->author->id,
            ]
        );

        // Why TALL Stack post
        CmsPost::updateOrCreate(
            ['slug' => 'why-we-chose-the-tall-stack'],
            [
                'title' => 'Why We Chose the TALL Stack for TallCMS',
                'excerpt' => 'The story behind our decision to build TallCMS on Tailwind, Alpine.js, Laravel, and Livewire.',
                'content' => $this->wrapContent([
                    [
                        'type' => 'customBlock',
                        'data' => [
                            'type' => 'content_block',
                            'values' => [
                                'content' => '<p>When we set out to build TallCMS, we had one goal: create a CMS that Laravel developers would love. The TALL stack was the obvious choice.</p><h3>Why Laravel?</h3><p>Laravel is the most popular PHP framework for good reason. It\'s elegant, well-documented, and has an incredible ecosystem. Building on Laravel means TallCMS feels familiar to millions of developers.</p><h3>Why Livewire?</h3><p>Livewire lets us build dynamic interfaces without writing JavaScript. The admin panel, the page builder, the media library - all powered by Livewire. It\'s PHP all the way down.</p><h3>Why Alpine.js?</h3><p>For the small bits of interactivity that need client-side JavaScript, Alpine.js is perfect. It\'s lightweight, declarative, and plays nicely with Livewire.</p><h3>Why Tailwind CSS?</h3><p>Tailwind gives us the flexibility to style anything without fighting CSS. Combined with DaisyUI, we get beautiful, consistent components with minimal effort.</p><h3>The Result</h3><p>The TALL stack lets us build a CMS that\'s fast, flexible, and familiar. If you know Laravel, you know TallCMS.</p>',
                                'heading_level' => 'h2',
                                'width' => 'normal',
                            ],
                        ],
                    ],
                ]),
                'meta_title' => 'Why We Chose the TALL Stack for TallCMS',
                'meta_description' => 'Learn why TallCMS is built on Tailwind, Alpine.js, Laravel, and Livewire.',
                'status' => ContentStatus::Published->value,
                'published_at' => now()->subDays(5),
                'author_id' => $this->author->id,
            ]
        );

        $this->command->info('Created 3 blog posts');
    }

    protected function createMenus(): void
    {
        // Header Menu
        $headerMenu = TallcmsMenu::updateOrCreate(
            ['location' => 'header'],
            [
                'name' => 'Header Menu',
                'description' => 'Main navigation menu',
                'is_active' => true,
            ]
        );

        // Clear existing items
        $headerMenu->allItems()->delete();

        // Get page IDs
        $pages = CmsPage::whereIn('slug', ['features', 'blocks', 'pricing', 'docs', 'blog'])
            ->pluck('id', 'slug');

        $headerItems = [
            ['label' => 'Features', 'type' => 'page', 'page_id' => $pages['features'] ?? null],
            ['label' => 'Blocks', 'type' => 'page', 'page_id' => $pages['blocks'] ?? null],
            ['label' => 'Pricing', 'type' => 'page', 'page_id' => $pages['pricing'] ?? null],
            ['label' => 'Docs', 'type' => 'page', 'page_id' => $pages['docs'] ?? null],
            ['label' => 'Blog', 'type' => 'page', 'page_id' => $pages['blog'] ?? null],
        ];

        $lft = 1;
        foreach ($headerItems as $item) {
            TallcmsMenuItem::create(array_merge($item, [
                'menu_id' => $headerMenu->id,
                'is_active' => true,
                '_lft' => $lft,
                '_rgt' => $lft + 1,
            ]));
            $lft += 2;
        }

        // Footer Menu
        $footerMenu = TallcmsMenu::updateOrCreate(
            ['location' => 'footer'],
            [
                'name' => 'Footer Menu',
                'description' => 'Footer navigation links',
                'is_active' => true,
            ]
        );

        $footerMenu->allItems()->delete();

        $footerPages = CmsPage::whereIn('slug', ['about', 'contact'])
            ->pluck('id', 'slug');

        $footerItems = [
            ['label' => 'About', 'type' => 'page', 'page_id' => $footerPages['about'] ?? null],
            ['label' => 'Contact', 'type' => 'page', 'page_id' => $footerPages['contact'] ?? null],
            ['label' => 'GitHub', 'type' => 'external', 'url' => 'https://github.com/tallcms/tallcms'],
            ['label' => 'Discord', 'type' => 'external', 'url' => 'https://discord.gg/tallcms'],
        ];

        $lft = 1;
        foreach ($footerItems as $item) {
            TallcmsMenuItem::create(array_merge($item, [
                'menu_id' => $footerMenu->id,
                'is_active' => true,
                '_lft' => $lft,
                '_rgt' => $lft + 1,
            ]));
            $lft += 2;
        }

        $this->command->info('Created header and footer menus');
    }
}
