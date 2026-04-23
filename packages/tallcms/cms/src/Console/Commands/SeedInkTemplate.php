<?php

declare(strict_types=1);

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seed the "Ink" template — a blog starter for writers, essayists,
 * and thought-leadership-focused creators.
 *
 * Differs from Keystone (realtor) by being content-first rather than
 * service-first: no stats, no testimonials, no FAQ. Home page centers
 * the post feed and a newsletter CTA. Seeded with 5 posts so the
 * cloned site feels populated out of the box.
 *
 * Scope: a professional-looking blog — personal, opinion, essays,
 * tech writing, newsletter companion sites. Not optimized for
 * e-commerce, portfolios, or multi-author publications (those get
 * their own templates eventually).
 *
 * Copy uses bracketed placeholders ([Your Name], [Topic], etc.) so
 * authors search-and-replace after cloning.
 */
class SeedInkTemplate extends Command
{
    protected $signature = 'tallcms:seed-ink-template
                            {--owner= : User ID to own the template site (defaults to first super_admin)}
                            {--force : Delete any existing Ink template and recreate}';

    protected $description = 'Seed the Ink blog template site (home, about, archive, contact + 5 seed posts)';

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

        $existing = DB::table('tallcms_sites')->where('domain', 'ink.template')->first();
        if ($existing) {
            if (! $this->option('force')) {
                $this->components->warn('Ink template already exists (site id '.$existing->id.'). Use --force to recreate.');

                return self::SUCCESS;
            }
            $this->deleteSite((int) $existing->id);
            $this->components->info('Removed existing Ink template.');
        }

        $siteId = $this->createSite($ownerId);
        $this->components->info("Created site: Ink (id {$siteId})");

        $pageIds = $this->createPages($siteId, $ownerId);
        $this->components->info('Created '.count($pageIds).' pages.');

        $this->createMenu($siteId, $pageIds);
        $this->components->info('Created primary menu.');

        $this->createPosts($siteId, $ownerId);
        $this->components->info('Created 5 seed posts.');

