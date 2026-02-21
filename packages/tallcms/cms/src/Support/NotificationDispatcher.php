<?php

declare(strict_types=1);

namespace TallCms\Cms\Support;

use Illuminate\Notifications\Notifiable;

/**
 * Helper for sending notifications that resolves to App wrapper classes when they exist.
 *
 * This ensures that App-level notification customizations are used when notifications
 * are sent from the package, maintaining backward compatibility in standalone mode.
 */
class NotificationDispatcher
{
    /**
     * Map of package notification classes to their App wrapper counterparts.
     */
    protected static array $notificationMap = [
        \TallCms\Cms\Notifications\ContentApprovedNotification::class => 'App\\Notifications\\ContentApprovedNotification',
        \TallCms\Cms\Notifications\ContentRejectedNotification::class => 'App\\Notifications\\ContentRejectedNotification',
        \TallCms\Cms\Notifications\ContentSubmittedForReviewNotification::class => 'App\\Notifications\\ContentSubmittedForReviewNotification',
        \TallCms\Cms\Notifications\NewCommentNotification::class => 'App\\Notifications\\NewCommentNotification',
        \TallCms\Cms\Notifications\CommentApprovedNotification::class => 'App\\Notifications\\CommentApprovedNotification',
    ];

    /**
     * Send a notification, using App wrapper class if it exists.
     *
     * @param  Notifiable  $notifiable  The entity to notify
     * @param  object  $notification  The notification instance (package class)
     */
    public static function send($notifiable, object $notification): void
    {
        $packageClass = get_class($notification);
        $appClass = static::$notificationMap[$packageClass] ?? null;

        // If App wrapper exists and is a subclass, re-instantiate as App class
        if ($appClass && class_exists($appClass) && is_subclass_of($appClass, $packageClass)) {
            $notification = static::recreateAsAppClass($notification, $appClass);
        }

        $notifiable->notify($notification);
    }

    /**
     * Recreate a notification instance as its App wrapper class.
     *
     * Since App wrappers extend package classes with identical constructors,
     * we can use reflection to copy constructor arguments.
     */
    protected static function recreateAsAppClass(object $notification, string $appClass): object
    {
        $reflection = new \ReflectionClass($notification);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new $appClass;
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            // Check for protected/private properties via reflection
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                $property->setAccessible(true);
                $args[] = $property->getValue($notification);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
        }

        return new $appClass(...$args);
    }
}
