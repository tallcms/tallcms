<?php

declare(strict_types=1);

namespace TallCms\Cms\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use TallCms\Cms\Models\CmsComment;
use TallCms\Cms\Services\SeoService;

class CommentApprovedNotification extends Notification
{
    public function __construct(
        protected CmsComment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $postTitle = $this->comment->post?->title ?? 'a post';
        $postUrl = $this->getPostUrl();
        $siteName = $this->getSiteName();

        $subject = $siteName
            ? "Your comment on {$siteName} has been approved"
            : 'Your comment has been approved';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$this->comment->getAuthorName()}!")
            ->line("Your comment on \"{$postTitle}\" has been approved and is now visible.")
            ->action('View Your Comment', $postUrl)
            ->line('Thank you for your contribution!');
    }

    protected function getPostUrl(): string
    {
        if ($this->comment->post) {
            // Pass site_id explicitly for queued notification context
            // where no request exists for domain resolution.
            $siteId = $this->comment->site_id ?? $this->comment->post->site_id ?? null;

            return SeoService::getPostUrl($this->comment->post, $siteId).'#comment-'.$this->comment->id;
        }

        return tallcms_base_url($this->comment->site_id ?? null);
    }

    protected function getSiteName(): ?string
    {
        $siteId = $this->comment->site_id ?? $this->comment->post?->site_id ?? null;

        if (! $siteId) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\DB::table('tallcms_sites')
                ->where('id', $siteId)
                ->value('name');
        } catch (\Throwable) {
            return null;
        }
    }
}
