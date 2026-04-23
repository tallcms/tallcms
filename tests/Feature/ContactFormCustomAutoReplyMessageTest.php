<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tallcms\Multisite\Models\Site;
use TallCms\Cms\Mail\ContactFormAutoReply;
use TallCms\Cms\Models\TallcmsContactSubmission;
use Tests\TestCase;

/**
 * Contract tests for the per-block contact-form auto-reply customization.
 *
 * Each contact-form block can now carry its own auto-reply copy via the
 * Filament configurator's new "Auto-Reply Message" textarea. When set, it
 * overrides the default paragraph in the sent email; when blank, the default
 * is used. The rendered message is HTML-escaped and converts newlines to
 * <br> so user copy can't inject markup.
 */
class ContactFormCustomAutoReplyMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_message_replaces_default_when_provided(): void
    {
        $submission = $this->makeSubmission();

        $custom = 'Thanks for enquiring about our new launch. Our agent will call you within 24 hours.';
        $mailable = new ContactFormAutoReply($submission, $custom);

        $rendered = $mailable->render();

        $this->assertStringContainsString($custom, $rendered);
        $this->assertStringNotContainsString('respond within 1-2 business days', $rendered);
    }

    public function test_default_message_used_when_custom_is_null_or_blank(): void
    {
        $submission = $this->makeSubmission();

        foreach ([null, ''] as $empty) {
            $mailable = new ContactFormAutoReply($submission, $empty);
            $rendered = $mailable->render();

            $this->assertStringContainsString('respond within 1-2 business days', $rendered);
        }
    }

    public function test_custom_message_escapes_html_but_preserves_newlines(): void
    {
        $submission = $this->makeSubmission();

        $xss = "Line one\nLine two with <script>alert('xss')</script>";
        $mailable = new ContactFormAutoReply($submission, $xss);
        $rendered = $mailable->render();

        // HTML-escaped — angle brackets must not appear as raw tags.
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);

        // Newlines converted to <br> so user copy can span paragraphs.
        $this->assertStringContainsString('<br', $rendered);
    }

    protected function makeSubmission(): TallcmsContactSubmission
    {
        if (class_exists(Site::class)) {
            $site = Site::create([
                'name' => 'Portal',
                'domain' => 'portal.test',
                'is_active' => true,
            ]);
            $siteId = $site->id;
        } else {
            $siteId = null;
        }

        $id = DB::table('tallcms_contact_submissions')->insertGetId(array_filter([
            'site_id' => $siteId,
            'name' => 'Test',
            'email' => 'test@example.com',
            'form_data' => json_encode([]),
            'page_url' => 'https://portal.test/contact',
            'created_at' => now(),
            'updated_at' => now(),
        ], fn ($v) => $v !== null));

        return TallcmsContactSubmission::withoutGlobalScopes()->findOrFail($id);
    }
}