        $this->newLine();
        $this->components->info('✍️  Ink template ready. It now appears in the Template Gallery.');

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
            'name' => 'Ink',
            'domain' => 'ink.template',
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
            'archive' => [
                'title' => 'Archive',
                'content' => $this->archiveContent(),
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
        foreach (['home' => 'Home', 'about' => 'About', 'archive' => 'Archive', 'contact' => 'Contact'] as $slug => $label) {
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
                'title' => 'Welcome — what this blog is about',
                'slug' => 'welcome',
                'excerpt' => "A quick intro to what you'll find here, who it's for, and how often new posts land.",
                'content' => $this->welcomePost(),
            ],
            [
                'title' => 'The reading list I return to',
                'slug' => 'reading-list',
                'excerpt' => 'A running list of the books and essays that have most shaped how I think. Updated quarterly.',
                'content' => $this->readingListPost(),
            ],
            [
                'title' => 'On thinking in systems',
                'slug' => 'thinking-in-systems',
                'excerpt' => "Why I try to reason about systems, not symptoms — and the failure modes that forces me to confront.",
                'content' => $this->systemsPost(),
            ],
            [
                'title' => 'A simpler writing process',
                'slug' => 'writing-process',
                'excerpt' => "After a decade of trying fancier systems, I've ended up with a boringly simple one. Here it is.",
                'content' => $this->writingProcessPost(),
            ],
            [
                'title' => 'What I got wrong about productivity',
                'slug' => 'productivity-wrong',
                'excerpt' => "Five productivity beliefs I held firmly — and eventually abandoned after they didn't survive contact with real work.",
                'content' => $this->productivityPost(),
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
                'published_at' => now()->subDays($i * 10),
                'created_at' => now()->subDays($i * 10),
                'updated_at' => now()->subDays($i * 10),
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
                'heading' => '<p>I write about [Topic].</p>',
                'subheading' => "<p>Essays, field notes, and occasional long-form. New posts every other week. Subscribe to get them in your inbox.</p>",
                'button_text' => 'Subscribe',
                'button_link_type' => 'custom',
                'button_url' => '#subscribe',
                'secondary_button_text' => 'Read recent posts',
                'secondary_button_link_type' => 'custom',
                'secondary_button_url' => '#recent',
                'layout' => 'centered',
                'height' => 'min-h-[60vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-gradient-to-br from-neutral to-base-300',
                'overlay_opacity' => 0,
                'button_variant' => 'btn-primary',
                'secondary_button_variant' => 'btn-ghost text-white hover:bg-white/20',
                'button_size' => 'btn-lg',
            ]),
            $this->block('posts', [
                'posts_count' => 6,
                'show_image' => true,
                'show_excerpt' => true,
                'show_date' => true,
                'show_author' => false,
                'show_categories' => false,
                'show_read_more' => true,
                'layout' => 'grid',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
                'anchor_id' => 'recent',
            ]),
            $this->block('content_block', [
                'title' => 'About the writer',
                'body' => "<p>I'm [Your Name] — a [role / short descriptor] based in [City]. I've been writing here since [Year], mostly about [Topic 1], [Topic 2], and whatever else I can't stop thinking about.</p><p>If you've been sent here by a friend, welcome. If you arrived some other way — that works too.</p>",
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => 'Get new posts in your inbox',
                'description' => 'No paywalls, no tracking, no algorithm. Every other Tuesday.',
                'button_text' => 'Subscribe',
                'button_link_type' => 'url',
                'button_url' => '#',
                'button_microcopy' => 'Swap this link for your newsletter provider (Buttondown, ConvertKit, Substack, etc.)',
                'button_variant' => 'btn-primary',
                'button_size' => 'btn-lg',
                'background' => 'bg-primary',
                'padding' => 'py-16',
                'anchor_id' => 'subscribe',
            ]),
        ]);
    }

    protected function aboutContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>About [Your Name]</p>',
                'subheading' => '<p>Writer, reader, occasional rambler. Here since [Year].</p>',
                'layout' => 'centered',
                'height' => 'min-h-[40vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'Hi, I am [Your Name]',
                'body' => "<p>I'm a [role / short descriptor] based in [City]. I started writing here in [Year] because I had thoughts on [Topic] that wouldn't fit in a tweet and nobody was asking me to write them as a column.</p><p>I still have those thoughts. I write them here.</p><p>Mostly the posts are about [Topic 1], [Topic 2], and [Topic 3] — with occasional detours into anything I find genuinely interesting. I try to be honest about what I don't know, and to write like I'm talking to a friend who has 20 minutes and is willing to listen.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
            $this->block('features', [
                'heading' => 'What I write about',
                'features' => [
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-light-bulb',
                        'title' => '[Topic 1]',
                        'description' => 'A short description of the topic and why it matters. Two or three sentences that tell a reader whether this blog is for them.',
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-book-open',
                        'title' => '[Topic 2]',
                        'description' => "Another topic you regularly return to. Readers come here for this too; say what the angle is.",
                    ],
                    [
                        'icon_type' => 'heroicon',
                        'icon' => 'heroicon-o-map',
                        'title' => '[Topic 3]',
                        'description' => 'Your third recurring theme. Optional — delete the block if you only have two.',
                    ],
                ],
                'columns' => '3',
                'background' => 'bg-base-200',
                'padding' => 'py-16',
            ]),
            $this->block('cta', [
                'title' => 'Stay in the loop',
                'description' => "Get new posts in your inbox — or follow along wherever you prefer.",
                'button_text' => 'Subscribe',
                'button_link_type' => 'url',
                'button_url' => '#',
                'button_microcopy' => 'Swap this link for your newsletter signup URL',
                'button_variant' => 'btn-primary',
                'background' => 'bg-primary',
                'padding' => 'py-16',
            ]),
        ]);
    }

    protected function archiveContent(): string
    {
        return implode("\n", [
            $this->block('hero', [
                'heading' => '<p>Archive</p>',
                'subheading' => '<p>Every post, newest first.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[35vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('posts', [
                'posts_count' => 20,
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
                'heading' => '<p>Get in touch</p>',
                'subheading' => '<p>Reader questions, speaking invitations, collaborations, or just to say hi.</p>',
                'layout' => 'centered',
                'height' => 'min-h-[35vh]',
                'text_alignment' => 'text-center',
                'background_color' => 'bg-base-200',
                'overlay_opacity' => 0,
            ]),
            $this->block('content_block', [
                'title' => 'Reach out',
                'body' => "<p>The fastest way to get in touch is the form below — it goes straight to my inbox and I answer most messages within a week.</p><p>For something public (a reply, a disagreement, a reading recommendation), I also enjoy getting them as letters to the editor and may quote you in a future post. Let me know if you'd rather stay anonymous.</p>",
                'background' => 'bg-base-100',
                'padding' => 'py-12',
            ]),
            $this->block('contact_form', [
                'title' => 'Send a note',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'options' => []],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
                    ['name' => 'intent', 'type' => 'select', 'label' => "What's this about?", 'required' => true, 'options' => ['Reader question', 'Speaking invitation', 'Collaboration', 'Just saying hi', 'Other']],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'options' => []],
                ],
                'submit_button_text' => 'Send',
                'success_message' => "Thanks — I'll reply within a week.",
                'auto_reply_message' => "Thanks for writing. I read every message and aim to reply within a week, though sometimes life gets in the way. — [Your Name]",
                'button_style' => 'btn-primary',
                'background' => 'bg-base-100',
                'padding' => 'py-16',
            ]),
        ]);
    }

    // --- Post bodies --------------------------------------------------------

    protected function welcomePost(): string
    {
        return implode("\n", [
            $this->paragraph("If you're reading this, you've stumbled onto my small corner of the internet. Welcome."),
            $this->paragraph("This blog is where I write about <strong>[Topic 1]</strong>, <strong>[Topic 2]</strong>, and occasionally <strong>[Topic 3]</strong>. It's a mix of personal essays, field notes from the work I do, and the occasional long-form piece when something's been rattling around in my head for months."),
            $this->paragraph("New posts go out every other Tuesday. You can subscribe to the email version if you'd rather not remember to check back, or follow the RSS feed if you're the RSS kind."),
            $this->paragraph("A few things to know:"),
            $this->paragraph("<strong>I'm wrong often.</strong> I try to flag when I'm speculating versus when I'm sure. I also try to come back and correct posts when I change my mind — with a note at the top rather than silently."),
            $this->paragraph("<strong>I write slowly on purpose.</strong> These are meant to be read, not skimmed. Most posts are 800–2,500 words. If that's too long for your attention, that's a reasonable reason to close the tab."),
            $this->paragraph("<strong>I love getting replies.</strong> The best part of writing in public is the conversations. Hit the <a href=\"/contact\">contact page</a> if you want to push back, share a better example, or recommend something."),
            $this->paragraph("That's it. Thanks for being here."),
        ]);
    }

    protected function readingListPost(): string
    {
        return implode("\n", [
            $this->paragraph("A running list of the books and essays I keep returning to. Not a definitive \"best of\" — just the ones that I've actually re-read or referenced in my own work multiple times."),
            $this->paragraph("<strong>Books</strong>"),
            $this->paragraph("<em>Thinking, Fast and Slow</em> by Daniel Kahneman — The book that made me distrust my own intuition in specific, useful ways. System 1 / System 2 is now how I think about almost any decision."),
            $this->paragraph("<em>The Design of Everyday Things</em> by Don Norman — Still the clearest explanation of why bad design is everywhere and what to do about it. Re-read every few years."),
            $this->paragraph("<em>Mindset</em> by Carol Dweck — Growth-mindset-as-self-help is a cliché at this point, but the underlying research is real and the book is better than its pop reputation."),
            $this->paragraph("<strong>Essays</strong>"),
            $this->paragraph("Paul Graham's \"<em>How to Do Great Work</em>\" — Probably the most re-read essay of the last few years for me. Long, specific, and unromantic about what it takes."),
            $this->paragraph("Maria Popova's \"<em>Learning, Presence, and the Art of Self-Renewal</em>\" — A beautiful piece on why we need to periodically re-read things we already know."),
            $this->paragraph("<strong>Substacks / blogs</strong>"),
            $this->paragraph("I read these weekly: [Writer 1]'s [newsletter/blog], [Writer 2], [Writer 3]. If you like what I write, you'll probably like them."),
            $this->paragraph("I'll update this list every quarter. Got something I should be reading? <a href=\"/contact\">Tell me</a>."),
        ]);
    }

    protected function systemsPost(): string
    {
        return implode("\n", [
            $this->paragraph("Most of the advice you read tells you to fix the symptom. Your coworker dropped a task → have a tough conversation. Your customer churned → call them. Your inbox is overflowing → declare inbox zero."),
            $this->paragraph("Sometimes the symptom is the problem. Most of the time it isn't."),
            $this->paragraph("The symptom is a signal. What's generating the signal is the system. If you keep fixing symptoms, you'll keep seeing new ones — because the system hasn't changed."),
            $this->paragraph("<strong>A real example.</strong>"),
            $this->paragraph("A few years ago I kept having the same Monday. I'd arrive at work with a long list of things I wanted to accomplish that week. By Wednesday I'd accomplished none of them because I was drowning in Slack messages and calendar invites. Friday I'd feel behind. Monday I'd make a new list. Repeat."),
            $this->paragraph("The symptom: I wasn't getting my list done. The fix-the-symptom advice was everywhere: \"block time on your calendar,\" \"turn off notifications,\" \"say no more often.\""),
            $this->paragraph("The system was: my job had two modes — reactive (handling what came at me) and proactive (what I actually wanted to do). My calendar was structured to serve the reactive mode. I'd wedge the proactive work into whatever gaps were left, which were never big enough to do real work."),
            $this->paragraph("Fixing the symptom (blocking time) helped for a week. Fixing the system — moving proactive work to the morning before reactive work could interrupt it, and moving reactive work to the afternoon so it had a natural deadline — helped for a year."),
            $this->paragraph("<strong>The uncomfortable part.</strong>"),
            $this->paragraph("Thinking in systems usually means admitting that the symptom isn't the problem. Your relationship isn't going to improve by having one more good conversation. Your team isn't going to ship faster by working one more weekend. Your diet isn't going to work by having one more disciplined week."),
            $this->paragraph("Something bigger is generating all those symptoms. Finding it is slower, less satisfying, and more likely to actually help."),
            $this->paragraph("This is why I keep coming back to it — not because it's an easy heuristic, but because it's one of the few that consistently forces me to ask a better question."),
        ]);
    }

    protected function writingProcessPost(): string
    {
        return implode("\n", [
            $this->paragraph("For a long time I was convinced my writing would get better if I found the right system."),
            $this->paragraph("I tried Zettelkasten. I tried Roam. I tried morning pages. I tried the Hemingway approach (stop in the middle of a good sentence). I tried writing at 5am. I tried Scrivener. I tried outlining everything. I tried never outlining."),
            $this->paragraph("After a decade of that, my writing process is: I open a blank document, I write the thing, I edit it twice, I publish it. The tools don't matter. The time of day doesn't matter. The outline helps if the piece is long; otherwise it doesn't."),
            $this->paragraph("<strong>What actually mattered, in rough order:</strong>"),
            $this->paragraph("<strong>Reading more than writing.</strong> Most writing problems are reading problems — specifically, a problem of having nothing interesting to say because you haven't fed the machine enough. When I'm stuck, it's because I haven't read anything new in a while."),
            $this->paragraph("<strong>Editing harder than drafting.</strong> First drafts should be bad. Good writing comes out of the second and third pass, not the first one. I had to internalize this before I could stop deleting first drafts in frustration."),
            $this->paragraph("<strong>Writing in sentences that I could say out loud.</strong> If I can't read a sentence to a friend without feeling pompous, it's wrong. This single rule fixed more of my writing than any tool ever did."),
            $this->paragraph("<strong>Shipping incomplete ideas.</strong> Posts don't need to be complete. They need to be clear about what they are and what they aren't. A post saying \"I'm thinking about X and here's where I am\" is more useful than a half-finished post trying to be definitive."),
            $this->paragraph("<strong>Writing regularly.</strong> Not daily — I tried and hated it. But weekly, with occasional skipped weeks. The habit compounds more than the quantity does."),
            $this->paragraph("That's it. The simplest process I've found, after many expensive detours, is just \"open doc, write, edit, publish.\" I used to think this was boring advice. Now I think it's the point."),
        ]);
    }

    protected function productivityPost(): string
    {
        return implode("\n", [
            $this->paragraph("Five productivity beliefs I held firmly — and eventually abandoned after they didn't survive contact with real work."),
            $this->paragraph("<strong>1. \"If you're not working 60+ hours, you're not serious.\"</strong>"),
            $this->paragraph("I believed this for about five years. It produced a lot of output and not much that I'd call good. The best work I've done came from 40-hour weeks with enough room for walks and sleep."),
            $this->paragraph("<strong>2. \"A perfect morning routine will unlock my potential.\"</strong>"),
            $this->paragraph("I had the ideal morning routine on a printed card next to my bed. I followed it perfectly for about three weeks. Then I traveled, missed a morning, and the whole thing collapsed. My output the week I followed the routine perfectly was indistinguishable from the week I didn't. Routines matter; the <em>particular</em> routine doesn't."),
            $this->paragraph("<strong>3. \"If I'm tired, I need better sleep hygiene.\"</strong>"),
            $this->paragraph("Sometimes. Most of the time I was tired because my schedule was bad, my priorities were wrong, or I was avoiding something hard. Fixing the schedule was more useful than fixing the sleep."),
            $this->paragraph("<strong>4. \"Saying no is the most important skill.\"</strong>"),
            $this->paragraph("It's a good skill. It's not the most important one. The most important one is <em>knowing what to say yes to</em>. Saying no to everything is just a slower way of doing nothing."),
            $this->paragraph("<strong>5. \"If I had 10 more hours a week, I'd get ahead.\"</strong>"),
            $this->paragraph("I got 10 more hours a week twice in my career. Both times I used them the same way I used the other hours. The problem wasn't the number of hours; it was what I did with them. If you're not ahead now, you won't be ahead with more hours — you'll just be ahead less slowly."),
            $this->paragraph("None of these beliefs were stupid. They each had a grain of truth. But they were load-bearing for me in ways that the truth didn't actually support, and abandoning them freed up energy I didn't know I was spending."),
        ]);
    }
}
