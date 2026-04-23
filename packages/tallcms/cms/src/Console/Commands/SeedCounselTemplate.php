<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed the "Counsel" template — a professional law firm website.
 *
 * Tone is deliberately conservative and trust-building, the way law
 * firm websites actually need to read. No overclaiming ("best lawyer"
 * or specific case-result dollar amounts) because most jurisdictions'
 * bar advertising rules prohibit it. Copy is factual, restrained, and
 * focused on practice areas + attorney credentials + consultation
 * booking as the primary conversion.
 *
 * Scope: solo practitioners and small-to-mid firms. A boutique
 * general-practice firm, a specialist (family, employment, immigration,
 * corporate), or a small partnership. Not designed for large
 * multi-office firms with hundreds of attorneys — those need a
 * different scale of site.
 *
 * Copy uses bracketed placeholders ([Firm Name], [Attorney Name],
 * [Practice Area], [Bar Admission], etc.) for search-and-replace.
 */
class SeedCounselTemplate extends Command
{
    protected $signature = 'tallcms:seed-counsel-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Counsel template and recreate}';

    protected $description = 'Seed the Counsel template — a professional law firm website';

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

        $existing = DB::table('tallcms_sites')->where('domain', 'counsel.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn('Counsel template already exists (site id '.$existing->id.'). Use --force to recreate.');

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info('Removed existing Counsel template.');
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info("Created site: Counsel (id {$siteId})");

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info('Created '.count($pageIds).' pages.');

        $this->createMenu($siteId, $pageIds);
        $this->components->info('Created primary menu.');

        $this->createPosts($siteId, $ownerId);
        $this->components->info('Created 3 seed insight posts.');

        $this->newLine();
        $this->components->info('⚖️ Counsel template ready. It now appears in the Template Gallery.');

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
            'name' => 'Counsel',
            'domain' => 'counsel.template',
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
            'practice-areas' => ['title' => 'Practice Areas', 'content' => $this->practiceAreasContent()],
            'attorneys' => ['title' => 'Attorneys', 'content' => $this->attorneysContent()],
            'insights' => ['title' => 'Insights', 'content' => $this->insightsContent()],
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
            'practice-areas' => 'Practice Areas',
            'attorneys' => 'Attorneys',
            'insights' => 'Insights',
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

    protected function createPosts(int $siteId, int $ownerId): void
    {
        $posts = [
            [
                'title' => 'What to expect from your first consultation',
                'slug' => 'first-consultation',
                'excerpt' => 'Before you book, here is what a consultation with our firm looks like — what to bring, what we discuss, and what you leave with.',
                'content' => $this->firstConsultationPost(),
            ],
            [
                'title' => 'How to choose the right attorney for your situation',
                'slug' => 'choosing-an-attorney',
                'excerpt' => 'Not every firm is right for every matter. Here is how we think about fit — for our own referrals and for clients choosing us.',
                'content' => $this->choosingAttorneyPost(),
            ],
            [
                'title' => 'How our fees work: transparency over anxiety',
                'slug' => 'how-our-fees-work',
                'excerpt' => 'A plain-English explanation of how we bill, when we bill, and why our engagement letters read the way they do.',
                'content' => $this->feesPost(),
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
                'published_at' => now()->subDays($i * 14),
                'created_at' => now()->subDays($i * 14),
                'updated_at' => now()->subDays($i * 14),
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
                'heading' => '<p>[Firm Name]</p>',
                'subheading' => "<p>[A short, factual tagline — e.g. \"Practical legal counsel for families and small businesses in [City], since [Year].\"]</p>",
                'button_text' => 'Schedule a consultation',
                'button_link_type' => 'custom',
                'button_url' => '#contact',
                'secondary_button_text' => 'Our practice areas',
                'secondary_button_link_type' => 'page',
                'layout' => 'centered',
                'height' => 'min-h-[70vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->block('content_block', [
                'title' => 'About [Firm Name]',
                'body' => "<p>[Firm Name] is a [size descriptor — e.g. \"boutique\" / \"mid-size\"] law firm based in [City]. We advise [client focus — e.g. \"individuals, families, and small businesses\"] on matters in [Practice Area 1], [Practice Area 2], and [Practice Area 3].</p><p>We built the firm on a simple idea: good legal counsel should be honest, accessible, and billed predictably. We tell our clients what we'd tell a family member in the same situation — and we tell it to them in plain English, not in legalese that serves no one.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'Practice areas',
                'subheading' => 'Focused expertise in the matters where we can make the most difference.',
                'features' => [
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-home', 'title' => '[Family Law]', 'description' => "Divorce, custody, matrimonial agreements, adoption. We handle sensitive matters with discretion and clear communication."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-building-office', 'title' => '[Corporate & Commercial]', 'description' => 'Entity formation, shareholder agreements, commercial contracts, M&A advisory for small-to-mid businesses.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-document-text', 'title' => '[Wills & Estate Planning]', 'description' => "Wills, trusts, lasting powers of attorney, estate administration. Thoughtful planning to protect what you've built."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-scale', 'title' => '[Civil Litigation]', 'description' => 'Commercial disputes, contract enforcement, debt recovery. When negotiation fails, we prepare cases methodically.'],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-briefcase', 'title' => '[Employment Law]', 'description' => "Contracts, workplace disputes, wrongful termination, non-competes. Advising employers and employees alike."],
                    ['icon_type' => 'heroicon', 'icon' => 'heroicon-o-globe-alt', 'title' => '[Immigration]', 'description' => "Employment passes, PR applications, citizenship, appeals. [If you don't offer this, swap for your actual sixth area or delete.]"],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('content_block', [
                'title' => 'How we work',
                'body' => "<p>Our approach rests on three commitments:</p><p><strong>Honest assessment.</strong> We tell you whether you have a strong case, a marginal one, or no case at all. A lawyer who promises only what you want to hear is a lawyer who doesn't serve you well.</p><p><strong>Predictable fees.</strong> We quote scope and rates upfront. We flag when work exceeds the quote before the bill lands. No surprises at month-end.</p><p><strong>Responsive communication.</strong> We return calls the same business day and emails within 24 hours. If we can't take your matter, we tell you quickly and refer you to someone who can.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('team', [
                'heading' => 'Our attorneys',
                'subheading' => 'Experienced counsel — approachable in conversation, rigorous in execution.',
                'members' => [
                    [
                        'name' => '[Lead Partner Name]',
                        'role' => 'Managing Partner · [Bar Admission Year] · [Jurisdictions]',
                        'bio' => '[Two-sentence bio. Practice focus + notable credentials or background. Avoid overclaiming.]',
                    ],
                    [
                        'name' => '[Partner Name]',
                        'role' => 'Partner · [Area of Focus]',
                        'bio' => '[Bio highlighting their specialty and what they bring to clients.]',
                    ],
                    [
                        'name' => '[Associate Name]',
                        'role' => 'Senior Associate · [Area of Focus]',
                        'bio' => '[Bio including qualifications and notable matters handled.]',
                    ],
                ],
                'columns' => '3',
                'card_style' => 'bg-base-100 shadow',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('testimonials', [
                'heading' => 'What clients say',
                'subheading' => 'Quoted with permission. Identifying details redacted where appropriate.',
                'testimonials' => [
                    [
                        'quote' => '[Firm Name] took on our matrimonial matter with skill and discretion. Clear communication throughout, and the result we hoped for.',
                        'author_name' => 'M., Partner',
                        'author_title' => 'Matrimonial matter, [Year]',
                    ],
                    [
                        'quote' => 'We engaged [Firm Name] for our shareholder agreement and have continued to use them for every commercial contract since. Responsive and practical.',
                        'author_name' => 'R., Director',
                        'author_title' => 'SME corporate client',
                    ],
                    [
                        'quote' => 'They said upfront our case was marginal, quoted a reasonable fixed fee to give it a proper try, and won. That was five years ago; still our firm of choice.',
                        'author_name' => 'J., Business owner',
                        'author_title' => 'Civil litigation',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('posts', [
                'posts_count' => 3,
                'show_image' => false,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => 'Schedule an initial consultation',
                'description' => 'A 30-minute consultation costs [$X / is complimentary] and gives us both a chance to understand whether we are the right firm for your matter.',
                'button_text' => 'Schedule consultation',
                'button_link_type' => 'custom',
                'button_url' => '#contact',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
            $this->block('contact_form', [
                'title' => 'Request a consultation',
                'description' => "Share a brief description of your matter. We will respond within one business day to confirm whether we can assist and book a consultation if appropriate.",
                'anchor_id' => 'contact',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Full name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'required' => true, 'options' => []],
                    ['name' => 'matter_type', 'type' => 'select', 'label' => 'Area of law', 'required' => true, 'options' => ['Family Law', 'Corporate & Commercial', 'Wills & Estate Planning', 'Civil Litigation', 'Employment Law', 'Immigration', 'Other / Not sure']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Brief description of the matter', 'required' => true, 'options' => []],
                ],
                'submit_button_text' => 'Submit request',
                'success_message' => 'Thank you. We will respond within one business day.',
                'auto_reply_message' => "Thank you for reaching out to [Firm Name]. Your request has been received and one of our attorneys will respond within one business day. Please note: submitting this form does not create an attorney-client relationship. If your matter is urgent, call us at [Phone]. — [Firm Name]",
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function practiceAreasContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Practice areas</p>',
                'subheading' => '<p>The matters we handle, the clients we serve, and the approach we take in each.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => '[Family Law]',
                'body' => "<p>Divorce, custody, matrimonial agreements, and adoption. These are matters where the law meets the hardest moments in our clients' lives. We work with discretion, plainly-worded advice, and a commitment to resolving disputes through negotiation wherever possible — and to litigating capably when not.</p><p><strong>Typical engagements:</strong> uncontested and contested divorce, custody and access, maintenance agreements, variation of court orders, deed of separation, prenuptial and postnuptial agreements, adoption petitions.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->block('content_block', [
                'title' => '[Corporate & Commercial]',
                'body' => "<p>We advise founders, owner-operators, and small-to-mid businesses on the full lifecycle of a company — from incorporation through exit. Our focus is practical transactional work done at reasonable fees, not large-firm theater.</p><p><strong>Typical engagements:</strong> entity formation (Pte Ltd, LLP, partnership), shareholder agreements, founders' agreements, commercial contracts, service agreements, NDAs, employment contracts, M&A (small-cap), share transfers, due diligence.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
            $this->block('content_block', [
                'title' => '[Wills & Estate Planning]',
                'body' => "<p>A will is the least expensive, highest-leverage legal document most people will ever sign. We help clients think about who they want to provide for, how, and what contingencies to plan for — and we draft plainly-worded documents that will hold up.</p><p><strong>Typical engagements:</strong> simple and mirror wills, trust deeds, lasting powers of attorney, advance medical directives, estate administration (probate and letters of administration), trust administration.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->block('content_block', [
                'title' => '[Civil Litigation]',
                'body' => "<p>Commercial disputes, debt recovery, contract enforcement, and tort claims. We work hard to resolve matters through negotiation — it is almost always faster, cheaper, and more predictable than litigation — but we prepare every case as if trial were certain.</p><p><strong>Typical engagements:</strong> breach of contract claims, debt recovery, injunctions and Anton Piller orders, shareholder disputes, defamation, negligence claims. Both prosecution and defence.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
            $this->block('cta', [
                'title' => 'Not sure which area covers your matter?',
                'description' => "Tell us a bit about your situation and we'll confirm whether we can assist — or refer you to a firm that can.",
                'button_text' => 'Schedule consultation',
                'button_link_type' => 'page',
                'button_variant' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function attorneysContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Our attorneys</p>',
                'subheading' => '<p>The people who will handle your matter — their training, focus, and how to reach them.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('team', [
                'heading' => 'Attorneys',
                'members' => [
                    [
                        'name' => '[Lead Partner Name]',
                        'role' => 'Managing Partner',
                        'bio' => "<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [One paragraph on background, notable experience, and approach — factual, not marketing-voice.]",
                    ],
                    [
                        'name' => '[Partner Name]',
                        'role' => 'Partner',
                        'bio' => "<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [Background paragraph.]",
                    ],
                    [
                        'name' => '[Senior Associate Name]',
                        'role' => 'Senior Associate',
                        'bio' => "<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [Background paragraph.]",
                    ],
                    [
                        'name' => '[Associate Name]',
                        'role' => 'Associate',
                        'bio' => "<strong>Practice focus:</strong> [Areas]. <strong>Admitted:</strong> [Year], [Jurisdiction]. <strong>Education:</strong> [LL.B./J.D.], [University], [Year]. [Background paragraph.]",
                    ],
                ],
                'columns' => '2',
                'card_style' => 'bg-base-100 shadow',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => 'Want to work with a specific attorney?',
                'description' => "Mention them by name when you book — we'll honor the request whenever possible.",
                'button_text' => 'Schedule consultation',
                'button_link_type' => 'page',
                'button_variant' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function insightsContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Insights</p>',
                'subheading' => '<p>Plain-English commentary on the questions our clients most commonly ask. Not legal advice — consult us directly for that.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('posts', [
                'posts_count' => 20,
                'show_image' => false,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => true,
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
                'heading' => '<p>Contact [Firm Name]</p>',
                'subheading' => '<p>We respond within one business day. For urgent matters, please call.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'Our office',
                'body' => "<p><strong>Address:</strong><br>[Firm Name]<br>[Building / Floor]<br>[Street Address]<br>[Postal Code], [City]</p><p><strong>Office hours:</strong> Mon–Fri, 9:00am–6:00pm<br><strong>Phone:</strong> [Main Phone]<br><strong>General enquiries:</strong> [general@firm.com]<br><strong>Urgent matters:</strong> [Emergency Phone] (existing clients only)</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            // Requires the TallCMS Pro plugin.
            $this->block('pro-map', [
                'heading' => 'Find us',
                'subheading' => '[Office address with postal code]',
                'latitude' => '1.3521',
                'longitude' => '103.8198',
                'address' => '[Office address with postal code]',
                'marker_title' => '[Firm Name]',
                'contact_info' => "Office hours: Mon-Fri 9am-6pm\nPhone: [Main Phone]",
                'provider' => 'openstreetmap',
                'zoom' => 16,
                'height' => 'lg',
                'show_marker' => true,
                'scrollwheel_zoom' => false,
                'rounded' => true,
                'background' => 'bg-base-200',
                'padding' => 'py-12',
            ]),
            $this->block('contact_form', [
                'title' => 'Request a consultation',
                'description' => "Please do not include confidential or time-sensitive information in this form. Submitting this form does not create an attorney-client relationship.",
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Full name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'required' => true, 'options' => []],
                    ['name' => 'matter_type', 'type' => 'select', 'label' => 'Area of law', 'required' => true, 'options' => ['Family Law', 'Corporate & Commercial', 'Wills & Estate Planning', 'Civil Litigation', 'Employment Law', 'Immigration', 'Other / Not sure']],
                    ['name' => 'existing_client', 'type' => 'select', 'label' => 'Are you an existing client?', 'required' => true, 'options' => ['No — new enquiry', 'Yes']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Brief description of the matter', 'required' => true, 'options' => []],
                ],
                'submit_button_text' => 'Submit request',
                'success_message' => "Thank you. We will respond within one business day.",
                'auto_reply_message' => "Thank you for contacting [Firm Name]. We have received your request and one of our attorneys will respond within one business day. Please note: submitting this form does not create an attorney-client relationship; an engagement letter will be sent to you if we proceed. For urgent matters, please call us at [Phone]. — [Firm Name]",
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function firstConsultationPost(): string
    {
        return implode("\n", [
            $this->paragraph("Most people who book a consultation with us are doing so for the first time. Here is what to expect, so you arrive prepared and leave with clarity."),
            $this->paragraph("<strong>Before the meeting</strong>"),
            $this->paragraph("When you book, we will send a short confirmation with the attorney you'll meet, a firm address, and a pre-consultation form asking for basic context about your matter. Please fill it in — even briefly — because it helps us prepare and makes the hour more useful to you."),
            $this->paragraph("Bring copies (not originals) of any documents relevant to your matter: contracts, correspondence, court papers, medical records, bank statements, emails. If in doubt, bring it."),
            $this->paragraph("<strong>During the meeting</strong>"),
            $this->paragraph("Our consultations are 60 minutes. The first 15 minutes are about understanding your situation: what has happened, what you are trying to achieve, and what you have already tried. The remainder is our assessment — what the law says, where the risks are, what your options look like, and what we would recommend."),
            $this->paragraph("We try hard to be honest about strong cases, marginal ones, and the ones where you don't need us at all. If we recommend a different firm for your matter, we will tell you in plain terms why."),
            $this->paragraph("<strong>After the meeting</strong>"),
            $this->paragraph("If we're a good fit and you want to proceed, we send you an engagement letter within one business day. It spells out scope, fees, and expected timeline. You sign it, we open a file, work begins."),
            $this->paragraph("If we're not a good fit — or if after thinking about it you'd like to speak to another firm — that is fine too. The consultation stands on its own."),
            $this->paragraph("Book a consultation through the <a href=\"/contact\">contact form</a> or call us at [Phone]."),
        ]);
    }

    protected function choosingAttorneyPost(): string
    {
        return implode("\n", [
            $this->paragraph("We refer work to other firms weekly — when a matter falls outside our practice areas, when the scale is wrong for us, or when a potential client and our firm are not a good fit. Over the years this has clarified for us what makes a good match."),
            $this->paragraph("Here is how we think about it — useful whether you are choosing us or someone else."),
            $this->paragraph("<strong>Subject-matter fit.</strong> Legal work is specialized. A corporate-commercial firm that occasionally takes on a divorce is almost certainly weaker at it than a family-law specialist. For anything significant, find a firm that does this type of work every week — not one where your matter is unusual."),
            $this->paragraph("<strong>Scale fit.</strong> A large firm charging $800/hour is not the right fit for a $50,000 employment dispute. A solo practitioner may not have the bench depth to take on a $50 million commercial case. Ask candidly how your matter compares to their typical engagement. The answer matters."),
            $this->paragraph("<strong>Fee structure fit.</strong> Hourly, fixed fee, contingency, retainer — each has trade-offs. A fee structure that works for routine contract work (fixed fee) is the wrong structure for open-ended litigation (hourly with periodic updates). Ask them to explain what they typically do for matters like yours and why."),
            $this->paragraph("<strong>Communication fit.</strong> If you like daily updates, hire a firm that provides them. If you prefer a monthly check-in, say so. Mismatched communication expectations are the most common source of client dissatisfaction, in our experience. Raise it in the first meeting."),
            $this->paragraph("<strong>Person fit.</strong> You are going to be talking about uncomfortable things — money, family, business problems — with this person, possibly for months. If you don't trust them after 60 minutes of conversation, keep looking. Good lawyers are not rare."),
            $this->paragraph("We try to be honest with prospective clients when we're not the right firm. Any good firm should do the same."),
        ]);
    }

    protected function feesPost(): string
    {
        return implode("\n", [
            $this->paragraph("Clients rarely enjoy discussing legal fees. We try to make it simpler than most firms do."),
            $this->paragraph("<strong>How we structure fees</strong>"),
            $this->paragraph("We use four fee structures, depending on the matter:"),
            $this->paragraph("<strong>1. Fixed fee.</strong> For well-defined engagements — a simple will, a standard shareholder agreement, a trademark filing — we quote a fixed fee upfront. You know the cost before we start. If the scope changes, we tell you before doing the extra work."),
            $this->paragraph("<strong>2. Hourly.</strong> For matters where scope is hard to predict — most litigation, complex transactions, ongoing advisory — we bill hourly, at the rate listed in the engagement letter. We send itemized invoices monthly with time entries you can review."),
            $this->paragraph("<strong>3. Retainer.</strong> For ongoing client relationships, we bill monthly for a block of hours. Unused hours roll into the following month; excess hours bill at our standard hourly rate."),
            $this->paragraph("<strong>4. Contingency / conditional fee.</strong> Only for specific matters (e.g. certain personal injury or debt recovery claims) and only where the law permits. Not available for most family or corporate work."),
            $this->paragraph("<strong>What happens if the quote is wrong</strong>"),
            $this->paragraph("Sometimes a matter grows beyond what was foreseeable at the outset. When that happens, we stop, tell you, and ask — before we do the extra work. We do not present surprise charges at month-end. If we underestimated, we will often absorb part of the overrun; we consider it our mistake to price realistically at the outset."),
            $this->paragraph("<strong>What if you can't afford us</strong>"),
            $this->paragraph("We refer at cost or pro bono in certain circumstances, particularly for family-law matters involving domestic violence or custody issues for clients of limited means. Ask during the consultation. There is no shame in it, and we would rather refer you than take work you cannot pay for."),
            $this->paragraph("If you would like to discuss fees for your specific matter, book a <a href=\"/contact\">consultation</a> — we will give you a realistic quote or range."),
        ]);
    }
}
