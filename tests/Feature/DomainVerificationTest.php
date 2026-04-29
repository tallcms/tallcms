<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tallcms\Multisite\Enums\DomainStatus;
use Tallcms\Multisite\Jobs\TriggerTlsProvisioning;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\DomainVerificationService;
use TallCms\Cms\Models\SiteSetting;
use Tests\TestCase;

class DomainVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        User::factory()->create();
        Cache::flush();

        $this->site = Site::create([
            'name' => 'Test Site',
            'domain' => 'custom.example.com',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    // -------------------------------------------------------
    // DomainStatus Enum
    // -------------------------------------------------------

    public function test_domain_status_enum_has_expected_cases(): void
    {
        $this->assertEquals('pending', DomainStatus::Pending->value);
        $this->assertEquals('verified', DomainStatus::Verified->value);
        $this->assertEquals('failed', DomainStatus::Failed->value);
        $this->assertEquals('stale', DomainStatus::Stale->value);
    }

    public function test_only_verified_status_is_eligible_for_tls(): void
    {
        $this->assertTrue(DomainStatus::Verified->isEligibleForTls());
        $this->assertFalse(DomainStatus::Pending->isEligibleForTls());
        $this->assertFalse(DomainStatus::Failed->isEligibleForTls());
        $this->assertFalse(DomainStatus::Stale->isEligibleForTls());
    }

    // -------------------------------------------------------
    // Managed Subdomain Detection
    // -------------------------------------------------------

    public function test_custom_domain_is_not_managed_subdomain(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $this->assertFalse($this->site->isManagedSubdomain());
    }

    public function test_subdomain_of_base_is_managed(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $managed = Site::create([
            'name' => 'Managed',
            'domain' => 'tenant.yoursaas.com',
            'is_active' => true,
        ]);

        $this->assertTrue($managed->isManagedSubdomain());
    }

    public function test_apex_domain_is_not_managed(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $apex = Site::create([
            'name' => 'Apex',
            'domain' => 'yoursaas.com',
            'is_active' => true,
        ]);

        $this->assertFalse($apex->isManagedSubdomain());
    }

    public function test_no_base_domain_means_not_managed(): void
    {
        config(['tallcms.multisite.base_domain' => null]);

        $this->assertFalse($this->site->isManagedSubdomain());
    }

    // -------------------------------------------------------
    // TLS Eligibility (domain_status based)
    // -------------------------------------------------------

    public function test_verified_custom_domain_is_eligible_for_tls(): void
    {
        $this->site->update(['domain_status' => DomainStatus::Verified]);

        $this->assertTrue($this->site->isEligibleForTls());
    }

    public function test_pending_custom_domain_is_not_eligible_for_tls(): void
    {
        $this->site->update(['domain_status' => DomainStatus::Pending]);

        $this->assertFalse($this->site->isEligibleForTls());
    }

    public function test_failed_custom_domain_is_not_eligible_for_tls(): void
    {
        $this->site->update(['domain_status' => DomainStatus::Failed]);

        $this->assertFalse($this->site->isEligibleForTls());
    }

    public function test_stale_custom_domain_is_not_eligible_for_tls(): void
    {
        $this->site->update(['domain_status' => DomainStatus::Stale]);

        $this->assertFalse($this->site->isEligibleForTls());
    }

    public function test_inactive_site_is_never_eligible_for_tls(): void
    {
        $this->site->update([
            'domain_status' => DomainStatus::Verified,
            'is_active' => false,
        ]);

        $this->assertFalse($this->site->isEligibleForTls());
    }

    public function test_managed_subdomain_is_always_eligible_for_tls(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $managed = Site::create([
            'name' => 'Managed',
            'domain' => 'tenant.yoursaas.com',
            'is_active' => true,
            'domain_status' => DomainStatus::Pending,
        ]);

        $this->assertTrue($managed->isEligibleForTls());
    }

    // -------------------------------------------------------
    // Domain Change Resets Verification
    // -------------------------------------------------------

    public function test_domain_change_resets_verification(): void
    {
        $this->site->update([
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
            'domain_verified_at' => now(),
            'domain_checked_at' => now(),
            'domain_verification_note' => 'A record verified',
            'domain_verification_data' => ['type' => 'A', 'ip' => '1.2.3.4'],
        ]);

        $this->site->update(['domain' => 'new-domain.example.com']);
        $this->site->refresh();

        $this->assertEquals(DomainStatus::Pending, $this->site->domain_status);
        $this->assertFalse($this->site->domain_verified);
        $this->assertNull($this->site->domain_verified_at);
        $this->assertNull($this->site->domain_checked_at);
        $this->assertNull($this->site->domain_verification_note);
        $this->assertNull($this->site->domain_verification_data);
    }

    public function test_domain_change_on_managed_subdomain_does_not_reset(): void
    {
        config(['tallcms.multisite.base_domain' => 'yoursaas.com']);

        $managed = Site::create([
            'name' => 'Managed',
            'domain' => 'old.yoursaas.com',
            'is_active' => true,
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
        ]);

        $managed->update(['domain' => 'new.yoursaas.com']);
        $managed->refresh();

        // Stays verified because it's still a managed subdomain
        $this->assertEquals(DomainStatus::Verified, $managed->domain_status);
        $this->assertTrue($managed->domain_verified);
    }

    // -------------------------------------------------------
    // Verification Service — Config Missing
    // -------------------------------------------------------

    public function test_verify_fails_when_no_config_set(): void
    {
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_cname_target', '', 'text', 'multisite');
        Cache::flush();

        $service = app(DomainVerificationService::class);
        $result = $service->verify($this->site);

        $this->assertEquals(DomainStatus::Failed->value, $result['status']);
        $this->assertStringContainsString('not configured', $result['message']);
    }

    public function test_failed_verification_revokes_trust(): void
    {
        // Start with a verified site
        $this->site->update([
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
            'domain_verified_at' => now()->subDays(5),
        ]);

        // Simulate a failed verification (no config = guaranteed failure)
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_cname_target', '', 'text', 'multisite');
        Cache::flush();

        $service = app(DomainVerificationService::class);
        $result = $service->verify($this->site);

        // Apply the same logic as EditSite action's failure branch
        $this->site->domain_status = DomainStatus::Failed;
        $this->site->domain_verified = false;
        $this->site->domain_verified_at = null;
        $this->site->domain_checked_at = now();
        $this->site->domain_verification_note = $result['message'];
        $this->site->domain_verification_data = $result['observed'];
        $this->site->save();

        $this->site->refresh();

        $this->assertEquals(DomainStatus::Failed, $this->site->domain_status);
        $this->assertFalse($this->site->domain_verified);
        $this->assertNull($this->site->domain_verified_at);
        $this->assertFalse($this->site->isEligibleForTls());
    }

    // -------------------------------------------------------
    // Instructions
    // -------------------------------------------------------

    public function test_instructions_with_both_configured(): void
    {
        SiteSetting::setGlobal('multisite_server_ips', '1.2.3.4', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_cname_target', 'sites.example.com', 'text', 'multisite');
        Cache::flush();

        $service = app(DomainVerificationService::class);
        $instructions = $service->getInstructions('test.com');

        $this->assertStringContainsString('CNAME', $instructions);
        $this->assertStringContainsString('A/AAAA', $instructions);
        $this->assertStringContainsString('sites.example.com', $instructions);
        $this->assertStringContainsString('1.2.3.4', $instructions);
    }

    public function test_instructions_with_only_ips(): void
    {
        SiteSetting::setGlobal('multisite_server_ips', "1.2.3.4\n5.6.7.8", 'text', 'multisite');
        SiteSetting::setGlobal('multisite_cname_target', '', 'text', 'multisite');
        Cache::flush();

        $service = app(DomainVerificationService::class);
        $instructions = $service->getInstructions('test.com');

        $this->assertStringContainsString('A record', $instructions);
        $this->assertStringContainsString('AAAA record', $instructions);
        $this->assertStringNotContainsString('CNAME', $instructions);
    }

    public function test_instructions_with_only_cname(): void
    {
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_cname_target', 'sites.example.com', 'text', 'multisite');
        Cache::flush();

        $service = app(DomainVerificationService::class);
        $instructions = $service->getInstructions('test.com');

        $this->assertStringContainsString('CNAME', $instructions);
        $this->assertStringContainsString('sites.example.com', $instructions);
    }

    public function test_instructions_when_nothing_configured(): void
    {
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_cname_target', '', 'text', 'multisite');
        Cache::flush();

        $service = app(DomainVerificationService::class);
        $instructions = $service->getInstructions('test.com');

        $this->assertStringContainsString('not yet configured', $instructions);
    }

    // -------------------------------------------------------
    // TLS Job Dispatch
    // -------------------------------------------------------

    public function test_trigger_tls_dispatches_job(): void
    {
        Queue::fake();

        $service = app(DomainVerificationService::class);
        $service->triggerTls('test.example.com');

        Queue::assertPushed(TriggerTlsProvisioning::class, function ($job) {
            return $job->domain === 'test.example.com';
        });
    }

    // -------------------------------------------------------
    // Re-verification State Machine
    // -------------------------------------------------------

    public function test_reverify_command_skips_when_disabled(): void
    {
        SiteSetting::setGlobal('multisite_reverify_days', '0', 'text', 'multisite');
        Cache::flush();

        $this->artisan('tallcms:reverify-domains')
            ->expectsOutput('Re-verification is disabled (multisite_reverify_days is 0 or not set).')
            ->assertExitCode(0);
    }

    public function test_reverify_command_skips_when_not_set(): void
    {
        Cache::flush();

        $this->artisan('tallcms:reverify-domains')
            ->expectsOutput('Re-verification is disabled (multisite_reverify_days is 0 or not set).')
            ->assertExitCode(0);
    }

    // -------------------------------------------------------
    // Re-verification Batching
    // -------------------------------------------------------

    public function test_reverify_command_respects_batch_limit(): void
    {
        SiteSetting::setGlobal('multisite_reverify_days', '7', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_reverify_batch_size', '2', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        Cache::flush();

        // Clear any pre-existing verified sites from boot
        Site::where('domain_status', DomainStatus::Verified->value)->update(['domain_status' => DomainStatus::Pending->value]);

        $domains = [];
        for ($i = 1; $i <= 5; $i++) {
            $domains[] = "batch{$i}.example.com";
            Site::create([
                'name' => "Batch Site {$i}",
                'domain' => "batch{$i}.example.com",
                'is_active' => true,
                'domain_status' => DomainStatus::Verified,
                'domain_verified' => true,
                'domain_checked_at' => now()->subDays(10),
            ]);
        }

        $this->artisan('tallcms:reverify-domains')->assertExitCode(0);

        // Only 2 of our 5 test domains should have been checked
        $checkedCount = Site::whereIn('domain', $domains)
            ->where('domain_checked_at', '>', now()->subMinutes(1))
            ->count();
        $this->assertEquals(2, $checkedCount);
    }

    public function test_reverify_batch_processes_stalest_first(): void
    {
        SiteSetting::setGlobal('multisite_reverify_days', '7', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_reverify_batch_size', '1', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        Cache::flush();

        // Ensure no other verified sites interfere
        Site::where('domain_status', DomainStatus::Verified->value)->update(['domain_status' => DomainStatus::Pending->value]);

        $oldest = Site::create([
            'name' => 'Oldest',
            'domain' => 'oldest.example.com',
            'is_active' => true,
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
            'domain_checked_at' => now()->subDays(30),
        ]);

        $newer = Site::create([
            'name' => 'Newer',
            'domain' => 'newer.example.com',
            'is_active' => true,
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
            'domain_checked_at' => now()->subDays(10),
        ]);

        $this->artisan('tallcms:reverify-domains')->assertExitCode(0);

        $oldest->refresh();
        $newer->refresh();

        // Only the stalest should have been checked (batch size = 1)
        $this->assertNotNull($oldest->domain_checked_at);
        $this->assertTrue($oldest->domain_checked_at->isAfter(now()->subMinutes(1)));
        $this->assertTrue($newer->domain_checked_at->isBefore(now()->subDays(9)));
    }

    public function test_reverify_batch_size_is_clamped(): void
    {
        SiteSetting::setGlobal('multisite_reverify_days', '7', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_reverify_batch_size', '99999', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        Cache::flush();

        $domains = [];
        for ($i = 1; $i <= 3; $i++) {
            $domains[] = "clamp{$i}.example.com";
            Site::create([
                'name' => "Clamp Site {$i}",
                'domain' => "clamp{$i}.example.com",
                'is_active' => true,
                'domain_status' => DomainStatus::Verified,
                'domain_verified' => true,
                'domain_checked_at' => now()->subDays(10),
            ]);
        }

        $this->artisan('tallcms:reverify-domains')->assertExitCode(0);

        // All 3 of our test domains should be checked (clamped to 500 > 3)
        $checkedCount = Site::whereIn('domain', $domains)
            ->where('domain_checked_at', '>', now()->subMinutes(1))
            ->count();
        $this->assertEquals(3, $checkedCount);
    }

    public function test_reverify_null_checked_at_processed_first(): void
    {
        SiteSetting::setGlobal('multisite_reverify_days', '7', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_reverify_batch_size', '1', 'text', 'multisite');
        SiteSetting::setGlobal('multisite_server_ips', '', 'text', 'multisite');
        Cache::flush();

        // Ensure no other verified sites interfere
        Site::where('domain_status', DomainStatus::Verified->value)->update(['domain_status' => DomainStatus::Pending->value]);

        $neverChecked = Site::create([
            'name' => 'Never Checked',
            'domain' => 'never.example.com',
            'is_active' => true,
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
            'domain_checked_at' => null,
        ]);

        $checkedBefore = Site::create([
            'name' => 'Checked Before',
            'domain' => 'checked.example.com',
            'is_active' => true,
            'domain_status' => DomainStatus::Verified,
            'domain_verified' => true,
            'domain_checked_at' => now()->subDays(10),
        ]);

        $this->artisan('tallcms:reverify-domains')->assertExitCode(0);

        $neverChecked->refresh();
        $checkedBefore->refresh();

        // Null checked_at should be processed first
        $this->assertNotNull($neverChecked->domain_checked_at);
        $this->assertTrue($neverChecked->domain_checked_at->isAfter(now()->subMinutes(1)));
        $this->assertTrue($checkedBefore->domain_checked_at->isBefore(now()->subDays(9)));
    }

    // -------------------------------------------------------
    // Migration Backfill
    // -------------------------------------------------------

    public function test_new_site_defaults_to_pending(): void
    {
        $site = Site::create([
            'name' => 'New Site',
            'domain' => 'new.example.com',
            'is_active' => true,
        ]);

        $this->assertEquals(DomainStatus::Pending, $site->domain_status);
    }
}
