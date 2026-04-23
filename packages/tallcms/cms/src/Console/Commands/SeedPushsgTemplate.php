<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed the PUSH.SG landing page — the meta template.
 *
 * This is the marketing site for PUSH.SG itself: a Singapore landing-page
 * builder for property agents, operating since 2017. The template
 * showcases the platform's own capabilities by BEING built on the
 * platform. Agents visiting the site see what their own can look like.
 *
 * The copy is PUSH.SG-specific (Singapore, property agents, since 2017)
 * rather than generic SaaS fill-in-the-blank. Anyone else who clones it
 * would rewrite — that's fine; the structure still transfers.
 */
class SeedPushsgTemplate extends Command
{
    protected $signature = 'tallcms:seed-pushsg-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing PUSH.SG template and recreate}';

    protected $description = 'Seed the PUSH.SG landing page template — the SaaS marketing site for the platform itself';

    public function handle(): int
    {
        if (! Schema::hasTable('tallcms_sites') || ! Schema::hasColumn('tallcms_sites', 'is_template_source')) {
            $this->error('Multisite plugin with is_template_source column required.');

            return self::FAILURE;
        }

        $ownerId = $this->resolveOwnerId();
        if (! $ownerId) {
            $this->error('No owner user found.');

            return self::FAILURE;
        }

        $existing = DB::table('tallcms_sites')->where('domain', 'pushsg.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn('PUSH.SG template already exists (site id '.$existing->id.'). Use --force to recreate.');

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info('Removed existing PUSH.SG template.');
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info("Created site: PUSH.SG (id {$siteId})");

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info('Created '.count($pageIds).' pages.');

        $this->createMenu($siteId, $pageIds);
        $this->components->info('Created primary menu.');

        $this->newLine();
        $this->components->info('🚀 PUSH.SG template ready. Since 2017.');

        return self::SUCCESS;
    }

    protected function resolveOwnerId(): ?int
    {
        if ($owner = $this->option('owner')) {
            return (int) $owner;
        }

        $superAdminId = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'super_admin')
            ->value('model_has_roles.model_id');

        return $superAdminId ? (int) $superAdminId : DB::table('users')->orderBy('id')->value('id');
    }

    protected function deleteSite(int $siteId): void
    {
        DB::table('tallcms_menu_items')
            ->whereIn('menu_id', DB::table('tallcms_menus')->where('site_id', $siteId)->pluck('id'))
            ->delete();
        DB::table('tallcms_menus')->where('site_id', $siteId)->delete();
        DB::table('tallcms_posts')->where('site_id', $siteId)->delete();
        DB::table('tallcms_pages')->where('site_id', $siteId)->delete();
        DB::table('tallcms_site_setting_overrides')->where('site_id', $siteId)->delete();
        DB::table('tallcms_sites')->where('id', $siteId)->delete();
    }

