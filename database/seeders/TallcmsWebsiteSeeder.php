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
                'meta_description' => 'Build beautiful, content-rich websites with the TALL stack, Filament 5, and 30+ DaisyUI themes. Free and open source.',
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
     * Build a Tiptap document from an array of block definitions.
     *
     * Each block can be:
     * - A heading: ['heading' => 'Text', 'level' => 2]
     * - A custom block: ['block' => 'hero', 'config' => [...]]
     */
    protected function buildContent(array $blocks): array
    {
        $content = [];

        foreach ($blocks as $block) {
            if (isset($block['heading'])) {
                $content[] = [
                    'type' => 'heading',
                    'attrs' => ['level' => $block['level'] ?? 2],
                    'content' => [
                        ['type' => 'text', 'text' => $block['heading']],
                    ],
                ];
            } elseif (isset($block['block'])) {
                $content[] = [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => $block['block'],
                        'config' => $block['config'] ?? [],
                    ],
                    'content' => [],
                ];
            }
        }

        return ['type' => 'doc', 'content' => $content];
    }

    /**
     * Common block config fields with sensible defaults.
     */
    protected function blockDefaults(array $overrides = []): array
    {
        return array_merge([
            'content_width' => 'inherit',
            'background' => 'bg-base-100',
            'padding' => 'py-16',
            'first_section' => false,
            'animation_type' => 'fade-in-up',
            'animation_duration' => 'anim-duration-700',
            'anchor_id' => null,
            'css_classes' => null,
        ], $overrides);
    }

    protected function getDocumentationContent(): array
    {
        $category = CmsCategory::withSlug('getting-started')->first();
        $categoryIds = $category ? [$category->id] : [];

        return $this->buildContent([
            ['heading' => 'Getting Started'],
            [
                'block' => 'posts',
                'config' => array_merge($this->blockDefaults(), [
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
                    'show_comments' => true,
                    'empty_message' => null,
                    'layout' => 'grid',
                    'columns' => 3,
                    'enable_pagination' => false,
                    'show_featured_badge' => false,
                    'featured_card_style' => 'default',
                    'animation_type' => null,
                ]),
            ],
        ]);
    }

    protected function getHomepageContent(): array
    {
        return $this->buildContent([
            // Hero — light bg activates Elevate's asymmetric layout + gradient text
            [
                'block' => 'hero',
                'config' => [
                    'heading' => 'Ship faster with <span data-color="text-primary">TallCMS</span>',
                    'subheading' => 'The open-source CMS that feels native to Laravel. Content blocks, themes, and a Filament admin — out of the box.',
                    'layout' => 'centered',
                    'height' => 'min-h-[90vh]',
                    'text_alignment' => 'text-left',
                    'background_color' => 'bg-base-100',
                    'parallax_effect' => false,
                    'button_text' => 'Start Building',
                    'button_link_type' => 'custom',
                    'button_url' => '/documentation',
                    'button_variant' => 'btn-primary',
                    'button_size' => 'btn-lg',
                    'secondary_button_text' => 'View on GitHub',
                    'secondary_button_link_type' => 'external',
                    'secondary_button_url' => 'https://github.com/tallcms/tallcms',
                    'secondary_button_variant' => 'btn-ghost',
                    'animation_type' => 'fade-in-up',
                    'animation_duration' => 'anim-duration-700',
                    'anchor_id' => null,
                    'css_classes' => 'light-canvas light-canvas-top-right',
                ],
            ],

            // Logos
            [
                'block' => 'logos',
                'config' => array_merge($this->blockDefaults(), [
                    'heading' => 'Powered by the TALL Stack',
                    'source' => 'manual',
                    'logos' => [
                        ['alt' => 'Laravel', 'url' => 'https://laravel.com'],
                        ['alt' => 'Livewire', 'url' => 'https://livewire.laravel.com'],
                        ['alt' => 'Alpine.js', 'url' => 'https://alpinejs.dev'],
                        ['alt' => 'Tailwind CSS', 'url' => 'https://tailwindcss.com'],
                        ['alt' => 'Filament', 'url' => 'https://filamentphp.com'],
                        ['alt' => 'DaisyUI', 'url' => 'https://daisyui.com'],
                    ],
                    'layout' => 'inline',
                    'grayscale' => true,
                    'hover_color' => true,
                    'columns' => '6',
                    'size' => 'medium',
                    'animation_type' => 'fade-in',
                ]),
            ],

            // Features — 6 items triggers mosaic, Light Canvas bottom-left
            [
                'block' => 'features',
                'config' => array_merge($this->blockDefaults(['content_width' => 'wide', 'css_classes' => 'light-canvas light-canvas-bottom-left']), [
                    'heading' => 'Everything you need to ship',
                    'subheading' => 'From content blocks to production deployment — TallCMS handles it all.',
                    'features' => [
                        ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-squares-2x2', 'title' => 'Drop-in Content Blocks', 'description' => 'Hero sections, pricing tables, testimonials — 24 blocks that just work. No code required.'],
                        ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-swatch', 'title' => 'One-Click Themes', 'description' => 'Switch between 35 daisyUI presets instantly. Or build your own with full Tailwind control.'],
                        ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-rectangle-group', 'title' => 'Filament Admin', 'description' => 'The best Laravel admin panel, built in. Pages, posts, media, menus — all managed beautifully.'],
                        ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-puzzle-piece', 'title' => 'Plugin Ready', 'description' => 'Extend with plugins. Mega menus, analytics, pro blocks — install from the admin panel.'],
                        ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-magnifying-glass', 'title' => 'Built for SEO', 'description' => 'Meta tags, sitemaps, structured data, Open Graph — search engines love TallCMS sites.'],
                        ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-rocket-launch', 'title' => 'Ship to Production', 'description' => 'One-click updates, maintenance mode, role-based access. Production-ready from day one.'],
                    ],
                    'columns' => '3',
                    'card_style' => 'card shadow-xl bg-base-100',
                    'icon_position' => 'top',
                    'icon_size' => 'w-10 h-10',
                    'text_alignment' => 'text-center',
                ]),
            ],

            // Pricing — Light Canvas center
            [
                'block' => 'pricing',
                'config' => array_merge($this->blockDefaults(['content_width' => 'wide', 'background' => 'bg-base-200', 'css_classes' => 'light-canvas light-canvas-center']), [
                    'section_title' => 'Simple, Transparent Pricing',
                    'section_subtitle' => 'Start free. Scale when you are ready.',
                    'text_alignment' => 'text-center',
                    'plans' => [
                        [
                            'name' => 'Starter',
                            'description' => 'Everything you need to launch.',
                            'is_popular' => false,
                            'currency_symbol' => '$',
                            'price' => '0',
                            'billing_period' => 'free',
                            'features' => [
                                ['text' => 'All 24 content blocks', 'included' => true],
                                ['text' => '8 bundled themes', 'included' => true],
                                ['text' => 'Full-text search', 'included' => true],
                                ['text' => 'SEO tools & sitemaps', 'included' => true],
                                ['text' => 'Pro blocks & analytics', 'included' => false],
                                ['text' => 'Priority support', 'included' => false],
                            ],
                            'button_text' => 'Get Started',
                            'button_url' => '/documentation',
                            'button_style' => 'btn-outline btn-primary',
                        ],
                        [
                            'name' => 'Pro',
                            'description' => 'For teams that move fast.',
                            'is_popular' => true,
                            'popular_badge_text' => 'Most Popular',
                            'currency_symbol' => '$',
                            'price' => '29',
                            'billing_period' => 'month',
                            'trial_text' => '14-day free trial · No credit card',
                            'features' => [
                                ['text' => 'Everything in Starter', 'included' => true],
                                ['text' => '11 premium Pro blocks', 'included' => true],
                                ['text' => 'Google Analytics dashboard', 'included' => true],
                                ['text' => 'Advanced animations', 'included' => true],
                                ['text' => 'Mega Menu plugin', 'included' => true],
                                ['text' => 'Email support', 'included' => true],
                            ],
                            'button_text' => 'Start Free Trial',
                            'button_url' => '#',
                            'button_style' => 'btn-primary',
                        ],
                        [
                            'name' => 'Enterprise',
                            'description' => 'White-glove support for scale.',
                            'is_popular' => false,
                            'currency_symbol' => '$',
                            'price' => '99',
                            'billing_period' => 'month',
                            'trial_text' => 'Custom demo available',
                            'features' => [
                                ['text' => 'Everything in Pro', 'included' => true],
                                ['text' => 'Custom theme development', 'included' => true],
                                ['text' => 'Multi-site license', 'included' => true],
                                ['text' => 'AI content tools', 'included' => true],
                                ['text' => 'SLA guarantee', 'included' => true],
                                ['text' => 'Dedicated support', 'included' => true],
                            ],
                            'button_text' => 'Contact Sales',
                            'button_url' => '#',
                            'button_style' => 'btn-outline btn-primary',
                        ],
                    ],
                    'columns' => '3',
                    'card_style' => 'shadow',
                    'spacing' => 'normal',
                ]),
            ],

            // Testimonials
            [
                'block' => 'testimonials',
                'config' => array_merge($this->blockDefaults(['content_width' => 'wide']), [
                    'heading' => 'Loved by Developers',
                    'subheading' => 'Hear from teams who shipped with TallCMS.',
                    'testimonials' => [
                        [
                            'quote' => 'We migrated from WordPress in a weekend. The block system is exactly what we needed.',
                            'author_name' => 'Sarah Chen',
                            'author_title' => 'CTO at LaunchPad',
                            'rating' => '5',
                        ],
                        [
                            'quote' => "Finally a CMS that doesn't fight Laravel. I actually enjoy building content sites now.",
                            'author_name' => 'Marcus Rivera',
                            'author_title' => 'Senior Developer at Pixel & Code',
                            'rating' => '5',
                        ],
                        [
                            'quote' => 'My clients can switch themes themselves. That alone justified the move to TallCMS.',
                            'author_name' => 'Aisha Patel',
                            'author_title' => 'Freelance Web Developer',
                            'rating' => '5',
                        ],
                    ],
                    'layout' => 'grid',
                    'columns' => '3',
                    'card_style' => 'card bg-base-200 shadow-lg',
                    'text_alignment' => 'text-left',
                    'show_rating' => true,
                    'show_company_logo' => false,
                ]),
            ],

            // Stats
            [
                'block' => 'stats',
                'config' => array_merge($this->blockDefaults(['content_width' => 'wide', 'background' => 'bg-base-200']), [
                    'heading' => 'By the Numbers',
                    'stats' => [
                        ['value' => '24', 'label' => 'Content Blocks', 'icon' => 'heroicon-o-squares-2x2'],
                        ['value' => '35', 'label' => 'Theme Presets', 'icon' => 'heroicon-o-swatch'],
                        ['value' => '8', 'label' => 'Shipped Themes', 'icon' => 'heroicon-o-paint-brush'],
                        ['value' => '100', 'suffix' => '%', 'label' => 'Open Source', 'icon' => 'heroicon-o-code-bracket'],
                    ],
                    'columns' => '4',
                    'stat_style' => 'stat bg-base-100 rounded-xl shadow-lg',
                    'text_alignment' => 'text-center',
                    'animate' => true,
                ]),
            ],

            // FAQ
            [
                'block' => 'faq',
                'config' => array_merge($this->blockDefaults(['content_width' => 'standard']), [
                    'heading' => 'Frequently Asked Questions',
                    'subheading' => 'Quick answers to common questions.',
                    'items' => [
                        ['question' => 'Is TallCMS free?', 'answer' => 'Yes. TallCMS core is free and open source under the MIT license. Premium plugins are optional.'],
                        ['question' => 'What does it require?', 'answer' => 'PHP 8.2+, Laravel 12, Filament 5, and Tailwind CSS 4 with daisyUI 5.'],
                        ['question' => 'Can I add it to my existing app?', 'answer' => 'Yes — run composer require tallcms/cms and register the Filament plugin. No new project needed.'],
                        ['question' => 'How do themes work?', 'answer' => 'Self-contained directories with views, CSS, and JS. Use any daisyUI preset or define custom colors. Switch instantly from the admin.'],
                        ['question' => 'Can I create custom blocks?', 'answer' => 'Run php artisan make:tallcms-block to scaffold one. Blocks get a Filament form and Blade view, and appear in the editor automatically.'],
                    ],
                    'style' => 'accordion',
                    'first_open' => true,
                    'allow_multiple' => false,
                    'text_alignment' => 'text-center',
                    'show_schema' => true,
                ]),
            ],

            // CTA — gradient bg activates Elevate's boxed CTA treatment
            [
                'block' => 'call_to_action',
                'config' => array_merge($this->blockDefaults(), [
                    'title' => 'Ready to ship?',
                    'description' => 'TallCMS is free, open source, and built for Laravel developers who value speed and quality.',
                    'text_alignment' => 'text-center',
                    'background' => 'bg-gradient-to-br from-primary to-secondary',
                    'button_text' => 'Get Started',
                    'button_link_type' => 'custom',
                    'button_url' => '/documentation',
                    'button_variant' => 'btn-primary',
                    'button_size' => 'btn-lg',
                    'secondary_button_text' => 'GitHub',
                    'secondary_button_link_type' => 'external',
                    'secondary_button_url' => 'https://github.com/tallcms/tallcms',
                    'secondary_button_variant' => 'btn-ghost',
                ]),
            ],
        ]);
    }
}
