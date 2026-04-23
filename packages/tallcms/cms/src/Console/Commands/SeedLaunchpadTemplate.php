<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed the "Launchpad" template — a landing page for a property launch.
 *
 * Singapore-shaped: designed for licensed property agents marketing new
 * launches (condo, executive condo, landed) as independent marketers.
 * The page matches the conventions of current SG new-launch sites:
 * single-page scroll, project stats as tiles, location narrative, unit
 * mix, amenities, brochure gate, register-interest form, and the
 * legally-required independent-marketer disclaimer.
 *
 * Scope: one project per site. For a multi-project agent, they'd clone
 * this once per launch (Narra Residences, The Collective, etc.) and
 * customize. The Template Gallery makes that straightforward.
 *
 * Copy uses bracketed placeholders for fields that change per launch:
 * [Project Name], [District #], [Developer], [TOP], [Unit Count],
 * [99-year leasehold / freehold], unit type sizes and prices, etc.
 */
class SeedLaunchpadTemplate extends Command
{
    protected $signature = 'tallcms:seed-launchpad-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Launchpad template and recreate}';

    protected $description = 'Seed the Launchpad template for new property launch landing pages';

    public function handle(): int
    {
        if (! Schema::hasTable('tallcms_sites') || ! Schema::hasColumn('tallcms_sites', 'is_template_source')) {
            $this->error('Multisite plugin with is_template_source column required.');

            return self::FAILURE;
        }

        $ownerId = $this->resolveOwnerId();
        if (! $ownerId) {
            $this->error('No owner user found. Pass --owner=<id> or create a super_admin first.');

            return self::FAILURE;
        }

        $existing = DB::table('tallcms_sites')->where('domain', 'launchpad.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn('Launchpad template already exists (site id '.$existing->id.'). Use --force to recreate.');

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info('Removed existing Launchpad template.');
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info("Created site: Launchpad (id {$siteId})");

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info('Created '.count($pageIds).' pages.');

        $this->createMenu($siteId, $pageIds);
        $this->components->info('Created primary menu.');

        $this->createPosts($siteId, $ownerId);
        $this->components->info('Created 3 seed update posts.');

        $this->newLine();
        $this->components->info('🏢 Launchpad template ready. It now appears in the Template Gallery.');

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
            'name' => 'Launchpad',
            'domain' => 'launchpad.template',
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
            'thank-you' => [
                'title' => 'Thank You',
                'content' => $this->thankYouContent(),
            ],
            'disclaimer' => [
                'title' => 'Disclaimer',
                'content' => $this->disclaimerContent(),
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

        // Landing pages usually just have anchor nav within the home page
        // plus a disclaimer link in the footer. Keep the primary menu tiny.
        $lft = 1;
        foreach ([
            ['label' => 'Project', 'type' => 'page', 'page_id' => $pageIds['home']],
            ['label' => 'Register Interest', 'type' => 'page', 'page_id' => $pageIds['home'], 'anchor' => 'register'],
            ['label' => 'Disclaimer', 'type' => 'page', 'page_id' => $pageIds['disclaimer']],
        ] as $item) {
            DB::table('tallcms_menu_items')->insert([
                'menu_id' => $menuId,
                'type' => $item['type'],
                'label' => json_encode(['en' => $item['label']]),
                'page_id' => $item['page_id'] ?? null,
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
                'title' => 'Showflat preview now open — book your slot',
                'slug' => 'showflat-preview-open',
                'excerpt' => "The [Project Name] showflat preview is open by appointment. Slots fill fast during the preview phase — reserve yours now.",
                'content' => $this->showflatPost(),
            ],
            [
                'title' => 'Construction update: what has happened since groundbreaking',
                'slug' => 'construction-update',
                'excerpt' => "A brief progress report on where [Project Name] is in its build timeline, ahead of TOP in [Q# YYYY].",
                'content' => $this->constructionPost(),
            ],
            [
                'title' => 'What to expect at the [Project Name] showflat',
                'slug' => 'what-to-expect',
                'excerpt' => "First time viewing a new launch showflat? Here's a quick walk-through of what you'll see, what to ask, and what to bring.",
                'content' => $this->whatToExpectPost(),
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

    protected function block(string $id, array $config): string
    {
        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return "<div data-type=\"customBlock\" data-config=\"{$encoded}\" data-id=\"{$id}\"></div>";
    }

    protected function paragraph(string $text): string
    {
        return '<p>'.$text.'</p>';
    }

    // --- Page contents ------------------------------------------------------

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>[Project Name]</p>',
                'subheading' => "<p>[Short, evocative tagline — e.g. \"Nature-inspired living at [Location]\"]. A [Unit Count]-unit development in [District # / Neighborhood Name].</p>",
                'button_text' => 'Register interest',
                'button_link_type' => 'custom',
                'button_url' => '#register',
                'secondary_button_text' => 'Book showflat preview',
                'secondary_button_link_type' => 'external',
                'secondary_button_url' => 'https://wa.me/659999999?text=Hi%20I%20am%20interested%20in%20[Project%20Name]',
                'secondary_button_microcopy' => 'WhatsApp link — replace 659999999 with your phone and update the project name in the text= parameter.',
                'layout' => 'centered',
                'height' => 'min-h-[85vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-success',
                'button_size' => 'btn-lg',
            ]),
            $this->block('content_block', [
                'title' => 'About [Project Name]',
                'body' => "<p>[Project Name] is a [Tenure: 99-year leasehold / freehold] development of [Unit Count] residences at [Address / Street Name], [District #]. [Developer] brings [X] years of development experience to the [Neighborhood] enclave, with a design that emphasizes [distinctive element — e.g. nature integration, skyline views, family-friendly layout].</p><p>Expected TOP: <strong>[Q# YYYY]</strong>. Limited preview units are now available for qualified buyers.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('stats', [
                'heading' => 'Key details',
                'stats' => [
                    ['value' => '[District #]', 'label' => 'District'],
                    ['value' => '[Tenure]', 'label' => 'Tenure'],
                    ['value' => '[Q# YYYY]', 'label' => 'Expected TOP'],
                    ['value' => '[Unit Count]', 'label' => 'Total units'],
                ],
                'columns' => '4',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'Unit mix',
                'subheading' => "Indicative sizes and starting prices. Final pricing at showflat.",
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home',
                        'title' => '1 Bedroom',
                        'description' => "[XXX-XXX sqft] · From $[X.XX]M",
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-home-modern',
                        'title' => '2 Bedroom',
                        'description' => "[XXX-XXX sqft] · From $[X.XX]M",
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-building-office-2',
                        'title' => '3 Bedroom',
                        'description' => "[XXXX-XXXX sqft] · From $[X.XX]M",
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-sparkles',
                        'title' => 'Penthouse',
                        'description' => "[XXXX+ sqft] · POA",
                    ],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'Amenities & facilities',
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-sun', 'title' => '50m Lap Pool', 'description' => 'Full-length swimming pool with sun loungers and cabanas.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-bolt', 'title' => 'Fully-Equipped Gym', 'description' => "Strength and cardio equipment, open 24/7 to residents."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-fire', 'title' => 'BBQ Pavilions', 'description' => 'Multiple bookable pavilions for gatherings, with dining tables and grills.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-library', 'title' => 'Clubhouse', 'description' => "A residents-only clubhouse for events and private bookings."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-cloud', 'title' => 'Sky Garden', 'description' => "Rooftop garden with panoramic views — the development's signature feature."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-user-group', 'title' => "Children's Playground", 'description' => "Thoughtfully-designed play area, shaded and safety-rated."],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('content_block', [
                'title' => 'Location — [Neighborhood Name]',
                'body' => "<p>[Neighborhood Name] is [two-sentence evocation of the neighborhood — e.g. \"one of Singapore's most coveted residential enclaves, where nature meets convenience. The Dairy Farm Nature Reserve sits five minutes away, while the Downtown MRT line connects you to town in under 20 minutes.\"]</p><p>Residents are a short walk from [schools], [green spaces], and [retail amenities], with direct access to [major expressway / MRT line] for commutes to the CBD and beyond.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            // Requires the TallCMS Pro plugin for the pro-map block.
            // Defaults below drop a marker on central Singapore — swap the
            // lat/lng, address, and marker_title to your project's site.
            // OpenStreetMap provider requires no API key.
            $this->block('pro-map', [
                'heading' => 'Find the site',
                'subheading' => '[Project address with postal code]',
                'latitude' => '1.3521',
                'longitude' => '103.8198',
                'address' => '[Project address with postal code]',
                'marker_title' => '[Project Name]',
                'contact_info' => "Showflat: [Address]\nHours: [Mon-Sun, by appointment]\nWhatsApp: [Phone]",
                'provider' => 'openstreetmap',
                'zoom' => 15,
                'height' => 'lg',
                'show_marker' => true,
                'scrollwheel_zoom' => false,
                'rounded' => true,
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('stats', [
                'heading' => "What's nearby",
                'stats' => [
                    ['value' => '[X]', 'label' => 'min walk to [MRT Station]'],
                    ['value' => '[X]', 'label' => 'min drive to CBD'],
                    ['value' => '[X]', 'label' => 'top schools within 2km'],
                    ['value' => '[X]', 'label' => 'retail malls within 5min'],
                ],
                'columns' => '4',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => 'Download the full brochure',
                'description' => "Complete unit mix, floor plans, site plan, developer details, and pricing indications.",
                'button_text' => 'Download brochure',
                'button_link_type' => 'custom',
                'button_url' => '#register',
                'button_microcopy' => 'Complete the form below to receive the brochure by email.',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('contact_form', [
                'title' => 'Register your interest',
                'description' => "Fill in your details and our sales team will reach out with the brochure, pricing, and showflat availability within 24 hours.",
                'anchor_id' => 'register',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Full name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone (SG preferred)', 'required' => true, 'options' => []],
                    ['name' => 'unit_type', 'type' => 'select', 'label' => 'Preferred unit type', 'required' => true, 'options' => ['1 Bedroom', '2 Bedroom', '3 Bedroom', 'Penthouse', 'Still deciding']],
                    ['name' => 'buyer_status', 'type' => 'select', 'label' => 'Buyer status', 'required' => true, 'options' => ['Singapore Citizen', 'Singapore PR', 'Foreigner', 'Company/Trust']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Anything specific you want to know?', 'required' => false, 'options' => []],
                ],
                'submit_button_text' => 'Register interest',
                'success_message' => "Thank you — we'll be in touch with the brochure and details within 24 hours.",
                'auto_reply_message' => "Thanks for registering your interest in [Project Name]. We'll send the brochure and respond to your query within 24 hours. If it's urgent, WhatsApp us at [Phone]. — [Agent Name], [Agency]",
                'redirect_page_id' => null,
                'button_style' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
            $this->block('content_block', [
                'title' => 'The developer',
                'body' => "<p><strong>[Developer]</strong> has been shaping Singapore's residential landscape since [Year Founded]. Past projects include [Notable Project 1], [Notable Project 2], and [Notable Project 3] — each known for [common trait: design quality / location / amenity execution].</p><p>[Project Name] continues that track record, with [distinctive architectural or master-plan note].</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('content_block', [
                'title' => 'Disclaimer',
                'body' => "<p style=\"font-size: 0.9em; color: #666;\">This is an independent marketing website operated by <strong>[Agent Name]</strong>, a licensed salesperson (CEA Reg: [License #]) with <strong>[Agency Name]</strong>. This site is not the official developer's site and is not an official sales or marketing channel of [Developer].</p><p style=\"font-size: 0.9em; color: #666;\">All images are artist's impressions and are for illustrative purposes only. Specifications, unit mixes, prices, and availability are subject to change without notice. For binding information, please refer to the official developer material provided at the showflat.</p><p style=\"font-size: 0.9em; color: #666;\">See the full <a href=\"/disclaimer\">disclaimer</a> for more.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
        ]);
    }

    protected function thankYouContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Thank you.</p>',
                'subheading' => "<p>We've got your details and will send the brochure within 24 hours. If it's urgent, WhatsApp us at [Phone].</p>",
                'button_text' => 'Back to the project',
                'button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[60vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'What happens next',
                'body' => "<p>1. Check your inbox — the brochure is on its way.</p><p>2. We'll personally call or WhatsApp within 24 hours to answer questions and book a showflat preview if you'd like one.</p><p>3. Meanwhile, read up on the location and developer on the <a href=\"/\">project page</a>.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function disclaimerContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Disclaimer</p>',
                'subheading' => '<p>The fine print for [Project Name].</p>',
                'layout' => 'centered',
                'height' => 'min-h-[30vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'Independent marketing website',
                'body' => "<p>This website is owned and operated by <strong>[Agent Name]</strong>, a licensed salesperson registered with the Council for Estate Agencies (CEA Reg: <strong>[License #]</strong>) with <strong>[Agency Name]</strong> (Agency CEA Licence: [Agency License #]).</p><p>This is <strong>not</strong> the official developer's website and is <strong>not</strong> an official sales or marketing channel of [Developer]. We market [Project Name] as a licensed co-broker under the Estate Agents Act.</p><h3>Artist's Impressions</h3><p>All renderings, floor plans, site plans, images, and video content on this website are artist's impressions provided for illustrative purposes only. Actual building form, specifications, finishes, and external views may differ from what is shown.</p><h3>Pricing and availability</h3><p>Prices, unit mixes, and availability are correct at time of publication but are subject to change without notice at the developer's discretion. Official and binding pricing is provided only at the showflat or in the official sales material provided to you by the authorized marketing agent at the point of sale.</p><h3>Data protection</h3><p>When you submit a form on this website, your information is used solely to respond to your enquiry about [Project Name]. Your data is shared with the developer's authorized marketing agents for this purpose and is not sold or shared with unrelated third parties. You may request deletion of your data at any time by contacting us.</p><h3>No guarantees</h3><p>Any commentary about the investment potential, rental yield, capital appreciation, or similar forward-looking statements is provided as general information and should not be taken as personalized financial or investment advice. Consult your own advisors for decisions about your specific circumstances.</p><h3>Contact</h3><p>For questions about this website or our marketing of [Project Name], reach out via the <a href=\"/\">contact form on the project page</a> or WhatsApp us at [Phone].</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function showflatPost(): string
    {
        return implode("\n", [
            $this->paragraph("The [Project Name] showflat preview is now open by appointment. During the preview phase, slots are limited and allocated on a first-reserved basis — so book early if you're keen to view."),
            $this->paragraph("<strong>What the preview includes:</strong>"),
            $this->paragraph("• A fully-furnished show unit — typically a [preview unit type, e.g. 3-bedroom premium] laid out to scale so you can experience the space as it will be delivered."),
            $this->paragraph("• Site plan walkthrough with one of our specialists, covering facilities, orientation, and the unit mix."),
            $this->paragraph("• Indicative pricing, payment schedule, and availability."),
            $this->paragraph("• Answers to the specific questions you have — bring them."),
            $this->paragraph("<strong>What to bring:</strong>"),
            $this->paragraph("• Photo ID (NRIC or passport)."),
            $this->paragraph("• Your buyer status (Singapore Citizen, PR, Foreigner, Company/Trust) — this affects ABSD and eligibility."),
            $this->paragraph("• If you're buying with a partner or family member, bring them too."),
            $this->paragraph("<strong>Book your slot:</strong> fill in the form on the <a href=\"/\">project page</a> or WhatsApp us directly."),
        ]);
    }

    protected function constructionPost(): string
    {
        return implode("\n", [
            $this->paragraph("A quick progress update on [Project Name] ahead of expected TOP in <strong>[Q# YYYY]</strong>."),
            $this->paragraph("<strong>Site works</strong>: Groundworks and piling completed [Month YYYY]. Structural works currently at [X]% — the main tower reaches [floor number] level, with the [feature, e.g. sky garden] lift frame taking shape."),
            $this->paragraph("<strong>Timeline</strong>: Topping out expected by [Q# YYYY]. Façade and M&E fit-out follows through [timeframe]. TOP remains scheduled for [Q# YYYY], with CSC expected [X] months later."),
            $this->paragraph("<strong>What this means for buyers</strong>: Units sold during the launch phase remain under the original payment schedule — progress payments trigger at each construction milestone per the official S&P. If you're a current buyer and would like a personalized progress update on your unit, we can arrange that — reach out via WhatsApp."),
            $this->paragraph("<strong>For prospective buyers</strong>: Construction progress doesn't change the showflat preview experience — the showflat is built to the same specifications as the delivered unit. Pricing and availability are refreshed at each release phase."),
        ]);
    }

    protected function whatToExpectPost(): string
    {
        return implode("\n", [
            $this->paragraph("First time viewing a new launch showflat? Here's what typically happens — and what to pay attention to."),
            $this->paragraph("<strong>The walkthrough (30-45 minutes)</strong>"),
            $this->paragraph("You'll start with a brief welcome and orientation at the marketing suite — usually a dedicated space next to the showflat. A specialist will walk you through the site plan (where the towers are, where the amenities are, orientation of each block) and the unit mix (what sizes and layouts are available, how pricing works at each level)."),
            $this->paragraph("Then you'll walk through the show unit itself — typically a premium unit type that shows the development at its best. Take notes on: kitchen layout (is it closed or open?), storage (adequate for your family?), natural light (which direction does the unit face?), and the feel of the space. Photos are usually allowed."),
            $this->paragraph("<strong>What to ask</strong>"),
            $this->paragraph("• <em>Unit availability</em>: which stacks (column of units on the same floor across levels) are still available at my budget?"),
            $this->paragraph("• <em>Pricing per square foot</em>: what's the psf range, and how does it compare to nearby comparable launches?"),
            $this->paragraph("• <em>Orientation</em>: which direction does the unit face (north, east-facing sunrise, etc.) and what will I see?"),
            $this->paragraph("• <em>Floor premium</em>: how much does the same unit cost on different floors?"),
            $this->paragraph("• <em>Payment schedule</em>: what are the progress payment milestones?"),
            $this->paragraph("• <em>ABSD</em>: if you're a second-property buyer, foreigner, or PR, get clarity on your total cost including stamp duties."),
            $this->paragraph("<strong>What to bring</strong>"),
            $this->paragraph("Your ID, your buyer status info, and ideally your partner / family member if this is a joint purchase. If you're ready to move quickly, your IPA (In-Principle Approval from a bank) helps — but it's not required for the initial viewing."),
            $this->paragraph("<strong>After the viewing</strong>"),
            $this->paragraph("There's no obligation. A good agent will follow up with the brochure, answer questions you didn't think to ask in person, and wait for you to decide. If you feel pressured — find another agent."),
            $this->paragraph("Book a preview via the form on the <a href=\"/\">project page</a>. We'll confirm your slot within a business day."),
        ]);
    }
}
