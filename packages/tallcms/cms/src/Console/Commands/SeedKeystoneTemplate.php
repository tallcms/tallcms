<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed the "Keystone" template — a professional realtor starter site.
 *
 * Creates an is_template_source=true site with a home/about/insights/contact
 * page set, three seed blog posts, and a primary menu. Appears in the
 * Template Gallery for SaaS users to clone and rename. Safe to run
 * repeatedly — skips if a site with the Keystone slug already exists.
 *
 * Scope: professional website for realtors. No property-listing integration
 * (that's the future real-estate plugin). The template covers the generic
 * "I'm a trusted property advisor — here's my bio + services + some
 * insights + contact me" structure that every realtor needs.
 *
 * Copy uses bracketed placeholders ([Agent Name], [City], [Year], etc.) so
 * agents can search-and-replace after cloning.
 */
class SeedKeystoneTemplate extends Command
{
    protected $signature = 'tallcms:seed-keystone-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Keystone template and recreate}';

    protected $description = 'Seed the Keystone realtor template site (home, about, insights, contact + 3 seed posts)';

    public function handle(): int
    {
        if (! Schema::hasTable('tallcms_sites')) {
            $this->error('tallcms_sites table missing — install the multisite plugin first.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('tallcms_sites', 'is_template_source')) {
            $this->error('tallcms_sites.is_template_source column missing — update the multisite plugin.');

            return self::FAILURE;
        }

        $ownerId = $this->resolveOwnerId();
        if (! $ownerId) {
            $this->error('No owner user found. Pass --owner=<id> or create a super_admin first.');

            return self::FAILURE;
        }

        $existing = DB::table('tallcms_sites')->where('domain', 'keystone.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn('Keystone template already exists (site id '.$existing->id.'). Use --force to recreate.');

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info('Removed existing Keystone template.');
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info("Created site: Keystone (id {$siteId})");

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info('Created '.count($pageIds).' pages.');

        $this->createMenu($siteId, $pageIds);
        $this->components->info('Created primary menu.');

        $this->createPosts($siteId, $ownerId);
        $this->components->info('Created 3 seed insight posts.');

        $this->newLine();
        $this->components->info('🏠 Keystone template ready. It now appears in the Template Gallery.');

        return self::SUCCESS;
    }

    protected function resolveOwnerId(): ?int
    {
        if ($owner = $this->option('owner')) {
            return (int) $owner;
        }

        // First super_admin
        $superAdminId = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'super_admin')
            ->value('model_has_roles.model_id');

        if ($superAdminId) {
            return (int) $superAdminId;
        }

        // Fallback: first user
        return DB::table('users')->orderBy('id')->value('id');
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
            'name' => 'Keystone',
            'domain' => 'keystone.template',
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
            'home' => [
                'title' => 'Home',
                'is_homepage' => true,
                'content' => $this->homeContent(),
            ],
            'about' => [
                'title' => 'About',
                'content' => $this->aboutContent(),
            ],
            'insights' => [
                'title' => 'Insights',
                'content' => $this->insightsContent(),
            ],
            'contact' => [
                'title' => 'Contact',
                'content' => $this->contactContent(),
            ],
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
        foreach (['home' => 'Home', 'about' => 'About', 'insights' => 'Insights', 'contact' => 'Contact'] as $slug => $label) {
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

    protected function createPosts(int $siteId, int $ownerId): void
    {
        $posts = [
            [
                'title' => "A first-time buyer's guide to [City]",
                'slug' => 'first-time-buyer-guide',
                'excerpt' => "Everything a first-time buyer in [City] needs to know — from pre-approval to keys-in-hand. A practical, step-by-step walkthrough.",
                'content' => $this->firstTimeBuyerPost(),
            ],
            [
                'title' => "What's happening in the [City] property market — [Year Quarter]",
                'slug' => 'market-update',
                'excerpt' => "Median prices, days-on-market, neighborhood movers. A quarterly read of what's actually selling and for how much.",
                'content' => $this->marketUpdatePost(),
            ],
            [
                'title' => '10 things I tell every client who is selling',
                'slug' => 'selling-your-home-tips',
                'excerpt' => "After 15 years and 300+ transactions, these are the 10 principles that have consistently helped my clients sell faster and for more.",
                'content' => $this->sellingTipsPost(),
            ],
        ];

        foreach ($posts as $i => $post) {
            DB::table('tallcms_posts')->insert([
                'site_id' => $siteId,
                'author_id' => $ownerId,
                'title' => json_encode(['en' => $post['title']]),
                'slug' => json_encode(['en' => $post['slug']]),
                'excerpt' => json_encode(['en' => $post['excerpt']]),
                'content' => json_encode(['en' => $post['content']]),
                'status' => 'published',
                'published_at' => now()->subDays($i * 7),
                'created_at' => now()->subDays($i * 7),
                'updated_at' => now()->subDays($i * 7),
            ]);
        }
    }

    // --- Block helpers ------------------------------------------------------

    /**
     * Emit a custom block as TipTap content HTML.
     */
    protected function block(string $id, array $config): string
    {
        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return "<div data-type=\"customBlock\" data-config=\"{$encoded}\" data-id=\"{$id}\"></div>";
    }

    /**
     * Emit a paragraph node (TipTap renders it as a <p> when placed between blocks).
     */
    protected function paragraph(string $text): string
    {
        return '<p>'.htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</p>';
    }

    // --- Page contents ------------------------------------------------------

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => "<p>Hi, I'm [Agent Name].</p>",
                'subheading' => "<p>Helping families buy, sell, and rent in [City] since [Year]. Licensed agent, trusted advisor, and a familiar face in the neighborhoods I serve.</p>",
                'button_text' => 'Book a consultation',
                'button_link_type' => 'anchor',
                'button_anchor_id' => 'contact',
                'secondary_button_text' => 'About me',
                'secondary_button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[70vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-primary to-secondary',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->block('stats', [
                'heading' => 'A track record built one home at a time',
                'stats' => [
                    ['value' => '300+', 'label' => 'Transactions closed'],
                    ['value' => '$200M', 'label' => 'In property sold'],
                    ['value' => '15', 'label' => 'Years licensed'],
                    ['value' => '4.9/5', 'label' => 'Client rating'],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'How I can help',
                'subheading' => "Three services, one advisor. Straightforward fees, no surprises.",
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home',
                        'title' => 'Selling your home',
                        'description' => 'Pricing strategy, staging advice, listing photography, and negotiations that protect your bottom line. Most clients accept an offer within 30 days.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-key',
                        'title' => 'Buying your home',
                        'description' => "Shortlisting that respects your budget, honest neighborhood walk-throughs, and negotiations on your behalf — not the seller's.",
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office',
                        'title' => 'Rental advisory',
                        'description' => 'For landlords and tenants. Tenant-screening for owners; lease-negotiation and neighborhood matching for renters.',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('content_block', [
                'title' => 'A little about me',
                'body' => "<p>I grew up in [City] and have watched it change street by street. That neighborhood-level fluency is what I bring to every client relationship — whether you're an upgrader wondering which school district to target, a downsizer ready to simplify, or a first-time buyer nervous about the process.</p><p>I work with a small number of clients at a time so that every transaction gets the attention it deserves. I return calls the same day. I tell you what I'd do if it were my own family's money.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('testimonials', [
                'heading' => 'What clients say',
                'testimonials' => [
                    [
                        'quote' => "[Agent Name] made our first home purchase feel manageable. We were clueless; she was patient and honest — including about places we shouldn't buy.",
                        'author_name' => 'The Chen family',
                        'author_title' => 'First-time buyers, 2024',
                    ],
                    [
                        'quote' => "Sold our place in 11 days, $30k above asking. The pricing and staging advice were spot-on. We'd recommend her to anyone.",
                        'author_name' => 'Sarah & Michael',
                        'author_title' => 'Condo sellers, 2024',
                    ],
                    [
                        'quote' => 'Straightforward, fast to respond, and refused to let us overpay. That last one is rare in this industry.',
                        'author_name' => 'David L.',
                        'author_title' => 'HDB upgrader, 2023',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('posts', [
                'posts_count' => 3,
                'show_image' => true,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_categories' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('faq', [
                'heading' => 'Frequently asked',
                'items' => [
                    ['question' => 'How do you price a property?', 'answer' => "I combine recent comparable sales, current listings in the same neighborhood, and market conditions. For sellers, I provide a written pricing analysis before we list. For buyers, I'll tell you if a place is priced above market before you make an offer."],
                    ['question' => 'What areas do you cover?', 'answer' => "Primarily [City] and its neighboring districts. I don't take clients outside my area of expertise — you're better served by an agent who knows the streets."],
                    ['question' => "What's your commission?", 'answer' => 'Standard for the market, transparent, and discussed upfront. No hidden fees, no surprise charges at closing.'],
                    ['question' => 'How do we start?', 'answer' => "Book a 30-minute consultation through the form below. No commitment — we'll talk about what you're trying to do and whether I'm the right fit."],
                ],
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('contact_form', [
                'title' => "Let's talk",
                'description' => "Tell me what you're thinking and I'll be in touch within 24 hours. No hard sells, no mailing list tricks.",
                'anchor_id' => 'contact',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'required' => false, 'options' => []],
                    ['name' => 'intent', 'type' => 'select', 'label' => "I'm looking to", 'required' => true, 'options' => ['Buy', 'Sell', 'Rent', 'Just exploring']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'A bit more about your situation', 'required' => true, 'options' => []],
                ],
                'submit_button_text' => 'Send message',
                'success_message' => "Thanks — I'll be in touch within 24 hours. If it's urgent, text me directly at [Phone].",
                'auto_reply_message' => "Hi, thanks for reaching out — I've got your note and will call or email within 24 hours. If it's urgent, my number is [Phone] and I'm happy to text. Talk soon, [Agent Name]",
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function aboutContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>About [Agent Name]</p>',
                'subheading' => '<p>Licensed since [Year]. Based in [City]. Family first, property second.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'My story',
                'body' => "<p>I got into real estate by accident. I was helping my parents sell their flat in [Neighborhood] and the agent they hired was so uninterested in their situation that I ended up doing most of the work myself. They got a great price; I got a new career.</p><p>Fifteen years later, I've closed over 300 transactions — but the thing I remember most is the families. The couple who needed three bedrooms because their mother was moving in. The single mom who finally saved enough for a flat of her own. The seller who'd been widowed and was downsizing after 40 years in the same home.</p><p>Property is a big decision. Not just financially — emotionally. I try to be the advisor I wish my parents had.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'Credentials',
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-academic-cap', 'title' => 'CEA Licensed', 'description' => '[License #], registered with the Council for Estate Agencies since [Year].'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-office-2', 'title' => 'Agency', 'description' => "Associated with [Agency Name], one of [City]'s established property firms."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-trophy', 'title' => 'Recognition', 'description' => 'Top producer [Year] and [Year]. Platinum circle member [Year].'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-chart-bar', 'title' => 'Specializations', 'description' => 'HDB resale, private condo, rental advisory. Not landed or commercial — referred to trusted partners when asked.'],
                ],
                'columns' => '2',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => "Let's talk about your move",
                'description' => "I'll give you a straight answer on what your place is worth, or what you can realistically afford — no pressure, no commitment.",
                'button_text' => 'Start the conversation',
                'button_link_type' => 'page',
                'background' => 'bg-primary',
                'padding' => 'py-16',
                'text_color' => 'text-white',
            ]),
        ]);
    }

    protected function insightsContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Property insights</p>',
                'subheading' => '<p>Market commentary, buying and selling guides, and the questions I get asked most.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('posts', [
                'posts_count' => 12,
                'show_image' => true,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function contactContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => "<p>Let's talk</p>",
                'subheading' => '<p>Reach out and I will respond personally within 24 hours.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'How to reach me',
                'body' => "<p><strong>Phone / WhatsApp:</strong> [Phone]<br><strong>Email:</strong> [Email]<br><strong>Office hours:</strong> Mon–Fri 9am–7pm, Sat 10am–4pm</p><p>If you're urgent, text me. Otherwise the form below is the fastest way to reach me — the submissions go straight to my inbox and I read every one personally.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->block('contact_form', [
                'title' => 'Send me a note',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'required' => false, 'options' => []],
                    ['name' => 'intent', 'type' => 'select', 'label' => "I'm looking to", 'required' => true, 'options' => ['Buy', 'Sell', 'Rent', 'Just exploring']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Your message', 'required' => true, 'options' => []],
                ],
                'submit_button_text' => 'Send message',
                'success_message' => "Thanks — I'll be in touch within 24 hours.",
                'auto_reply_message' => "Hi, thanks for reaching out. I've received your note and will get back to you within 24 hours. — [Agent Name]",
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function firstTimeBuyerPost(): string
    {
        return implode("\n", [
            $this->paragraph("Buying your first home is one of the largest financial decisions you'll make, and the process is full of jargon and moving parts. Here's the 8-step path that works for most first-time buyers I've worked with in [City]."),
            $this->paragraph("1. Get pre-approved before you shop. Lenders will tell you exactly how much you can borrow. Without this, you're window-shopping. Budget 1-2 weeks."),
            $this->paragraph("2. Nail down your non-negotiables. Three things: location (be specific about neighborhoods), size (bedroom count is usually the clearest filter), and budget ceiling. Everything else is trade-offs."),
            $this->paragraph("3. Shortlist with an agent you trust. A good agent saves you weeks of viewing bad fits. Ask for 3 listings that match your criteria within 48 hours."),
            $this->paragraph("4. Walk through 5-8 properties maximum. More than that and you lose the ability to compare. Take photos, take notes, take your partner's temperature."),
            $this->paragraph("5. Make a first offer 5-10% below asking for an opening position. Unless it's a hot unit with multiple bids — then the game is different and your agent should coach you through it."),
            $this->paragraph("6. Negotiate. This is where the agent earns their commission. Counter-offers, contingencies, closing timeline, inclusions — all on the table."),
            $this->paragraph("7. Close. Legal paperwork, final inspection, key handover. Your lawyer does most of this; you show up at a few meetings."),
            $this->paragraph("8. Move in and breathe. The hard part's over. The hard part's over."),
            $this->paragraph("Questions? The easiest way to reach me is the contact form on the home page — I'll personally respond within 24 hours."),
        ]);
    }

    protected function marketUpdatePost(): string
    {
        return implode("\n", [
            $this->paragraph("Every quarter I write a short commentary on what's actually moving in [City]'s property market — not the headlines, the street-level reality. This edition covers [Year Quarter]."),
            $this->paragraph("<strong>Median prices:</strong> Resale HDB held steady quarter-over-quarter; private condos up a modest [X]% on thin volume; new launches continue to attract first-timer demand."),
            $this->paragraph("<strong>Days on market:</strong> Well-priced listings are moving in 2-4 weeks. Anything above the comparable-sales range sits for 60+ days and eventually discounts to clear."),
            $this->paragraph("<strong>Neighborhood movers:</strong> [Neighborhood A] saw stronger buyer interest as the new MRT station approached operational date; [Neighborhood B] inventory thinned after several large developments completed."),
            $this->paragraph("<strong>What it means for sellers:</strong> Price realistically. The market is rewarding sellers who list at fair value and penalizing those who chase last year's peak."),
            $this->paragraph("<strong>What it means for buyers:</strong> Shortlisting matters more than speed. There are good units; there are also overpriced ones sitting unsold. Your agent should know which is which."),
            $this->paragraph("Reach out if you want the numbers for a specific development or neighborhood — I'm happy to share what I'm seeing on the ground."),
        ]);
    }

    protected function sellingTipsPost(): string
    {
        return implode("\n", [
            $this->paragraph("After 300+ transactions, these are the 10 things I find myself saying to every seller. Not original — but consistently true."),
            $this->paragraph("<strong>1. Price is 80% of the game.</strong> Everything else — staging, listing copy, marketing — matters at the margin. Price matters first."),
            $this->paragraph("<strong>2. List at market, not at hope.</strong> Overpriced listings get stale fast and sell for less than a correctly-priced listing would have."),
            $this->paragraph("<strong>3. Declutter before you stage.</strong> Buyers need to imagine themselves living there. Your stuff is in the way of that."),
            $this->paragraph("<strong>4. Clean like your mother-in-law is coming.</strong> Not kidding. Professional clean, including windows. It's the cheapest ROI in the entire sale."),
            $this->paragraph("<strong>5. Photos are everything online.</strong> Invest in a professional photographer. Buyers decide whether to view your place from the listing photos."),
            $this->paragraph("<strong>6. Price rounds to the nearest K.</strong> No $847,250 listing prices. It signals you care more about a spreadsheet than about selling."),
            $this->paragraph("<strong>7. Don't over-renovate.</strong> The $30k kitchen upgrade rarely returns $30k at sale. Fix the obvious, leave the rest."),
            $this->paragraph("<strong>8. Accept feedback.</strong> If three viewings all mention the same thing, it's the thing. Listen to the market."),
            $this->paragraph("<strong>9. First offer is often the best offer.</strong> The hot-market instinct to wait for higher rarely pays off."),
            $this->paragraph("<strong>10. Hire an agent who tells you the truth.</strong> Not the agent who promises the highest price — the one who tells you what the market will actually bear."),
            $this->paragraph("Thinking about selling? Reach out — the consultation is free and I'll give you a realistic read on your place in 30 minutes."),
        ]);
    }
}
