<?php

declare(strict_types=1);

namespace TallCms\Cms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\TallcmsContactSubmission;

class ContactFormAutoReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TallcmsContactSubmission $submission,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for contacting '.$this->resolveSiteName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'tallcms::emails.contact-form-auto-reply',
            with: [
                'siteName' => $this->resolveSiteName(),
                'customMessage' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Resolve the name of the site the submission was made to.
     *
     * Falls back to the install's app.name when the submission has no
     * site (pre-multisite records, or single-site installs where the
     * plugin hasn't added site_id). Using the site's own name matters
     * in SaaS: the sender submitted to "Portal", not to the install's
     * "TallCMS SaaS" meta-brand, and the auto-reply should mirror that.
     */
    protected function resolveSiteName(): string
    {
        $fallback = (string) config('app.name', 'our site');

        if (! Schema::hasColumn('tallcms_contact_submissions', 'site_id')) {
            return $fallback;
        }

        if (! $this->submission->site_id) {
            return $fallback;
        }

        try {
            $name = DB::table('tallcms_sites')
                ->where('id', $this->submission->site_id)
                ->value('name');

            return $name ?: $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
