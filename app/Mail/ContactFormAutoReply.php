<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TallcmsContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            view: 'emails.contact-form-auto-reply',
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
