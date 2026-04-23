<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed the "Spotlight" template — a premium variant of Launchpad for
 * property-launch landing pages that captures leads in the hero.
 *
 * Variation from Launchpad:
 *  - Hero uses the with-form layout so the lead-capture form sits
 *    alongside the hero copy on first paint. No scroll required to
 *    register interest. Converts better for high-intent traffic
 *    (paid ads, email campaigns, QR codes at the showflat).
 *  - No separate Register Interest section later in the page (the
 *    hero form handles it).
 *  - More premium / evocative copy tone — positioned for higher-end
 *    launches (freehold, luxury condo, landed) where the pitch is
 *    lifestyle rather than best-value.
 *
 * Same SG-specific compliance as Launchpad: CEA disclaimer page,
 * independent-marketer language, ABSD-aware buyer status field.
 */
class SeedSpotlightTemplate extends Command
{
    protected $signature = 'tallcms:seed-spotlight-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Spotlight template and recreate}';

    protected $description = 'Seed the Spotlight template — a premium property launch landing page with in-hero lead form';

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

        $existing = DB::table('tallcms_sites')->where('domain', 'spotlight.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn('Spotlight template already exists (site id '.$existing->id.'). Use --force to recreate.');

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info('Removed existing Spotlight template.');
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info("Created site: Spotlight (id {$siteId})");

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info('Created '.count($pageIds).' pages.');

        $this->createMenu($siteId, $pageIds);
        $this->components->info('Created primary menu.');

        $this->newLine();
        $this->components->info('✨ Spotlight template ready. It now appears in the Template Gallery.');

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
            'name' => 'Spotlight',
            'domain' => 'spotlight.template',
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

        $lft = 1;
        foreach ([
            ['label' => 'The Residence', 'type' => 'page', 'page_id' => $pageIds['home']],
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

    // --- Block helpers ------------------------------------------------------

    protected function block(string $id, array $config): string
    {
        $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded = htmlspecialchars($json, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return "<div data-type=\"customBlock\" data-config=\"{$encoded}\" data-id=\"{$id}\"></div>";
    }

    // --- Page contents ------------------------------------------------------

    protected function homeContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>[Project Name]</p>',
                'subheading' => "<p>A limited collection of [Unit Count] residences in [District #] — [Tenure] from [Year]. Register your interest to receive the brochure, price guide, and priority access to the showflat.</p>",
                // Hero's with-form layout renders a form card alongside the
                // hero copy instead of duplicating contact form lower down.
                'layout' => 'with-form',
                'height' => 'min-h-[90vh]',
                'text_alignment' => 'text-left',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 40,
                'form_title' => 'Priority preview access',
                'form_fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'required' => true, 'options' => []],
                    ['name' => 'unit_type', 'type' => 'select', 'label' => 'Interested in', 'required' => true, 'options' => ['1 Bedroom', '2 Bedroom', '3 Bedroom', 'Penthouse', 'Still deciding']],
                    ['name' => 'buyer_status', 'type' => 'select', 'label' => 'Buyer profile', 'required' => true, 'options' => ['Singapore Citizen', 'Singapore PR', 'Foreigner', 'Company/Trust']],
                ],
                'form_submit_text' => 'Get the brochure',
                'form_success_message' => "Thank you. We'll be in touch with the brochure and priority preview details within 24 hours.",
                'form_button_style' => 'btn-primary',
                'form_card_style' => 'bg-base-100 shadow-2xl',
            ]),
            $this->block('content_block', [
                'title' => 'A quiet assertion of arrival',
                'body' => "<p>[Project Name] is a [Tenure] development of [Unit Count] residences at [Address], [District #]. Designed by [Architect/Designer] for [Developer], its architecture draws from [design cue — e.g. \"the mature shade of the surrounding canopy\" / \"the rhythm of pre-war shophouses the estate is named for\"].</p><p>Residences range from [X] to [Y] square feet, with finishes and orientations specified to outlast fashion. Expected TOP: <strong>[Q# YYYY]</strong>.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            $this->block('stats', [
                'heading' => 'At a glance',
                'stats' => [
                    ['value' => '[District #]', 'label' => 'District'],
                    ['value' => '[Tenure]', 'label' => 'Tenure'],
                    ['value' => '[Q# YYYY]', 'label' => 'Expected TOP'],
                    ['value' => '[Unit Count]', 'label' => 'Residences'],
                ],
                'columns' => '4',
                'background' => 'bg-base-200',
                'padding' => 'py-24',
            ]),
            $this->block('features', [
                'heading' => 'The residences',
                'subheading' => 'Each floorplan is considered — not just configured. Indicative starting prices, final pricing at preview.',
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-home', 'title' => '1 Bedroom', 'description' => "[XXX–XXX sqft] · From \$[X.XX]M"],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-home-modern', 'title' => '2 Bedroom', 'description' => "[XXX–XXX sqft] · From \$[X.XX]M"],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-office-2', 'title' => '3 Bedroom', 'description' => "[XXXX–XXXX sqft] · From \$[X.XX]M"],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-sparkles', 'title' => 'Penthouse', 'description' => "[XXXX+ sqft] · Upon application"],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            $this->block('features', [
                'heading' => 'The day-to-day',
                'subheading' => "Amenities designed for how residents actually live — not just what looks good in a brochure.",
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-sun', 'title' => '50m Lap Pool', 'description' => 'Full-length pool with timber deck and private cabanas. Lit for evenings.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-bolt', 'title' => 'Gym & Wellness', 'description' => "Fully-equipped gym and dedicated yoga / stretch room. Residents-only, 24 hours."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-fire', 'title' => 'Dining Pavilions', 'description' => 'Bookable pavilions for gatherings, with prep kitchens and shaded courtyards.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-library', 'title' => 'Residents\' Club', 'description' => "Clubhouse with lounge, library nook, and private event space."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-cloud', 'title' => 'Sky Garden', 'description' => "Rooftop garden with panoramic views — the development's quiet centerpiece."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-user-group', 'title' => 'Children\'s Play', 'description' => "Thoughtfully-designed play area, shaded and safety-rated."],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-24',
            ]),
            $this->block('content_block', [
                'title' => 'Location — [Neighborhood Name]',
                'body' => "<p>[Neighborhood Name] is [a single evocative sentence — e.g. \"one of the last low-rise enclaves of mature District [#]\" / \"where the city's formal streets give way to green\"]. The address is [X] minutes to [MRT Station] and [Y] minutes to the CBD by car.</p><p>Good neighbors within walking distance: [school], [park], [café/bakery], [provision shop], [clinic]. The daily convenience most new developments promise and rarely deliver.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            // Requires the TallCMS Pro plugin. Swap coords/address/marker_title.
            $this->block('pro-map', [
                'heading' => 'The site',
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
                'padding' => 'py-24',
            ]),
            $this->block('stats', [
                'heading' => 'Within reach',
                'stats' => [
                    ['value' => '[X] min', 'label' => 'walk to [MRT]'],
                    ['value' => '[X] min', 'label' => 'drive to CBD'],
                    ['value' => '[X]', 'label' => 'top schools within 2km'],
                    ['value' => '[X] min', 'label' => 'to Changi Airport'],
                ],
                'columns' => '4',
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
            $this->block('content_block', [
                'title' => 'About [Developer]',
                'body' => "<p><strong>[Developer]</strong> has been shaping Singapore's residential landscape since [Year]. Past projects include [Project 1], [Project 2], and [Project 3] — each defined by [common trait: attention to proportion / thoughtful amenity planning / site-specific design]. [Project Name] is their [Xth] development in [District/Area] and continues that lineage.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-24',
            ]),
            $this->block('cta', [
                'title' => 'Ready to view?',
                'description' => "Showflat previews are by appointment. WhatsApp us directly for the earliest available slot.",
                'button_text' => 'WhatsApp to book',
                'button_link_type' => 'external',
                'button_url' => 'https://wa.me/659999999?text=Hi%20I%20would%20like%20to%20book%20a%20showflat%20preview%20for%20[Project%20Name]',
                'button_microcopy' => 'Replace 659999999 with your phone and update the project name in the text= parameter.',
                'button_variant' => 'btn-success',
                'button_size' => 'btn-lg',
                'background' => 'bg-neutral',
                'padding' => 'py-24',
            ]),
            $this->block('content_block', [
                'title' => 'Disclaimer',
                'body' => "<p style=\"font-size: 0.9em; color: #666;\">This is an independent marketing website operated by <strong>[Agent Name]</strong>, a licensed salesperson (CEA Reg: [License #]) with <strong>[Agency Name]</strong>. This site is not the official developer's site and is not an official sales or marketing channel of [Developer].</p><p style=\"font-size: 0.9em; color: #666;\">All images are artist's impressions for illustrative purposes only. Specifications, unit mixes, prices, and availability are subject to change without notice. For binding information, refer to the official material at the showflat.</p><p style=\"font-size: 0.9em; color: #666;\">See the full <a href=\"/disclaimer\">disclaimer</a>.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function thankYouContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Thank you.</p>',
                'subheading' => "<p>Your details are in. We'll send the brochure and priority preview details within 24 hours. If it's urgent, WhatsApp us at [Phone].</p>",
                'button_text' => 'Back to the residence',
                'button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[60vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'What happens next',
                'body' => "<p>1. Check your inbox — the brochure is on its way.</p><p>2. A member of our team will reach out within 24 hours to answer questions and offer a priority showflat slot.</p><p>3. If you'd like to skip ahead, WhatsApp us directly at [Phone].</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-24',
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
                'body' => "<p>This website is owned and operated by <strong>[Agent Name]</strong>, a licensed salesperson registered with the Council for Estate Agencies (CEA Reg: <strong>[License #]</strong>) with <strong>[Agency Name]</strong> (Agency CEA Licence: [Agency License #]).</p><p>This is <strong>not</strong> the official developer's website and is <strong>not</strong> an official sales or marketing channel of [Developer]. We market [Project Name] as a licensed co-broker under the Estate Agents Act.</p><h3>Artist's Impressions</h3><p>All renderings, floor plans, site plans, images, and video content on this website are artist's impressions for illustrative purposes only. Actual building form, specifications, finishes, and external views may differ from what is shown.</p><h3>Pricing and availability</h3><p>Prices, unit mixes, and availability are correct at time of publication but are subject to change without notice at the developer's discretion. Official and binding pricing is provided only at the showflat or in the official sales material provided at the point of sale.</p><h3>Data protection</h3><p>When you submit a form on this website, your information is used solely to respond to your enquiry about [Project Name]. Your data is shared with the developer's authorized marketing agents for this purpose and is not sold or shared with unrelated third parties. You may request deletion of your data at any time by contacting us.</p><h3>No guarantees</h3><p>Any commentary about investment potential, rental yield, capital appreciation, or similar forward-looking statements is provided as general information and should not be taken as personalized financial or investment advice. Consult your own advisors for decisions about your specific circumstances.</p><h3>Contact</h3><p>For questions about this website or our marketing of [Project Name], use the form on the <a href=\"/\">project page</a> or WhatsApp us at [Phone].</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-24',
            ]),
        ]);
    }
}
