<?php

declare(strict_types=1);

namespace TallCms\Cms\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use TallCms\Cms\Models\CmsComment;

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

        return (new MailMessage)
            ->subject('Your comment has been approved')
            ->greeting("Hello {$this->comment->getAuthorName()}!")
            ->line("Your comment on \"{$postTitle}\" has been approved and is now visible.")
            ->action('View Your Comment', $postUrl)
            ->line('Thank you for your contribution!');
    }

    protected function getPostUrl(): string
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';
        $slug = $this->comment->post?->slug ?? '';

        return url("{$prefix}/blog/{$slug}#comment-{$this->comment->id}");
    }
}
