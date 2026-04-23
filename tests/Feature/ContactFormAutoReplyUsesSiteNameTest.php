<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tallcms\Multisite\Models\Site;
use TallCms\Cms\Mail\ContactFormAutoReply;
use TallCms\Cms\Models\TallcmsContactSubmission;
use Tests\TestCase;

/**
 * Regression test for the auto-reply subject/body branding.
 *
 * Pre-fix, the auto-reply mailable used config('app.name') — the install's
 * APP_NAME — for both the subject and body greeting. On SaaS, visitors
 * submitted to a specific site (e.g. "Portal") but received a generic
 * "Thank you for contacting TallCMS SaaS" message, breaking the illusion
 * that the site is its own brand. The resolver now prefers the submission's
 * owning site name.
 */
class ContactFormAutoReplyUsesSiteNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_reply_subject_uses_submission_site_name(): void
    {
        if (! class_exists(Site::class)) {
            $this->markTestSkipped('Multisite plugin not installed.');
        }

        $site = Site::create([
            'name' => 'Portal',
            'domain' => 'portal.test',
            'is_active' => true,
        ]);

        $id = DB::table('tallcms_contact_submissions')->insertGetId([
            'site_id' => $site->id,
            'name' => 'Test',
            'email' => 'test@example.com',
            'form_data' => json_encode([]),
            'page_url' => 'https://portal.test/contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $submission = TallcmsContactSubmission::withoutGlobalScopes()->findOrFail($id);

        $mailable = new ContactFormAutoReply($submission);

        $this->assertSame('Thank you for contacting Portal', $mailable->envelope()->subject);

        // Render the content and confirm the site name is in the body, not APP_NAME.
        $rendered = $mailable->render();

        $this->assertStringContainsString('Portal', $rendered);
    }

    public function test_auto_reply_falls_back_to_app_name_when_site_missing(): void
    {
        $submission = new TallcmsContactSubmission;
        $submission->site_id = null;
        $submission->name = 'Test';
        $submission->email = 'test@example.com';
        $submission->form_data = [];
        $submission->page_url = 'https://x/contact';

        $mailable = new ContactFormAutoReply($submission);

        $this->assertSame(
            'Thank you for contacting '.config('app.name'),
            $mailable->envelope()->subject,
        );
    }
}
