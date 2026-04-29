<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tallcms\Pro\Jobs\SyncContactToEmailMarketing;
use Tallcms\Pro\Models\ProSetting;
use Tallcms\Pro\Services\Analytics\AnalyticsManager;
use Tallcms\Pro\Services\Analytics\FathomAnalyticsProvider;
use Tallcms\Pro\Services\Analytics\PlausibleAnalyticsProvider;
use Tallcms\Pro\Services\EmailMarketing\BrevoProvider;
use Tallcms\Pro\Services\EmailMarketing\ConvertKitProvider;
use Tallcms\Pro\Services\EmailMarketing\EmailMarketingManager;
use Tallcms\Pro\Services\EmailMarketing\MailchimpProvider;
use Tests\TestCase;

class ProFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(ProSetting::class)) {
            $this->markTestSkipped('Pro plugin not installed.');
        }

        // Ensure Pro plugin tables exist in test database.
        // Plugin migrations are loaded at runtime by the service provider
        // but RefreshDatabase may not pick them up. Create tables manually.
        $this->ensureProTablesExist();

        Cache::flush();
    }

    protected function ensureProTablesExist(): void
    {
        $schema = \Illuminate\Support\Facades\Schema::class;

        if (! $schema::hasTable('tallcms_pro_settings')) {
            $schema::create('tallcms_pro_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->default('text');
                $table->string('group')->default('general');
                $table->boolean('is_encrypted')->default(false);
                $table->timestamps();
            });
        }

        if (! $schema::hasTable('tallcms_pro_analytics_cache')) {
            $schema::create('tallcms_pro_analytics_cache', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('metric');
                $table->string('period');
                $table->json('value')->nullable();
                $table->timestamp('fetched_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->unique(['provider', 'metric', 'period']);
            });
        }
    }

    // -------------------------------------------------------
    // Analytics: Provider Resolution
    // -------------------------------------------------------

    public function test_analytics_manager_resolves_google_provider(): void
    {
        ProSetting::set('analytics_provider', 'google');
        Cache::flush();

        $manager = new AnalyticsManager;
        $provider = $manager->getProvider();

        $this->assertNotNull($provider);
        $this->assertEquals('google', $provider->getName());
    }

    public function test_analytics_manager_resolves_plausible_provider(): void
    {
        ProSetting::set('analytics_provider', 'plausible');
        Cache::flush();

        $manager = new AnalyticsManager;
        $provider = $manager->getProvider();

        $this->assertNotNull($provider);
        $this->assertEquals('plausible', $provider->getName());
    }

    public function test_analytics_manager_resolves_fathom_provider(): void
    {
        ProSetting::set('analytics_provider', 'fathom');
        Cache::flush();

        $manager = new AnalyticsManager;
        $provider = $manager->getProvider();

        $this->assertNotNull($provider);
        $this->assertEquals('fathom', $provider->getName());
    }

    public function test_analytics_manager_returns_null_for_unknown_provider(): void
    {
        ProSetting::set('analytics_provider', 'nonexistent');
        Cache::flush();

        $manager = new AnalyticsManager;
        $this->assertNull($manager->getProvider());
    }

    // -------------------------------------------------------
    // Analytics: Plausible Normalization
    // -------------------------------------------------------

    public function test_plausible_is_not_configured_without_credentials(): void
    {
        ProSetting::set('analytics_provider', 'plausible');
        Cache::flush();

        $provider = new PlausibleAnalyticsProvider;
        $this->assertFalse($provider->isConfigured());
    }

    public function test_plausible_is_configured_with_credentials(): void
    {
        ProSetting::set('plausible_domain', 'example.com');
        ProSetting::set('plausible_api_key', 'test-key');
        Cache::flush();

        $provider = new PlausibleAnalyticsProvider;
        $this->assertTrue($provider->isConfigured());
    }

    public function test_plausible_overview_returns_normalized_shape(): void
    {
        ProSetting::set('plausible_domain', 'example.com');
        ProSetting::set('plausible_api_key', 'test-key');
        Cache::flush();

        Http::fake([
            'plausible.io/api/v1/stats/aggregate*' => Http::response([
                'results' => [
                    'visitors' => ['value' => 1234],
                    'pageviews' => ['value' => 5678],
                    'bounce_rate' => ['value' => 45.2],
                    'visit_duration' => ['value' => 180],
                ],
            ]),
        ]);

        $provider = new PlausibleAnalyticsProvider;
        $metrics = $provider->getOverviewMetrics('7d');

        $this->assertArrayHasKey('visitors', $metrics);
        $this->assertArrayHasKey('pageviews', $metrics);
        $this->assertArrayHasKey('bounce_rate', $metrics);
        $this->assertArrayHasKey('avg_session_duration', $metrics);
        $this->assertArrayHasKey('visitors_change', $metrics);
        $this->assertArrayHasKey('pageviews_change', $metrics);

        $this->assertEquals(1234, $metrics['visitors']);
        $this->assertEquals(5678, $metrics['pageviews']);
        $this->assertIsFloat($metrics['bounce_rate']);
        $this->assertIsInt($metrics['avg_session_duration']);
    }

    public function test_plausible_top_pages_returns_normalized_shape(): void
    {
        ProSetting::set('plausible_domain', 'example.com');
        ProSetting::set('plausible_api_key', 'test-key');
        Cache::flush();

        Http::fake([
            'plausible.io/api/v1/stats/breakdown*' => Http::response([
                'results' => [
                    ['page' => '/about', 'visitors' => 100],
                    ['page' => '/contact', 'visitors' => 50],
                ],
            ]),
        ]);

        $provider = new PlausibleAnalyticsProvider;
        $pages = $provider->getTopPages(5, '7d');

        $this->assertCount(2, $pages);
        $this->assertArrayHasKey('path', $pages[0]);
        $this->assertArrayHasKey('title', $pages[0]);
        $this->assertArrayHasKey('views', $pages[0]);
        $this->assertEquals('/about', $pages[0]['path']);
        $this->assertEquals(100, $pages[0]['views']);
    }

    public function test_plausible_visitor_trend_returns_normalized_shape(): void
    {
        ProSetting::set('plausible_domain', 'example.com');
        ProSetting::set('plausible_api_key', 'test-key');
        Cache::flush();

        Http::fake([
            'plausible.io/api/v1/stats/timeseries*' => Http::response([
                'results' => [
                    ['date' => '2026-04-10', 'visitors' => 50, 'pageviews' => 120],
                    ['date' => '2026-04-11', 'visitors' => 65, 'pageviews' => 140],
                ],
            ]),
        ]);

        $provider = new PlausibleAnalyticsProvider;
        $trend = $provider->getVisitorTrend('7d');

        $this->assertCount(2, $trend);
        $this->assertArrayHasKey('date', $trend[0]);
        $this->assertArrayHasKey('visitors', $trend[0]);
        $this->assertArrayHasKey('pageviews', $trend[0]);
    }

    // -------------------------------------------------------
    // Analytics: Fathom Normalization
    // -------------------------------------------------------

    public function test_fathom_is_not_configured_without_credentials(): void
    {
        ProSetting::set('analytics_provider', 'fathom');
        Cache::flush();

        $provider = new FathomAnalyticsProvider;
        $this->assertFalse($provider->isConfigured());
    }

    public function test_fathom_overview_returns_normalized_shape(): void
    {
        ProSetting::set('fathom_site_id', 'TESTSITE');
        ProSetting::set('fathom_api_key', 'test-key');
        Cache::flush();

        Http::fake([
            'api.usefathom.com/api/v1/aggregations*' => Http::response([
                [
                    'uniques' => 800,
                    'pageviews' => 2400,
                    'bounce_rate' => 0.35,
                    'avg_duration' => 210,
                ],
            ]),
        ]);

        $provider = new FathomAnalyticsProvider;
        $metrics = $provider->getOverviewMetrics('7d');

        $this->assertArrayHasKey('visitors', $metrics);
        $this->assertArrayHasKey('pageviews', $metrics);
        $this->assertArrayHasKey('bounce_rate', $metrics);
        $this->assertArrayHasKey('avg_session_duration', $metrics);
        $this->assertArrayHasKey('visitors_change', $metrics);
        $this->assertArrayHasKey('pageviews_change', $metrics);

        $this->assertEquals(800, $metrics['visitors']);
        $this->assertEquals(2400, $metrics['pageviews']);
    }

    // -------------------------------------------------------
    // Analytics: Comparison Fallback
    // -------------------------------------------------------

    public function test_plausible_defaults_change_to_zero_on_incomplete_comparison(): void
    {
        ProSetting::set('plausible_domain', 'example.com');
        ProSetting::set('plausible_api_key', 'test-key');
        Cache::flush();

        // Return data without comparison fields
        Http::fake([
            'plausible.io/api/v1/stats/aggregate*' => Http::response([
                'results' => [
                    'visitors' => ['value' => 100],
                    'pageviews' => ['value' => 200],
                    'bounce_rate' => ['value' => 50],
                    'visit_duration' => ['value' => 60],
                ],
            ]),
        ]);

        $provider = new PlausibleAnalyticsProvider;
        $metrics = $provider->getOverviewMetrics('7d');

        // Both comparison calls return same data (no change field),
        // so calculateChange(100, 100) = 0
        $this->assertIsFloat($metrics['visitors_change']);
        $this->assertIsFloat($metrics['pageviews_change']);
    }

    // -------------------------------------------------------
    // Email Marketing: Manager
    // -------------------------------------------------------

    public function test_email_marketing_not_configured_by_default(): void
    {
        $manager = new EmailMarketingManager;
        $this->assertFalse($manager->isConfigured());
    }

    public function test_email_marketing_resolves_mailchimp(): void
    {
        ProSetting::set('email_provider', 'mailchimp');
        ProSetting::set('mailchimp_api_key', 'test-key-us1');
        ProSetting::set('mailchimp_list_id', 'abc123');
        Cache::flush();

        $manager = new EmailMarketingManager;
        $this->assertTrue($manager->isConfigured());
        $this->assertInstanceOf(MailchimpProvider::class, $manager->getProvider());
    }

    public function test_email_marketing_resolves_convertkit(): void
    {
        ProSetting::set('email_provider', 'convertkit');
        ProSetting::set('convertkit_api_key', 'test-key');
        ProSetting::set('convertkit_form_id', '12345');
        Cache::flush();

        $manager = new EmailMarketingManager;
        $this->assertTrue($manager->isConfigured());
        $this->assertInstanceOf(ConvertKitProvider::class, $manager->getProvider());
    }

    public function test_email_marketing_resolves_brevo(): void
    {
        ProSetting::set('email_provider', 'sendinblue');
        ProSetting::set('sendinblue_api_key', 'test-key');
        ProSetting::set('sendinblue_list_id', '5');
        Cache::flush();

        $manager = new EmailMarketingManager;
        $this->assertTrue($manager->isConfigured());
        $this->assertInstanceOf(BrevoProvider::class, $manager->getProvider());
    }

    // -------------------------------------------------------
    // Email Marketing: Job Dispatch on Contact Submission
    // -------------------------------------------------------

    public function test_contact_submission_dispatches_email_marketing_job(): void
    {
        ProSetting::set('email_provider', 'mailchimp');
        ProSetting::set('mailchimp_api_key', 'test-key-us1');
        ProSetting::set('mailchimp_list_id', 'abc123');
        Cache::flush();

        Bus::fake([SyncContactToEmailMarketing::class]);

        // Create submission via the app wrapper model (Livewire path)
        $modelClass = class_exists(\App\Models\TallcmsContactSubmission::class)
            ? \App\Models\TallcmsContactSubmission::class
            : \TallCms\Cms\Models\TallcmsContactSubmission::class;

        $modelClass::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'form_data' => ['message' => 'Hello'],
            'page_url' => '/contact',
        ]);

        Bus::assertDispatched(SyncContactToEmailMarketing::class);
    }

    public function test_contact_submission_does_not_dispatch_when_not_configured(): void
    {
        // No email provider configured
        ProSetting::set('email_provider', '');
        Cache::flush();

        Bus::fake([SyncContactToEmailMarketing::class]);

        $modelClass = class_exists(\App\Models\TallcmsContactSubmission::class)
            ? \App\Models\TallcmsContactSubmission::class
            : \TallCms\Cms\Models\TallcmsContactSubmission::class;

        $modelClass::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'form_data' => ['message' => 'Hello'],
            'page_url' => '/contact',
        ]);

        Bus::assertNotDispatched(SyncContactToEmailMarketing::class);
    }

    public function test_email_job_retries_on_provider_failure(): void
    {
        ProSetting::set('email_provider', 'mailchimp');
        ProSetting::set('mailchimp_api_key', 'test-key-us1');
        ProSetting::set('mailchimp_list_id', 'abc123');
        Cache::flush();

        Http::fake([
            'us1.api.mailchimp.com/*' => Http::response('Server Error', 500),
        ]);

        $job = new SyncContactToEmailMarketing('test@example.com', 'Test User');

        $this->expectException(\Illuminate\Http\Client\RequestException::class);
        $job->handle(new EmailMarketingManager);
    }

    // -------------------------------------------------------
    // Email Marketing: Observer Rescue (sync-safe)
    // -------------------------------------------------------

    public function test_observer_rescue_catches_exceptions_on_sync_queue(): void
    {
        // Set up a broken provider config to trigger an exception
        ProSetting::set('email_provider', 'mailchimp');
        ProSetting::set('mailchimp_api_key', 'bad-key-us1');
        ProSetting::set('mailchimp_list_id', 'bad-list');
        Cache::flush();

        Http::fake([
            'us1.api.mailchimp.com/*' => Http::response('Unauthorized', 401),
        ]);

        // With sync queue, the job runs inline. rescue() should catch the exception
        // and the submission should still be created successfully.
        $modelClass = class_exists(\App\Models\TallcmsContactSubmission::class)
            ? \App\Models\TallcmsContactSubmission::class
            : \TallCms\Cms\Models\TallcmsContactSubmission::class;

        // This should NOT throw — rescue() protects the submission
        $submission = $modelClass::create([
            'name' => 'Rescue Test',
            'email' => 'rescue@example.com',
            'form_data' => ['message' => 'Testing rescue'],
            'page_url' => '/contact',
        ]);

        $this->assertDatabaseHas('tallcms_contact_submissions', [
            'email' => 'rescue@example.com',
        ]);
    }

    // -------------------------------------------------------
    // Block Rendering: Comparison Color Allowlist
    // -------------------------------------------------------

    public function test_comparison_block_sanitizes_highlight_color(): void
    {
        $colors = ['primary', 'secondary', 'accent', 'info', 'success', 'warning', 'error'];

        foreach ($colors as $color) {
            $html = view('tallcms-pro::blocks.comparison', [
                'heading' => '',
                'subheading' => '',
                'columns' => [
                    ['title' => 'Basic', 'highlighted' => false],
                    ['title' => 'Pro', 'highlighted' => true],
                ],
                'features' => [
                    ['name' => 'Feature', 'values' => [
                        ['status' => 'check', 'text' => ''],
                        ['status' => 'check', 'text' => ''],
                    ]],
                ],
                'style' => 'default',
                'highlight_color' => $color,
                'is_preview' => false,
                'anchor_id' => null,
                'css_classes' => '',
                'contentWidthClass' => 'max-w-6xl mx-auto',
                'contentPadding' => 'px-4',
                'animation_type' => '',
                'animation_duration' => '',
                'animation_stagger' => false,
                'animation_stagger_delay' => 100,
            ])->render();

            // Should contain the allowlisted class, not raw interpolation
            $this->assertStringContainsString("badge-{$color}", $html);
        }

        // Test that an invalid color falls back to primary
        $html = view('tallcms-pro::blocks.comparison', [
            'heading' => '',
            'subheading' => '',
            'columns' => [
                ['title' => 'Basic', 'highlighted' => false],
                ['title' => 'Exploit', 'highlighted' => true],
            ],
            'features' => [
                ['name' => 'Feature', 'values' => [
                    ['status' => 'check', 'text' => ''],
                    ['status' => 'check', 'text' => ''],
                ]],
            ],
            'style' => 'default',
            'highlight_color' => '<script>alert(1)</script>',
            'is_preview' => false,
            'anchor_id' => null,
            'css_classes' => '',
            'contentWidthClass' => 'max-w-6xl mx-auto',
            'contentPadding' => 'px-4',
            'animation_type' => '',
            'animation_duration' => '',
            'animation_stagger' => false,
            'animation_stagger_delay' => 100,
        ])->render();

        $this->assertStringContainsString('badge-primary', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    // -------------------------------------------------------
    // Block Rendering: Tabs aria-label escaping
    // -------------------------------------------------------

    public function test_tabs_escapes_aria_label(): void
    {
        $html = view('tallcms-pro::blocks.tabs', [
            'heading' => '',
            'subheading' => '',
            'tabs' => [
                ['title' => 'Tab "with" <quotes>', 'content' => 'Content', 'icon' => 'heroicon-o-home'],
            ],
            'layout' => 'horizontal',
            'style' => 'underline',
            'alignment' => 'left',
            'tab_size' => 'md',
            'icon_position' => 'only',
            'active_indicator' => 'default',
            'is_preview' => true,
            'anchor_id' => null,
            'css_classes' => '',
            'contentWidthClass' => 'max-w-6xl mx-auto',
            'contentPadding' => 'px-4',
            'animation_type' => '',
            'animation_duration' => '',
            'animation_stagger' => false,
            'animation_stagger_delay' => 100,
        ])->render();

        // Should use e() escaping — quotes become &quot;, < becomes &lt;
        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringNotContainsString('addslashes', $html);
    }

    // -------------------------------------------------------
    // Block Rendering: Table header toggle
    // -------------------------------------------------------

    public function test_table_renders_without_header_when_toggled_off(): void
    {
        $html = view('tallcms-pro::blocks.table', [
            'heading' => '',
            'subheading' => '',
            'headers' => [['label' => 'Col A', 'align' => 'left']],
            'rows' => [['cells' => [['value' => 'data']], 'highlight' => false]],
            'table_size' => 'md',
            'striped' => true,
            'bordered' => true,
            'hover' => true,
            'responsive' => true,
            'show_header' => false,
            'is_preview' => false,
            'anchor_id' => null,
            'css_classes' => '',
            'contentWidthClass' => 'max-w-6xl mx-auto',
            'contentPadding' => 'px-4',
            'animation_type' => '',
            'animation_duration' => '',
            'animation_stagger' => false,
            'animation_stagger_delay' => 100,
        ])->render();

        $this->assertStringNotContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
    }

    public function test_table_renders_with_header_by_default(): void
    {
        $html = view('tallcms-pro::blocks.table', [
            'heading' => '',
            'subheading' => '',
            'headers' => [['label' => 'Col A', 'align' => 'left']],
            'rows' => [['cells' => [['value' => 'data']], 'highlight' => false]],
            'table_size' => 'md',
            'striped' => true,
            'bordered' => true,
            'hover' => true,
            'responsive' => true,
            'show_header' => true,
            'is_preview' => false,
            'anchor_id' => null,
            'css_classes' => '',
            'contentWidthClass' => 'max-w-6xl mx-auto',
            'contentPadding' => 'px-4',
            'animation_type' => '',
            'animation_duration' => '',
            'animation_stagger' => false,
            'animation_stagger_delay' => 100,
        ])->render();

        $this->assertStringContainsString('<thead>', $html);
    }

    // -------------------------------------------------------
    // Block Rendering: Code Snippet themes
    // -------------------------------------------------------

    public function test_code_snippet_renders_theme_class(): void
    {
        $themes = [
            'dracula', 'github-dark', 'one-dark', 'nord', 'monokai',
            'vs-code-dark', 'solarized-dark', 'material', 'github-light', 'one-light',
        ];

        foreach ($themes as $theme) {
            $html = view('tallcms-pro::blocks.code-snippet', [
                'heading' => '',
                'subheading' => '',
                'code' => 'echo "hello";',
                'language' => 'php',
                'theme' => $theme,
                'filename' => '',
                'line_prefix' => 'numbers',
                'show_line_numbers' => true,
                'show_copy_button' => true,
                'show_language_badge' => true,
                'max_height' => 'none',
                'highlight_lines' => '',
                'is_preview' => true,
                'anchor_id' => null,
                'css_classes' => '',
                'contentWidthClass' => 'max-w-6xl mx-auto',
                'contentPadding' => 'px-4',
                'animation_type' => '',
                'animation_duration' => '',
            ])->render();

            $this->assertStringContainsString("code-theme-{$theme}", $html, "Theme class missing for: {$theme}");
        }
    }

    public function test_code_snippet_maps_legacy_default_theme_to_dracula(): void
    {
        $html = view('tallcms-pro::blocks.code-snippet', [
            'heading' => '',
            'subheading' => '',
            'code' => 'test',
            'language' => 'php',
            'theme' => 'default',
            'filename' => '',
            'line_prefix' => 'numbers',
            'show_line_numbers' => true,
            'show_copy_button' => false,
            'show_language_badge' => false,
            'max_height' => 'none',
            'highlight_lines' => '',
            'is_preview' => true,
            'anchor_id' => null,
            'css_classes' => '',
            'contentWidthClass' => 'max-w-6xl mx-auto',
            'contentPadding' => 'px-4',
            'animation_type' => '',
            'animation_duration' => '',
        ])->render();

        $this->assertStringContainsString('code-theme-dracula', $html);
    }

    // -------------------------------------------------------
    // Multisite: ProSetting per-site overrides
    // -------------------------------------------------------

    public function test_pro_setting_reads_global_without_multisite_context(): void
    {
        ProSetting::setGlobal('email_provider', 'mailchimp');
        Cache::flush();

        // No site context → reads global
        $this->assertEquals('mailchimp', ProSetting::get('email_provider'));
    }

    public function test_pro_setting_reads_override_with_site_context(): void
    {
        $this->ensureOverrideTableExists();

        ProSetting::setGlobal('email_provider', 'mailchimp');
        Cache::flush();

        // Write a per-site override directly
        \Illuminate\Support\Facades\DB::table('tallcms_site_setting_overrides')->insert([
            'site_id' => 99,
            'key' => 'email_provider',
            'value' => 'convertkit',
            'type' => 'text',
            'updated_at' => now(),
        ]);

        // With site context → reads override
        $result = ProSetting::withSiteContext(99, fn () => ProSetting::get('email_provider'));
        $this->assertEquals('convertkit', $result);
    }

    public function test_pro_setting_falls_back_to_global_when_no_override(): void
    {
        $this->ensureOverrideTableExists();

        ProSetting::setGlobal('maps_provider', 'openstreetmap');
        Cache::flush();

        // Site 99 has no override for maps_provider
        $result = ProSetting::withSiteContext(99, fn () => ProSetting::get('maps_provider'));
        $this->assertEquals('openstreetmap', $result);
    }

    public function test_pro_setting_get_global_bypasses_site_context(): void
    {
        $this->ensureOverrideTableExists();

        ProSetting::setGlobal('email_provider', 'mailchimp');
        Cache::flush();

        \Illuminate\Support\Facades\DB::table('tallcms_site_setting_overrides')->insert([
            'site_id' => 99,
            'key' => 'email_provider',
            'value' => 'brevo',
            'type' => 'text',
            'updated_at' => now(),
        ]);

        // getGlobal always returns global, even with site context
        $result = ProSetting::withSiteContext(99, fn () => ProSetting::getGlobal('email_provider'));
        $this->assertEquals('mailchimp', $result);
    }

    public function test_pro_setting_reset_to_global_deletes_override(): void
    {
        $this->ensureOverrideTableExists();

        \Illuminate\Support\Facades\DB::table('tallcms_site_setting_overrides')->insert([
            'site_id' => 99,
            'key' => 'email_provider',
            'value' => 'convertkit',
            'type' => 'text',
            'updated_at' => now(),
        ]);

        ProSetting::withSiteContext(99, fn () => ProSetting::resetToGlobal('email_provider'));

        $this->assertDatabaseMissing('tallcms_site_setting_overrides', [
            'site_id' => 99,
            'key' => 'email_provider',
        ]);
    }

    // -------------------------------------------------------
    // Multisite: withSiteContext guarantees reset
    // -------------------------------------------------------

    public function test_with_site_context_resets_on_success(): void
    {
        $this->assertNull(ProSetting::$overrideSiteId);

        ProSetting::withSiteContext(42, fn () => 'ok');

        $this->assertNull(ProSetting::$overrideSiteId);
    }

    public function test_with_site_context_resets_on_exception(): void
    {
        $this->assertNull(ProSetting::$overrideSiteId);

        try {
            ProSetting::withSiteContext(42, function () {
                throw new \RuntimeException('test error');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertNull(ProSetting::$overrideSiteId);
    }

    public function test_with_site_context_restores_previous_value(): void
    {
        ProSetting::$overrideSiteId = 10;

        ProSetting::withSiteContext(20, function () {
            $this->assertEquals(20, ProSetting::$overrideSiteId);
        });

        $this->assertEquals(10, ProSetting::$overrideSiteId);

        // Clean up
        ProSetting::$overrideSiteId = null;
    }

    // -------------------------------------------------------
    // Multisite: Encrypted keys in override table
    // -------------------------------------------------------

    public function test_encrypted_keys_are_encrypted_in_override_table(): void
    {
        $this->ensureOverrideTableExists();

        ProSetting::withSiteContext(99, function () {
            ProSetting::set('mailchimp_api_key', 'secret-key-123', 'text', 'email', true);
        });

        // The value in the override table should be encrypted (not plaintext)
        $stored = \Illuminate\Support\Facades\DB::table('tallcms_site_setting_overrides')
            ->where('site_id', 99)
            ->where('key', 'mailchimp_api_key')
            ->value('value');

        $this->assertNotEquals('secret-key-123', $stored);

        // But reading it back should decrypt
        Cache::flush();
        $result = ProSetting::withSiteContext(99, fn () => ProSetting::get('mailchimp_api_key'));
        $this->assertEquals('secret-key-123', $result);
    }

    // -------------------------------------------------------
    // Multisite: Email job carries site_id
    // -------------------------------------------------------

    public function test_email_job_uses_site_context(): void
    {
        $this->ensureOverrideTableExists();

        // Global: mailchimp
        ProSetting::setGlobal('email_provider', 'mailchimp');
        ProSetting::setGlobal('mailchimp_api_key', 'global-key-us1');
        ProSetting::setGlobal('mailchimp_list_id', 'global-list');

        // Site 99 override: convertkit (encrypted keys must be encrypted in the override table)
        \Illuminate\Support\Facades\DB::table('tallcms_site_setting_overrides')->insert([
            ['site_id' => 99, 'key' => 'email_provider', 'value' => 'convertkit', 'type' => 'text', 'updated_at' => now()],
            ['site_id' => 99, 'key' => 'convertkit_api_key', 'value' => encrypt('site-key'), 'type' => 'text', 'updated_at' => now()],
            ['site_id' => 99, 'key' => 'convertkit_form_id', 'value' => '12345', 'type' => 'text', 'updated_at' => now()],
        ]);
        Cache::flush();

        Http::fake([
            'api.convertkit.com/*' => Http::response(['subscription' => ['id' => 1]], 200),
        ]);

        // Job with site_id=99 should use ConvertKit, not Mailchimp
        $job = new SyncContactToEmailMarketing('test@example.com', 'Test', 99);
        $job->handle(new EmailMarketingManager);

        // Verify ConvertKit was called (not Mailchimp)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'convertkit.com');
        });
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    protected function ensureOverrideTableExists(): void
    {
        $schema = \Illuminate\Support\Facades\Schema::class;

        if (! $schema::hasTable('tallcms_site_setting_overrides')) {
            $schema::create('tallcms_site_setting_overrides', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('site_id');
                $table->string('key');
                $table->longText('value')->nullable();
                $table->string('type')->default('text');
                $table->timestamps();
                $table->unique(['site_id', 'key']);
            });
        }

        // Ensure test site exists for FK constraints (multisite table may have FK to sites)
        if ($schema::hasTable('tallcms_sites')) {
            \Illuminate\Support\Facades\DB::table('tallcms_sites')->insertOrIgnore([
                'id' => 99,
                'name' => 'Test Site',
                'domain' => 'test-pro.test',
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
