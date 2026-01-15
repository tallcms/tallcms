<?php

declare(strict_types=1);

namespace TallCms\Cms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TallCms\Cms\Models\TallcmsContactSubmission;

class ContactFormAutoReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TallcmsContactSubmission $submission
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for contacting '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'tallcms::emails.contact-form-auto-reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
