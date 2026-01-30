<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Filament\Blocks\Concerns\HasAnimationOptions;
use TallCms\Cms\Tests\TestCase;

/**
 * Test helper that simulates Non-Pro environment.
 */
class NonProAnimationHelper
{
    use HasAnimationOptions;

    protected static function hasPro(): bool
    {
        return false;
    }
}

/**
 * Test helper that simulates Pro environment.
 */
class ProAnimationHelper
{
    use HasAnimationOptions;

    protected static function hasPro(): bool
    {
        return true;
    }
}

class HasAnimationOptionsTest extends TestCase
{
    // =========================================================================
    // Animation Type Tests (Non-Pro)
    // =========================================================================

    public function test_empty_animation_type_stays_empty(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig(['animation_type' => '']);

        $this->assertSame('', $result['animation_type']);
    }

    public function test_core_animation_types_pass_through(): void
    {
        $coreTypes = ['fade-in', 'fade-in-up'];

        foreach ($coreTypes as $type) {
            $result = NonProAnimationHelper::getAnimationConfig(['animation_type' => $type]);
            $this->assertSame($type, $result['animation_type'], "Core type '{$type}' should pass through");
        }
    }

    public function test_invalid_animation_type_becomes_empty(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig(['animation_type' => 'invalid-type']);

        $this->assertSame('', $result['animation_type']);
    }

    public function test_typo_animation_type_becomes_empty(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig(['animation_type' => 'fade-in-upp']);

        $this->assertSame('', $result['animation_type']);
    }

    public function test_pro_animation_types_stripped_without_pro(): void
    {
        $proTypes = ['fade-in-down', 'fade-in-left', 'fade-in-right', 'zoom-in', 'zoom-in-up'];

        foreach ($proTypes as $type) {
            $result = NonProAnimationHelper::getAnimationConfig(['animation_type' => $type]);
            $this->assertSame('', $result['animation_type'], "Pro type '{$type}' should be stripped without Pro");
        }
    }

    // =========================================================================
    // Animation Type Tests (Pro)
    // =========================================================================

    public function test_pro_animation_types_pass_through_with_pro(): void
    {
        $proTypes = ['fade-in-down', 'fade-in-left', 'fade-in-right', 'zoom-in', 'zoom-in-up'];

        foreach ($proTypes as $type) {
            $result = ProAnimationHelper::getAnimationConfig(['animation_type' => $type]);
            $this->assertSame($type, $result['animation_type'], "Pro type '{$type}' should pass through with Pro");
        }
    }

    // =========================================================================
    // Animation Duration Tests (Non-Pro)
    // =========================================================================

    public function test_core_durations_pass_through(): void
    {
        // Note: anim-duration-500 included for backwards compatibility
        $coreDurations = ['anim-duration-500', 'anim-duration-700', 'anim-duration-1000', 'anim-duration-1500'];

        foreach ($coreDurations as $duration) {
            $result = NonProAnimationHelper::getAnimationConfig(['animation_duration' => $duration]);
            $this->assertSame($duration, $result['animation_duration'], "Core duration '{$duration}' should pass through");
        }
    }

    public function test_invalid_duration_defaults_to_700(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig(['animation_duration' => 'invalid-duration']);

        $this->assertSame('anim-duration-700', $result['animation_duration']);
    }

    public function test_pro_durations_stripped_without_pro(): void
    {
        // Only 300ms is Pro-only (500ms kept for backwards compatibility)
        $proDurations = ['anim-duration-300'];

        foreach ($proDurations as $duration) {
            $result = NonProAnimationHelper::getAnimationConfig(['animation_duration' => $duration]);
            $this->assertSame('anim-duration-700', $result['animation_duration'], "Pro duration '{$duration}' should default to 700ms without Pro");
        }
    }

    // =========================================================================
    // Animation Duration Tests (Pro)
    // =========================================================================

    public function test_pro_durations_pass_through_with_pro(): void
    {
        // Only 300ms is Pro-only in UI (500ms kept for backwards compatibility)
        $proDurations = ['anim-duration-300'];

        foreach ($proDurations as $duration) {
            $result = ProAnimationHelper::getAnimationConfig(['animation_duration' => $duration]);
            $this->assertSame($duration, $result['animation_duration'], "Pro duration '{$duration}' should pass through with Pro");
        }
    }

    // =========================================================================
    // Stagger Tests (Non-Pro)
    // =========================================================================

    public function test_stagger_disabled_without_pro(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig(['animation_stagger' => true]);

        $this->assertFalse($result['animation_stagger']);
    }

    public function test_stagger_delay_forced_to_100_without_pro(): void
    {
        $delays = [0, 100, 200, 300];

        foreach ($delays as $delay) {
            $result = NonProAnimationHelper::getAnimationConfig(['animation_stagger_delay' => $delay]);
            $this->assertSame(100, $result['animation_stagger_delay'], "Stagger delay should be forced to 100 without Pro");
        }
    }

    // =========================================================================
    // Stagger Tests (Pro)
    // =========================================================================

    public function test_stagger_enabled_with_pro(): void
    {
        $result = ProAnimationHelper::getAnimationConfig(['animation_stagger' => true]);

        $this->assertTrue($result['animation_stagger']);
    }

    public function test_valid_stagger_delays_pass_through_with_pro(): void
    {
        $validDelays = [0, 100, 200, 300];

        foreach ($validDelays as $delay) {
            $result = ProAnimationHelper::getAnimationConfig(['animation_stagger_delay' => $delay]);
            $this->assertSame($delay, $result['animation_stagger_delay'], "Valid delay {$delay} should pass through with Pro");
        }
    }

    public function test_invalid_stagger_delay_defaults_to_100_with_pro(): void
    {
        $result = ProAnimationHelper::getAnimationConfig(['animation_stagger_delay' => 150]);

        $this->assertSame(100, $result['animation_stagger_delay']);
    }

    // =========================================================================
    // Default Values Tests
    // =========================================================================

    public function test_empty_config_returns_safe_defaults(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig([]);

        $this->assertSame('', $result['animation_type']);
        $this->assertSame('anim-duration-700', $result['animation_duration']);
        $this->assertFalse($result['animation_stagger']);
        $this->assertSame(100, $result['animation_stagger_delay']);
    }

    public function test_all_keys_always_present(): void
    {
        $result = NonProAnimationHelper::getAnimationConfig([]);

        $this->assertArrayHasKey('animation_type', $result);
        $this->assertArrayHasKey('animation_duration', $result);
        $this->assertArrayHasKey('animation_stagger', $result);
        $this->assertArrayHasKey('animation_stagger_delay', $result);
    }
}
