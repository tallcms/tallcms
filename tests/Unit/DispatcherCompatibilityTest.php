<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Event;
use ReflectionClass;
use TallCms\Cms\Events\PluginInstalled;
use TallCms\Cms\Events\PluginInstalling;
use TallCms\Cms\Events\PluginUninstalled;
use TallCms\Cms\Events\PluginUninstalling;
use TallCms\Cms\Events\ThemeActivated;
use TallCms\Cms\Events\ThemeActivating;
use TallCms\Cms\Events\ThemeInstalled;
use TallCms\Cms\Events\ThemeInstalling;
use TallCms\Cms\Events\ThemeRollback;
use TallCms\Cms\Models\Plugin;
use TallCms\Cms\Models\Theme;
use TallCms\Cms\Notifications\ContentApprovedNotification;
use TallCms\Cms\Notifications\ContentRejectedNotification;
use TallCms\Cms\Notifications\ContentSubmittedForReviewNotification;
use TallCms\Cms\Support\EventDispatcher;
use TallCms\Cms\Support\NotificationDispatcher;
use Tests\TestCase;

/**
 * Tests that dispatchers correctly resolve App wrapper classes.
 *
 * These tests ensure backward compatibility when package code dispatches
 * events/notifications - App\* wrappers should be used when they exist.
 */
class DispatcherCompatibilityTest extends TestCase
{
    /**
     * All package event classes that should have App wrappers.
     */
    protected array $packageEvents = [
        PluginInstalling::class,
        PluginInstalled::class,
        PluginUninstalling::class,
        PluginUninstalled::class,
        ThemeActivating::class,
        ThemeActivated::class,
        ThemeInstalling::class,
        ThemeInstalled::class,
        ThemeRollback::class,
    ];

    /**
     * All package notification classes that should have App wrappers.
     */
    protected array $packageNotifications = [
        ContentApprovedNotification::class,
        ContentRejectedNotification::class,
        ContentSubmittedForReviewNotification::class,
    ];

    /**
     * Test that all package events are in the EventDispatcher map.
     */
    public function test_all_package_events_are_in_dispatcher_map(): void
    {
        $reflection = new ReflectionClass(EventDispatcher::class);
        $property = $reflection->getProperty('eventMap');
        $property->setAccessible(true);
        $eventMap = $property->getValue();

        foreach ($this->packageEvents as $eventClass) {
            $this->assertArrayHasKey(
                $eventClass,
                $eventMap,
                "Package event {$eventClass} is missing from EventDispatcher map"
            );
        }
    }

    /**
     * Test that all package notifications are in the NotificationDispatcher map.
     */
    public function test_all_package_notifications_are_in_dispatcher_map(): void
    {
        $reflection = new ReflectionClass(NotificationDispatcher::class);
        $property = $reflection->getProperty('notificationMap');
        $property->setAccessible(true);
        $notificationMap = $property->getValue();

        foreach ($this->packageNotifications as $notificationClass) {
            $this->assertArrayHasKey(
                $notificationClass,
                $notificationMap,
                "Package notification {$notificationClass} is missing from NotificationDispatcher map"
            );
        }
    }

    /**
     * Test that App wrapper classes exist for all mapped events.
     */
    public function test_app_event_wrappers_exist(): void
    {
        $reflection = new ReflectionClass(EventDispatcher::class);
        $property = $reflection->getProperty('eventMap');
        $property->setAccessible(true);
        $eventMap = $property->getValue();

        foreach ($eventMap as $packageClass => $appClass) {
            $this->assertTrue(
                class_exists($appClass),
                "App wrapper {$appClass} does not exist for {$packageClass}"
            );

            $this->assertTrue(
                is_subclass_of($appClass, $packageClass),
                "App wrapper {$appClass} does not extend {$packageClass}"
            );
        }
    }

    /**
     * Test that App wrapper classes exist for all mapped notifications.
     */
    public function test_app_notification_wrappers_exist(): void
    {
        $reflection = new ReflectionClass(NotificationDispatcher::class);
        $property = $reflection->getProperty('notificationMap');
        $property->setAccessible(true);
        $notificationMap = $property->getValue();

        foreach ($notificationMap as $packageClass => $appClass) {
            $this->assertTrue(
                class_exists($appClass),
                "App wrapper {$appClass} does not exist for {$packageClass}"
            );

            $this->assertTrue(
                is_subclass_of($appClass, $packageClass),
                "App wrapper {$appClass} does not extend {$packageClass}"
            );
        }
    }

    /**
     * Test that EventDispatcher dispatches App wrapper when it exists.
     */
    public function test_event_dispatcher_uses_app_wrapper(): void
    {
        $dispatchedEvent = null;

        Event::listen('*', function ($eventName, $payload) use (&$dispatchedEvent) {
            if (isset($payload[0]) && is_object($payload[0])) {
                $class = get_class($payload[0]);
                if (str_contains($class, 'ThemeRollback')) {
                    $dispatchedEvent = $payload[0];
                }
            }
        });

        // Create a mock theme for the event
        $theme = new Theme([
            'name' => 'Test Theme',
            'slug' => 'test-theme',
            'version' => '1.0.0',
        ], '/tmp/test-theme');

        // Dispatch using package class
        EventDispatcher::dispatch(new ThemeRollback($theme));

        $this->assertNotNull($dispatchedEvent, 'Event was not dispatched');
        $this->assertInstanceOf(
            \App\Events\ThemeRollback::class,
            $dispatchedEvent,
            'Event should be dispatched as App wrapper class'
        );
    }

    /**
     * Test that EventDispatcher preserves event data when converting to App wrapper.
     */
    public function test_event_dispatcher_preserves_data(): void
    {
        $capturedEvent = null;

        Event::listen(\App\Events\ThemeActivated::class, function ($event) use (&$capturedEvent) {
            $capturedEvent = $event;
        });

        $theme = new Theme([
            'name' => 'Test Theme',
            'slug' => 'test-theme',
            'version' => '1.0.0',
        ], '/tmp/test-theme');

        $previousTheme = new Theme([
            'name' => 'Previous Theme',
            'slug' => 'previous-theme',
            'version' => '1.0.0',
        ], '/tmp/previous-theme');

        EventDispatcher::dispatch(new ThemeActivated($theme, $previousTheme));

        $this->assertNotNull($capturedEvent, 'Event listener was not called');
        $this->assertEquals('test-theme', $capturedEvent->theme->slug);
        $this->assertEquals('previous-theme', $capturedEvent->previousTheme->slug);
    }

    /**
     * Test that plugin events preserve all constructor arguments.
     */
    public function test_plugin_event_preserves_all_arguments(): void
    {
        $capturedEvent = null;

        Event::listen(\App\Events\PluginInstalled::class, function ($event) use (&$capturedEvent) {
            $capturedEvent = $event;
        });

        $plugin = new Plugin([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'vendor' => 'test-vendor',
            'version' => '1.0.0',
        ], '/tmp/test-plugin');

        $migrationsRan = ['2024_01_01_000000_create_test_table'];

        EventDispatcher::dispatch(new PluginInstalled($plugin, $migrationsRan, 'composer'));

        $this->assertNotNull($capturedEvent, 'Event listener was not called');
        $this->assertEquals('test-plugin', $capturedEvent->plugin->slug);
        $this->assertEquals($migrationsRan, $capturedEvent->migrationsRan);
        $this->assertEquals('composer', $capturedEvent->source);
    }
}
