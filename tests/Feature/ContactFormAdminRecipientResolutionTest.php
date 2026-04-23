<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Http\Controllers\ContactFormController;
use TallCms\Cms\Models\SiteSetting;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\CurrentSiteResolver;
use Tests\TestCase;

/**
 * Regression test for SaaS contact-form admin recipient resolution.
 *
 * Pre-fix, the controller called SiteSetting::get('contact_email'), which
 * falls back to the global value when the site has no override. On SaaS
 * installs where an admin had set a "dummy" global contact_email (e.g. to
 * MAIL_FROM_ADDRESS as a placeholder), every cloned site inherited that
 * dummy as its admin recipient — site owners never saw their own form
 * submissions.
 *
 * The resolver now walks: site override → site owner's user email → global.
 */
class ContactFormAdminRecipientResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        Cache::flush();
        SiteSetting::forgetMemoizedDefaultSiteId();

        $this->owner = User::factory()->create(['email' => 'owner@portal.test']);
        $this->site = Site::create([
            'name' => 'Portal',
            'domain' => 'portal.test',
            'is_active' => true,
            'user_id' => $this->owner->id,
        ]);

        // Pin the resolver to our test site — simulates the frontend having
        // resolved the Host header to this site. The resolver has no public
        // setter; poke the protected properties directly for test setup.
        $resolver = app(CurrentSiteResolver::class);
        $refl = new \ReflectionClass($resolver);
        foreach (['resolved' => true, 'resolvedSite' => $this->site, 'adminContext' => false] as $prop => $value) {
            if ($refl->hasProperty($prop)) {
                $p = $refl->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($resolver, $value);
            }
        }
    }

    public function test_site_override_wins(): void
    {
        DB::table('tallcms_site_setting_overrides')->insert([
            'site_id' => $this->site->id,
            'key' => 'contact_email',
            'value' => 'override@portal.test',
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SiteSetting::setGlobal('contact_email', 'global@example.com');

        $this->assertSame(
            'override@portal.test',
            ContactFormController::resolveAdminRecipient(),
        );
    }

    public function test_site_owner_email_used_when_no_override_and_no_dummy_global_wanted(): void
    {
        // No site override; global carries a "dummy" value the owner never set.
        SiteSetting::setGlobal('contact_email', 'noreply@saas.test');

        $this->assertSame(
            'owner@portal.test',
            ContactFormController::resolveAdminRecipient(),
            'Site owner email must win over a misleading global default — '.
            'otherwise SaaS form submissions go to the install-wide noreply address.',
        );
    }

    public function test_global_used_when_site_has_no_owner(): void
    {
        DB::table('tallcms_sites')
            ->where('id', $this->site->id)
            ->update(['user_id' => null]);

        SiteSetting::setGlobal('contact_email', 'global@example.com');

        $this->assertSame(
            'global@example.com',
            ContactFormController::resolveAdminRecipient(),
        );
    }

    public function test_returns_null_when_nothing_configured(): void
    {
        DB::table('tallcms_sites')
            ->where('id', $this->site->id)
            ->update(['user_id' => null]);

        $this->assertNull(ContactFormController::resolveAdminRecipient());
    }
}