    protected function createSite(int $ownerId): int
    {
        return (int) DB::table('tallcms_sites')->insertGetId([
            'name' => 'PUSH.SG',
            'domain' => 'pushsg.template',
            'uuid' => (string) Str::uuid(),
            'user_id' => $ownerId,
            'is_default' => false,
            'is_active' => true,
            'is_template_source' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createPages(int $siteId, int $ownerId): array
    {
        $pages = [
            'home' => ['title' => 'Home', 'is_homepage' => true, 'content' => $this->homeContent()],
            'pricing' => ['title' => 'Pricing', 'content' => $this->pricingContent()],
            'contact' => ['title' => 'Contact', 'content' => $this->contactContent()],
        ];

        $ids = [];
        foreach ($pages as $slug => $page) {
            $ids[$slug] = (int) DB::table('tallcms_pages')->insertGetId([
                'site_id' => $siteId,
                'author_id' => $ownerId,
                'title' => json_encode(['en' => $page['title']]),
                'slug' => json_encode(['en' => $slug]),
                'content' => json_encode(['en' => $page['content']]),
                'status' => 'published',
                'is_homepage' => $page['is_homepage'] ?? false,
                'sort_order' => 0,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    protected function createMenu(int $siteId, array $pageIds): void
    {
        $menuId = (int) DB::table('tallcms_menus')->insertGetId([
            'site_id' => $siteId,
            'name' => 'Primary',
            'location' => 'header',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lft = 1;
        foreach ([
            'home' => 'Home',
            'pricing' => 'Pricing',
            'contact' => 'Contact',
        ] as $slug => $label) {
            DB::table('tallcms_menu_items')->insert([
                'menu_id' => $menuId,
                'type' => 'page',
                'label' => json_encode(['en' => $label]),
                'page_id' => $pageIds[$slug],
                'is_active' => true,
                '_lft' => $lft,
                '_rgt' => $lft + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $lft += 2;
        }
    }

    protected function block(string $id, array $config): string
    {
        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return "<div data-type=\"customBlock\" data-config=\"{$encoded}\" data-id=\"{$id}\"></div>";
    }

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Professional websites and landing pages, in 10 minutes.</p>',
                'subheading' => "<p>Singapore's landing-page builder for professionals. Real estate agents, law firms, writers, consultants — pick a template, swap in your details, go live. No designers, no WordPress nightmares, no \$5,000 agency quotes. Built in Singapore since 2017.</p>",
                'button_text' => 'Start building',
                'button_link_type' => 'custom',
                'button_url' => '/admin',
                'secondary_button_text' => 'See pricing',
                'secondary_button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[80vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-primary to-secondary',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->block('stats', [
                'heading' => 'Singapore-built. Since 2017.',
                'stats' => [
                    ['value' => '8+', 'label' => 'Years in business'],
                    ['value' => '500+', 'label' => 'Sites launched'],
                    ['value' => '5', 'label' => 'Verticals supported'],
                    ['value' => '10 min', 'label' => 'From signup to live'],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('content_block', [
                'title' => 'From real estate to any profession',
                'body' => "<p>PUSH.SG started in 2017 as a landing-page builder for Singapore property agents — because we were agents ourselves, and we were tired of agency quotes and WordPress plugins that never quite fit. Over the years, we got good at one thing: making it fast to launch a professional website that actually looks like someone cared.</p><p>Today, that same engine powers sites for law firms, writers, consultants, property teams, and everyone in between. The templates differ per profession. The principle is the same: pick the template that matches what you're launching, swap in your details, go live.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'What you get',
                'subheading' => 'A complete toolkit for professionals who want to look credible online without hiring a tech team.',
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-squares-2x2',
                        'title' => 'Drag-and-drop editor',
                        'description' => 'Block-based page editor with live preview. Hero, stats, features, testimonials, FAQ, contact form, map — configurable without touching code.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-paint-brush',
                        'title' => 'Profession-specific templates',
                        'description' => 'Start from a polished template tuned for your vertical — real estate, law, blog, new launch, and more coming. Clone, customize, publish.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office-2',
                        'title' => 'Multiple sites, one account',
                        'description' => 'Run a personal brand plus a separate landing page for every project, practice area, or publication. Each site has its own contact email, phone, and branding.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-envelope',
                        'title' => 'Lead capture that works',
                        'description' => 'Contact forms with per-form custom auto-replies. Submissions routed to the right inbox — not a generic catch-all.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-device-phone-mobile',
                        'title' => 'WhatsApp-first CTAs',
                        'description' => 'Built-in WhatsApp integration with pre-filled messages. Because Singapore customers text, they don\'t email.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-shield-check',
                        'title' => 'Compliance-aware defaults',
                        'description' => 'CEA disclaimers for property, attorney-client-relationship notices for law, data-protection language throughout. Industry-appropriate language baked into each template.',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'Ready-made templates by profession',
                'subheading' => 'Pick the one that fits. Swap in your name, photo, and details. Live the same day.',
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home',
                        'title' => 'Real estate agent',
                        'description' => 'Personal brand site with bio, services, testimonials, insights blog, and contact form. The template we started with.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office',
                        'title' => 'New launch landing page',
                        'description' => 'Hero with lead form, project details, unit mix, amenities, embedded map, register-interest form with buyer-status field. CEA disclaimer included.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-scale',
                        'title' => 'Law firm / attorney',
                        'description' => 'Conservative, trust-building site for solo practitioners and boutique firms. Practice areas, attorney bios, compliance-aware forms.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-pencil-square',
                        'title' => 'Blog / newsletter',
                        'description' => 'Content-first site for writers, essayists, and thought-leadership creators. Newsletter CTA throughout, archive page, and a clean reading experience.',
                    ],
                ],
                'columns' => '2',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('testimonials', [
                'heading' => 'What customers say',
                'testimonials' => [
                    [
                        'quote' => "I was paying an agency S\$3,000 per landing page per new launch. PUSH.SG gave me the same thing in an hour for a fraction of the cost. I've spun up 12 landing pages in the past year.",
                        'author_name' => '[Customer Name]',
                        'author_title' => 'Top producer, [Real Estate Agency]',
                    ],
                    [
                        'quote' => "Our firm needed a website that looked professional without reading like marketing fluff. The law-firm template hit the tone exactly — factual, conservative, compliance-aware. Up in a weekend.",
                        'author_name' => '[Customer Name]',
                        'author_title' => 'Managing Partner, [Law Firm]',
                    ],
                    [
                        'quote' => "I've been writing online for years and could never find a template that didn't scream blogger-in-2012. Picked the Ink template, swapped in my bio, done. Readers actually finish posts now.",
                        'author_name' => '[Customer Name]',
                        'author_title' => 'Writer and consultant',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('faq', [
                'heading' => 'Frequently asked',
                'items' => [
                    ['question' => 'Do I need to know any code?', 'answer' => 'No. The editor is drag-and-drop; the templates are done. You type your name, phone, license number — and you are live.'],
                    ['question' => 'Can I use my own domain?', 'answer' => 'Yes. Every paid plan includes custom domains. Verify once, we handle TLS automatically.'],
                    ['question' => 'What about my existing push.sg sites?', 'answer' => 'Current customers migrate for free. Reach out via the contact form and we\'ll walk you through it.'],
                    ['question' => 'How many sites can I have?', 'answer' => 'Depends on your plan — see Pricing. Most agents start on Solo (3 sites) and upgrade to Pro (10 sites) once they start doing new launches.'],
                    ['question' => 'Is this CEA-compliant?', 'answer' => 'Yes. Disclaimer language, independent-marketer notices, and data-handling text are pre-written and reviewed. You can customize them, but the defaults pass muster.'],
                    ['question' => 'Do you do custom design?', 'answer' => 'No — that\'s exactly what we built PUSH.SG to avoid. If you want a bespoke designer site, use an agency. If you want to launch fast and look good, use us.'],
                ],
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => 'Ready to build?',
                'description' => 'If you have a beta account, log in and launch your first site in 10 minutes. No beta account yet? Reach out via the contact form below — we are onboarding a small group at a time.',
                'button_text' => 'Start building',
                'button_link_type' => 'custom',
                'button_url' => '/admin',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function pricingContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Simple pricing, per site.</p>',
                'subheading' => '<p>All plans include the full editor, templates, per-site contact details, WhatsApp integration, and CEA-compliant defaults.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('pricing', [
                'section_title' => 'Choose your plan',
                'section_subtitle' => 'Start small. Upgrade as you scale your pipeline.',
                'plans' => [
                    [
                        'name' => 'Solo',
                        'description' => 'For individual agents building a personal brand.',
                        'is_popular' => false,
                        'currency_symbol' => 'S$',
                        'price' => '29',
                        'billing_period' => 'month',
                        'features' => [
                            ['text' => 'Up to 3 sites', 'included' => true],
                            ['text' => 'Custom domain support', 'included' => true],
                            ['text' => 'All templates included', 'included' => true],
                            ['text' => 'Email support', 'included' => true],
                            ['text' => 'Priority support', 'included' => false],
                        ],
                    ],
                    [
                        'name' => 'Pro',
                        'description' => 'For active agents running new-launch campaigns.',
                        'is_popular' => true,
                        'popular_badge_text' => 'Most popular',
                        'currency_symbol' => 'S$',
                        'price' => '79',
                        'billing_period' => 'month',
                        'features' => [
                            ['text' => 'Up to 10 sites', 'included' => true],
                            ['text' => 'Custom domain support', 'included' => true],
                            ['text' => 'All templates included', 'included' => true],
                            ['text' => 'WhatsApp priority support', 'included' => true],
                            ['text' => 'Early access to new templates', 'included' => true],
                        ],
                    ],
                    [
                        'name' => 'Team',
                        'description' => 'For agency branches and property teams.',
                        'is_popular' => false,
                        'currency_symbol' => 'S$',
                        'price' => '199',
                        'billing_period' => 'month',
                        'features' => [
                            ['text' => 'Unlimited sites', 'included' => true],
                            ['text' => 'Custom domain support', 'included' => true],
                            ['text' => 'All templates included', 'included' => true],
                            ['text' => 'Dedicated account manager', 'included' => true],
                            ['text' => 'Custom template requests', 'included' => true],
                        ],
                    ],
                ],
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('faq', [
                'heading' => 'Pricing questions',
                'items' => [
                    ['question' => 'How do I sign up?', 'answer' => "We are in invite-only beta. Reach out via the contact form and tell us a bit about what you are building — we are onboarding new users weekly, weighted toward professions we can support well (real estate, law, blogs, consulting). Public signups open once we are confident every template ships as good as we\\'d want to ship it ourselves."],
                    ['question' => 'Can I change plans?', 'answer' => 'Yes, any time. Upgrades are prorated; downgrades take effect at the next billing cycle.'],
                    ['question' => 'What happens to my sites if I cancel?', 'answer' => 'Sites go offline, but you keep your data for 90 days in case you come back. After 90 days, data is permanently deleted unless you export it first.'],
                    ['question' => 'Can I pay annually for a discount?', 'answer' => 'Yes — annual plans get 2 months free (equivalent to ~17% off). Contact us for annual pricing.'],
                ],
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function contactContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Get in touch</p>',
                'subheading' => '<p>Questions about plans, migrations, or whether PUSH.SG fits your practice? We reply within a business day.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'Reach us',
                'body' => "<p><strong>WhatsApp / Phone:</strong> [Main WhatsApp Number]<br><strong>Email:</strong> hello@push.sg<br><strong>Office hours:</strong> Mon–Fri, 9am–6pm SGT</p><p>For existing customers: priority support is available on Pro and Team plans. Mention your plan when you reach out.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->block('contact_form', [
                'title' => 'Drop us a note',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone (SG preferred)', 'required' => false, 'options' => []],
                    ['name' => 'topic', 'type' => 'select', 'label' => 'What is this about?', 'required' => true, 'options' => ['New signup questions', 'Migration from old PUSH.SG', 'Existing customer support', 'Custom template request (Team plan)', 'Partnership / agency enquiry', 'Other']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'options' => []],
                ],
                'submit_button_text' => 'Send message',
                'success_message' => "Thanks — we'll reply within one business day.",
                'auto_reply_message' => "Hi, thanks for reaching out to PUSH.SG. We've received your message and will reply within one business day. If it's urgent, WhatsApp us directly at [Main WhatsApp Number]. — The PUSH.SG team",
                'button_style' => 'btn-primary',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
        ]);
    }
}
